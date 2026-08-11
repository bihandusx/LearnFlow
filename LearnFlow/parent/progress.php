<?php

session_start();

include "../config/db.php";

if (
    !isset($_SESSION['user_id'], $_SESSION['role'])
    || $_SESSION['role'] !== 'Parent'
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION['user_id'];

$ensureParent = $conn->prepare("INSERT IGNORE INTO parent (ParentID) VALUES (?)");
$ensureParent->bind_param("i", $parentId);
$ensureParent->execute();
$ensureParent->close();

$parentStmt = $conn->prepare(
    "SELECT u.UserID, u.Name
     FROM users u
     INNER JOIN parent p ON p.ParentID = u.UserID
     WHERE u.UserID = ? AND u.Role = 'Parent'
     LIMIT 1"
);
$parentStmt->bind_param("i", $parentId);
$parentStmt->execute();
$parentResult = $parentStmt->get_result();
$parent = $parentResult ? $parentResult->fetch_assoc() : null;
$parentStmt->close();

if (!$parent) {
    header("Location: ../auth/login.php");
    exit();
}

$studentStmt = $conn->prepare(
    "SELECT s.StudentID, su.Name AS StudentName
     FROM parent_student ps
     INNER JOIN student s ON s.StudentID = ps.StudentID
     INNER JOIN users su ON su.UserID = s.StudentID
     WHERE ps.ParentID = ?
     LIMIT 1"
);
$studentStmt->bind_param("i", $parentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$linkedStudent = $studentResult ? $studentResult->fetch_assoc() : null;
$studentStmt->close();

function parent_initials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    if (!empty($parts[0])) {
        $initials .= strtoupper(substr($parts[0], 0, 1));
    }

    if (count($parts) > 1 && !empty($parts[count($parts) - 1])) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }

    return $initials !== '' ? $initials : 'P';
}

function progress_course_icon($courseName)
{
    $name = strtolower((string) $courseName);

    if (strpos($name, 'math') !== false) {
        return 'fa-calculator';
    }
    if (strpos($name, 'physics') !== false) {
        return 'fa-atom';
    }
    if (strpos($name, 'chemistry') !== false) {
        return 'fa-flask';
    }
    if (strpos($name, 'english') !== false) {
        return 'fa-language';
    }
    if (strpos($name, 'web') !== false || strpos($name, 'develop') !== false) {
        return 'fa-code';
    }

    return 'fa-book';
}

function progress_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

/**
 * Standard letter grade from marks out of 100.
 * A >= 90, B+ >= 80, B >= 70, C+ >= 65, C >= 55, S >= 40, F < 40
 */
function progress_marks_to_grade($marks)
{
    $marks = (int) round((float) $marks);

    if ($marks >= 90) {
        return 'A';
    }
    if ($marks >= 80) {
        return 'B+';
    }
    if ($marks >= 70) {
        return 'B';
    }
    if ($marks >= 65) {
        return 'C+';
    }
    if ($marks >= 55) {
        return 'C';
    }
    if ($marks >= 40) {
        return 'S';
    }
    return 'F';
}

function progress_short_exam_title($title)
{
    $title = trim((string) $title);
    if (strcasecmp($title, 'Mid-Term Examination') === 0) {
        return 'Mid-Term';
    }
    return $title !== '' ? $title : 'Exam';
}

function progress_trend_from_scores(array $scores)
{
    $scored = array_values(array_filter($scores, static function ($v) {
        return $v !== null;
    }));

    if (count($scored) < 2) {
        return ['label' => 'Stable', 'slug' => 'stable', 'icon' => 'fa-minus'];
    }

    $first = (int) $scored[0];
    $last = (int) $scored[count($scored) - 1];

    if ($last > $first) {
        return ['label' => 'Improving', 'slug' => 'up', 'icon' => 'fa-arrow-trend-up'];
    }
    if ($last < $first) {
        return ['label' => 'Declining', 'slug' => 'down', 'icon' => 'fa-arrow-trend-down'];
    }

    return ['label' => 'Stable', 'slug' => 'stable', 'icon' => 'fa-minus'];
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$studentFirstName = 'Student';
$trendRows = [];
$columnTitles = ['Test 1', 'Test 2', 'Mid-Term'];
$allMarks = [];
$improvingCount = 0;
$needsAttentionCount = 0;
$averageGrade = '—';
$hasExamData = false;

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = progress_first_name($linkedStudent['StudentName']);

    $examStmt = $conn->prepare(
        "SELECT c.CourseID, c.CourseName, t.Title, t.TotalMarks,
                e.ExamDate, er.Marks, er.Grade
         FROM exam_result er
         INNER JOIN exam e ON e.TestID = er.TestID
         INNER JOIN test t ON t.TestID = e.TestID
         INNER JOIN batch b ON b.BatchID = t.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         WHERE er.StudentID = ?
         ORDER BY c.CourseName ASC, e.ExamDate ASC, t.TestID ASC"
    );
    $examStmt->bind_param("i", $studentId);
    $examStmt->execute();
    $examResult = $examStmt->get_result();

    $byCourse = [];
    while ($row = $examResult->fetch_assoc()) {
        $courseId = (int) $row['CourseID'];
        if (!isset($byCourse[$courseId])) {
            $byCourse[$courseId] = [
                'course_name' => $row['CourseName'],
                'exams' => [],
            ];
        }

        $total = (int) ($row['TotalMarks'] ?? 100);
        if ($total <= 0) {
            $total = 100;
        }
        $marks = $row['Marks'] !== null ? (int) $row['Marks'] : null;
        if ($marks !== null) {
            // Normalize to out-of-100 for display when TotalMarks differs
            $pct = (int) round(($marks / $total) * 100);
            $allMarks[] = $pct;
        }

        $byCourse[$courseId]['exams'][] = [
            'title' => $row['Title'],
            'marks' => $marks !== null ? (int) round(($marks / $total) * 100) : null,
            'exam_date' => $row['ExamDate'],
        ];
    }
    $examStmt->close();

    foreach ($byCourse as $course) {
        $exams = $course['exams'];
        $lastThree = array_slice($exams, -3);
        if (count($lastThree) === 0) {
            continue;
        }

        $scores = [];
        $titles = [];
        foreach ($lastThree as $exam) {
            $scores[] = $exam['marks'];
            $titles[] = progress_short_exam_title($exam['title']);
        }

        while (count($scores) < 3) {
            $scores[] = null;
            $titles[] = '—';
        }

        $trend = progress_trend_from_scores($scores);
        if ($trend['label'] === 'Improving') {
            $improvingCount++;
        } elseif ($trend['label'] === 'Declining') {
            $needsAttentionCount++;
        }

        $trendRows[] = [
            'course_name' => $course['course_name'],
            'course_icon' => progress_course_icon($course['course_name']),
            'scores' => $scores,
            'titles' => $titles,
            'trend' => $trend,
        ];
    }

    if (!empty($trendRows)) {
        $hasExamData = true;
        // Prefer column headers from a row that has three titled exams
        foreach ($trendRows as $row) {
            $usable = true;
            foreach ($row['titles'] as $t) {
                if ($t === '—') {
                    $usable = false;
                    break;
                }
            }
            if ($usable) {
                $columnTitles = $row['titles'];
                break;
            }
        }
    }

    if (count($allMarks) > 0) {
        $mean = array_sum($allMarks) / count($allMarks);
        $averageGrade = progress_marks_to_grade($mean);
    }
}

$pageTitle = htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8') . "'s Academic Progress";

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Academic Progress | LEARNFLOW</title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <!-- Parent CSS -->

    <link
        rel="stylesheet"
        href="../css/parent.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        <!-- Brand -->

        <div class="sidebar-brand">

            <i class="fa-solid fa-graduation-cap"></i>

            <span>LEARNFLOW</span>

        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">


            <!-- Main -->

            <div class="nav-section-title">
                Main
            </div>


            <a
                href="dashboard.php"
                class="nav-link"
            >

                <i class="fa-solid fa-house"></i>

                <span>Dashboard</span>

            </a>


            <a
                href="profile.php"
                class="nav-link"
            >

                <i class="fa-solid fa-user"></i>

                <span>My Profile</span>

            </a>


            <!-- Student Monitoring -->

            <div class="nav-section-title">
                Student Monitoring
            </div>


            <a
                href="attendance.php"
                class="nav-link"
            >

                <i class="fa-solid fa-calendar-check"></i>

                <span>Attendance</span>

            </a>


            <a
                href="progress.php"
                class="nav-link active"
            >

                <i class="fa-solid fa-chart-line"></i>

                <span>Academic Progress</span>

            </a>


            <a
                href="assignments.php"
                class="nav-link"
            >

                <i class="fa-solid fa-file-pen"></i>

                <span>Assignments</span>

            </a>


            <a
                href="results.php"
                class="nav-link"
            >

                <i class="fa-solid fa-square-poll-vertical"></i>

                <span>Examination Results</span>

            </a>


            <a
                href="courses.php"
                class="nav-link"
            >

                <i class="fa-solid fa-book-open"></i>

                <span>Course Participation</span>

            </a>


            <!-- Communication -->

            <div class="nav-section-title">
                Communication
            </div>


            <a
                href="announcements.php"
                class="nav-link"
            >

                <i class="fa-solid fa-bullhorn"></i>

                <span>Announcements</span>

            </a>


            <a
                href="contact.php"
                class="nav-link"
            >

                <i class="fa-solid fa-envelope"></i>

                <span>Contact Teachers</span>

            </a>


            <a
                href="meetings.php"
                class="nav-link"
            >

                <i class="fa-solid fa-handshake"></i>

                <span>Request Meeting</span>

            </a>


            <!-- Payments -->

            <div class="nav-section-title">
                Payments
            </div>


            <a
                href="payments.php"
                class="nav-link"
            >

                <i class="fa-solid fa-credit-card"></i>

                <span>Payment Status</span>

            </a>


            <a
                href="receipts.php"
                class="nav-link"
            >

                <i class="fa-solid fa-receipt"></i>

                <span>Payment Receipts</span>

            </a>


            <!-- Settings -->

            <div class="nav-section-title">
                Settings
            </div>


            <a
                href="emergency-contact.php"
                class="nav-link"
            >

                <i class="fa-solid fa-phone-volume"></i>

                <span>Emergency Contact</span>

            </a>


        </nav>


        <!-- Sidebar Footer -->

        <div class="sidebar-footer">

            <a
                href="../auth/logout.php"
                class="nav-link"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

            </a>

        </div>


    </aside>



    <!-- =================================================
         MAIN AREA
    ================================================== -->

    <main class="main-area">


        <!-- TOP NAVBAR -->

        <header class="top-navbar">


            <div class="page-title">


                <div class="profile-top-title">


                    <button
                        class="menu-toggle"
                        id="menuToggle"
                    >

                        <i class="fa-solid fa-bars"></i>

                    </button>


                    <div>

                        <h2>Academic Progress</h2>

                        <p>
                            Track your child's academic performance across all courses.
                        </p>

                    </div>


                </div>


            </div>



            <!-- User Area -->

            <div class="user-area">


                <button
                    class="notification-btn"
                    id="notificationButton"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-badge"></span>

                </button>


                <div class="user-profile">


                    <div class="user-avatar">

                        <?php echo htmlspecialchars($parentInitials, ENT_QUOTES, 'UTF-8'); ?>

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            <?php echo htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="user-role">
                            Parent
                        </span>

                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="progress-page-header">


                <div>

                    <h1>
                        <?php echo $pageTitle; ?>
                    </h1>

                    <p>
                        A summary of course completion and academic performance this term.
                    </p>

                </div>


                <div class="progress-summary">


                    <div class="summary-item">

                        <strong>
                            79%
                        </strong>

                        <span>
                            Overall Progress
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo htmlspecialchars($averageGrade, ENT_QUOTES, 'UTF-8'); ?>
                        </strong>

                        <span>
                            Average Grade
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $improvingCount; ?>
                        </strong>

                        <span>
                            Improving
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $needsAttentionCount; ?>
                        </strong>

                        <span>
                            Needs Attention
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 COURSE PROGRESS OVERVIEW (static — formula deferred)
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Course Progress Overview
                        </h3>


                        <a
                            href="courses.php"
                            class="view-all"
                        >

                            View Courses

                        </a>


                    </div>



                    <!-- Course 1 -->

                    <div class="course-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-calculator"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                Combined Mathematics
                            </h4>

                            <p>
                                Mr. Perera • Advanced Level
                            </p>

                        </div>


                        <div class="progress-container">


                            <div class="progress-label">

                                <span>
                                    Progress
                                </span>

                                <span>
                                    80%
                                </span>

                            </div>


                            <div class="progress-bar">

                                <div
                                    class="progress-fill"
                                    style="width: 80%;"
                                ></div>

                            </div>


                        </div>


                    </div>



                    <!-- Course 2 -->

                    <div class="course-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-flask"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                Chemistry
                            </h4>

                            <p>
                                Dr. Fernando • Advanced Level
                            </p>

                        </div>


                        <div class="progress-container">


                            <div class="progress-label">

                                <span>
                                    Progress
                                </span>

                                <span>
                                    65%
                                </span>

                            </div>


                            <div class="progress-bar">

                                <div
                                    class="progress-fill"
                                    style="width: 65%;"
                                ></div>

                            </div>


                        </div>


                    </div>



                    <!-- Course 3 -->

                    <div class="course-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-atom"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                Physics
                            </h4>

                            <p>
                                Mr. Silva • Advanced Level
                            </p>

                        </div>


                        <div class="progress-container">


                            <div class="progress-label">

                                <span>
                                    Progress
                                </span>

                                <span>
                                    72%
                                </span>

                            </div>


                            <div class="progress-bar">

                                <div
                                    class="progress-fill"
                                    style="width: 72%;"
                                ></div>

                            </div>


                        </div>


                    </div>



                    <!-- Course 4 -->

                    <div class="course-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-language"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                General English
                            </h4>

                            <p>
                                Ms. Perera • General
                            </p>

                        </div>


                        <div class="progress-container">


                            <div class="progress-label">

                                <span>
                                    Progress
                                </span>

                                <span>
                                    100%
                                </span>

                            </div>


                            <div class="progress-bar">

                                <div
                                    class="progress-fill"
                                    style="width: 100%;"
                                ></div>

                            </div>


                        </div>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 PERFORMANCE TREND (live)
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Test & Exam Performance Trend
                        </h3>


                        <a
                            href="results.php"
                            class="view-all"
                        >

                            View All Results

                        </a>


                    </div>



                    <div class="performance-table-card">

                        <?php if (!$linkedStudent): ?>

                            <p>
                                No linked student found for this parent account.
                            </p>

                        <?php elseif (!$hasExamData): ?>

                            <p>
                                No exam results available yet for
                                <?php echo htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8'); ?>.
                            </p>

                        <?php else: ?>

                        <table class="performance-table">


                            <thead>

                                <tr>

                                    <th>Course</th>

                                    <?php foreach ($columnTitles as $colTitle): ?>
                                        <th>
                                            <?php echo htmlspecialchars($colTitle, ENT_QUOTES, 'UTF-8'); ?>
                                        </th>
                                    <?php endforeach; ?>

                                    <th>Trend</th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php foreach ($trendRows as $row): ?>

                                <tr>

                                    <td>

                                        <span class="performance-course">

                                            <i class="fa-solid <?php echo htmlspecialchars($row['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                            <?php echo htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'); ?>

                                        </span>

                                    </td>

                                    <?php foreach ($row['scores'] as $score): ?>
                                        <td>
                                            <?php
                                            echo $score === null
                                                ? '—'
                                                : htmlspecialchars((string) $score, ENT_QUOTES, 'UTF-8') . '%';
                                            ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td>

                                        <span class="trend-indicator trend-<?php echo htmlspecialchars($row['trend']['slug'], ENT_QUOTES, 'UTF-8'); ?>">

                                            <i class="fa-solid <?php echo htmlspecialchars($row['trend']['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                            <?php echo htmlspecialchars($row['trend']['label'], ENT_QUOTES, 'UTF-8'); ?>

                                        </span>

                                    </td>

                                </tr>

                                <?php endforeach; ?>

                            </tbody>


                        </table>

                        <?php endif; ?>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 TEACHER REMARKS 
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Teacher Remarks
                        </h3>


                    </div>



                    <div class="remark-item">


                        <div class="remark-header">

                            <span class="remark-course">

                                <i class="fa-solid fa-flask"></i>

                                Chemistry

                            </span>

                            <span class="remark-date">
                                30 Jul 2026
                            </span>

                        </div>


                        <p class="remark-text">

                            Alex's performance has declined slightly over the last two
                            tests. Additional practice on organic chemistry topics is
                            recommended before the mid-term examination.

                        </p>


                    </div>



                    <div class="remark-item">


                        <div class="remark-header">

                            <span class="remark-course">

                                <i class="fa-solid fa-calculator"></i>

                                Combined Mathematics

                            </span>

                            <span class="remark-date">
                                26 Jul 2026
                            </span>

                        </div>


                        <p class="remark-text">

                            Great improvement in calculus problem-solving. Alex is
                            actively participating in class discussions and completing
                            all assignments on time.

                        </p>


                    </div>



                    <div class="remark-item">


                        <div class="remark-header">

                            <span class="remark-course">

                                <i class="fa-solid fa-atom"></i>

                                Physics

                            </span>

                            <span class="remark-date">
                                20 Jul 2026
                            </span>

                        </div>


                        <p class="remark-text">

                            Steady progress in mechanics and wave theory. Encourage
                            Alex to revise past paper questions ahead of the next
                            assessment.

                        </p>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


</body>

</html>
