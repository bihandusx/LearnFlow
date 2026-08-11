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

function results_course_icon($courseName)
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

function results_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

function results_slug($value)
{
    $slug = strtolower(trim((string) $value));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'item';
}

/**
 * Standard letter grade from marks out of 100.
 * A >= 90, B+ >= 80, B >= 70, C+ >= 65, C >= 55, S >= 40, F < 40
 */
function results_marks_to_grade($marks)
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

function results_normalize_pct($marks, $totalMarks)
{
    $total = (int) ($totalMarks ?? 100);
    if ($total <= 0) {
        $total = 100;
    }

    return (int) round(((int) $marks / $total) * 100);
}

function results_status_from_pct($pct)
{
    $pct = (int) $pct;

    if ($pct >= 80) {
        return ['label' => 'Distinction', 'slug' => 'distinction'];
    }
    if ($pct >= 40) {
        return ['label' => 'Pass', 'slug' => 'pass'];
    }

    return ['label' => 'Fail', 'slug' => 'fail'];
}

function results_grade_pill_class($grade)
{
    $grade = strtoupper(trim((string) $grade));

    if ($grade === 'A') {
        return 'grade-a';
    }
    if ($grade === 'B+' || $grade === 'B') {
        return 'grade-b';
    }

    return 'grade-c';
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$studentFirstName = 'Student';
$resultRows = [];
$subjectOverview = [];
$courseFilterOptions = [];
$examFilterOptions = [];
$overallAveragePct = null;
$bestSubject = '—';
$examsSat = 0;
$hasResults = false;

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = results_first_name($linkedStudent['StudentName']);

    $examStmt = $conn->prepare(
        "SELECT c.CourseID, c.CourseName, t.TestID, t.Title, t.TotalMarks,
                e.ExamDate, er.Marks, er.Grade
         FROM exam_result er
         INNER JOIN exam e ON e.TestID = er.TestID
         INNER JOIN test t ON t.TestID = e.TestID
         INNER JOIN batch b ON b.BatchID = t.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         WHERE er.StudentID = ?
         ORDER BY e.ExamDate DESC, c.CourseName ASC, t.TestID DESC"
    );
    $examStmt->bind_param("i", $studentId);
    $examStmt->execute();
    $examResult = $examStmt->get_result();

    $rawRows = [];
    $testIds = [];
    $allPcts = [];
    $byCourse = [];

    while ($row = $examResult->fetch_assoc()) {
        if ($row['Marks'] === null) {
            continue;
        }

        $testId = (int) $row['TestID'];
        $pct = results_normalize_pct($row['Marks'], $row['TotalMarks']);
        $storedGrade = trim((string) ($row['Grade'] ?? ''));
        $grade = $storedGrade !== '' ? $storedGrade : results_marks_to_grade($pct);
        $courseId = (int) $row['CourseID'];
        $courseName = (string) $row['CourseName'];
        $title = (string) $row['Title'];

        $rawRows[] = [
            'test_id' => $testId,
            'course_id' => $courseId,
            'course_name' => $courseName,
            'course_slug' => 'course-' . $courseId,
            'exam_slug' => results_slug($title),
            'title' => $title,
            'pct' => $pct,
            'grade' => $grade,
            'exam_date' => $row['ExamDate'],
        ];

        $testIds[$testId] = true;
        $allPcts[] = $pct;

        if (!isset($byCourse[$courseId])) {
            $byCourse[$courseId] = [
                'course_name' => $courseName,
                'pcts' => [],
            ];
        }
        $byCourse[$courseId]['pcts'][] = $pct;

        $courseFilterOptions[$courseId] = $courseName;
        $examFilterOptions[results_slug($title)] = $title;
    }
    $examStmt->close();

    $classAvgByTest = [];
    if (!empty($testIds)) {
        $idList = array_map('intval', array_keys($testIds));
        $placeholders = implode(',', array_fill(0, count($idList), '?'));
        $types = str_repeat('i', count($idList));

        $avgSql =
            "SELECT er.TestID, er.Marks, t.TotalMarks
             FROM exam_result er
             INNER JOIN test t ON t.TestID = er.TestID
             WHERE er.TestID IN ($placeholders)";
        $avgStmt = $conn->prepare($avgSql);
        $avgStmt->bind_param($types, ...$idList);
        $avgStmt->execute();
        $avgResult = $avgStmt->get_result();

        $sums = [];
        $counts = [];
        while ($avgRow = $avgResult->fetch_assoc()) {
            if ($avgRow['Marks'] === null) {
                continue;
            }
            $tid = (int) $avgRow['TestID'];
            $pct = results_normalize_pct($avgRow['Marks'], $avgRow['TotalMarks']);
            if (!isset($sums[$tid])) {
                $sums[$tid] = 0;
                $counts[$tid] = 0;
            }
            $sums[$tid] += $pct;
            $counts[$tid]++;
        }
        $avgStmt->close();

        foreach ($sums as $tid => $sum) {
            $classAvgByTest[$tid] = (int) round($sum / $counts[$tid]);
        }
    }

    foreach ($rawRows as $row) {
        $status = results_status_from_pct($row['pct']);
        $classAvg = $classAvgByTest[$row['test_id']] ?? $row['pct'];

        $resultRows[] = [
            'course_slug' => $row['course_slug'],
            'exam_slug' => $row['exam_slug'],
            'title' => $row['title'],
            'course_name' => $row['course_name'],
            'course_icon' => results_course_icon($row['course_name']),
            'marks_label' => $row['pct'] . '%',
            'grade' => $row['grade'],
            'class_avg_label' => $classAvg . '%',
            'status_label' => $status['label'],
            'status_slug' => $status['slug'],
        ];
    }

    foreach ($byCourse as $courseId => $course) {
        $mean = array_sum($course['pcts']) / count($course['pcts']);
        $avgPct = (int) round($mean);
        $letter = results_marks_to_grade($mean);

        $subjectOverview[] = [
            'course_id' => $courseId,
            'course_name' => $course['course_name'],
            'course_icon' => results_course_icon($course['course_name']),
            'average_pct' => $avgPct,
            'grade' => $letter,
            'grade_class' => results_grade_pill_class($letter),
        ];
    }

    usort($subjectOverview, static function ($a, $b) {
        return strcasecmp($a['course_name'], $b['course_name']);
    });

    asort($courseFilterOptions, SORT_NATURAL | SORT_FLAG_CASE);
    asort($examFilterOptions, SORT_NATURAL | SORT_FLAG_CASE);

    $examsSat = count($resultRows);
    $hasResults = $examsSat > 0;

    if ($examsSat > 0) {
        $overallAveragePct = (int) round(array_sum($allPcts) / count($allPcts));

        $bestPct = null;
        $bestName = null;
        foreach ($subjectOverview as $subject) {
            if ($bestPct === null
                || $subject['average_pct'] > $bestPct
                || ($subject['average_pct'] === $bestPct
                    && strcasecmp($subject['course_name'], (string) $bestName) < 0)
            ) {
                $bestPct = $subject['average_pct'];
                $bestName = $subject['course_name'];
            }
        }
        $bestSubject = $bestName !== null ? $bestName : '—';
    }
}

$pageTitle = htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8') . "'s Examination Results";

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Examination Results | LEARNFLOW</title>


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
                class="nav-link"
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
                class="nav-link active"
            >

                <i class="fa-solid fa-award"></i>

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

                <i class="fa-solid fa-comments"></i>

                <span>Contact Teachers/Admins</span>

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

                        <h2>Examination Results</h2>

                        <p>
                            View your child's examination performance across all subjects.
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

            <div class="results-page-header">


                <div>

                    <h1>
                        <?php echo $pageTitle; ?>
                    </h1>

                    <p>
                        Results from all term tests and examinations sat so far.
                    </p>

                </div>


                <div class="results-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo $overallAveragePct !== null ? $overallAveragePct . '%' : '—'; ?>
                        </strong>

                        <span>
                            Overall Average
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo htmlspecialchars($bestSubject, ENT_QUOTES, 'UTF-8'); ?>
                        </strong>

                        <span>
                            Best Subject
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $examsSat; ?>
                        </strong>

                        <span>
                            Exams Sat
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 SUBJECT GRADE OVERVIEW
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Subject Grade Overview
                        </h3>


                    </div>


                    <?php if (!$hasResults): ?>

                        <p style="padding: 0 20px 20px; color: #6b7280; font-size: 0.85rem;">
                            No examination results available yet.
                        </p>

                    <?php else: ?>

                        <?php foreach ($subjectOverview as $subject): ?>

                            <div class="grade-item">


                                <div class="course-icon">

                                    <i class="fa-solid <?php echo htmlspecialchars($subject['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                </div>


                                <div class="course-info">

                                    <h4>
                                        <?php echo htmlspecialchars($subject['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h4>

                                    <p>
                                        Average: <?php echo (int) $subject['average_pct']; ?>%
                                    </p>

                                </div>


                                <div class="grade-pill <?php echo htmlspecialchars($subject['grade_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($subject['grade'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>


                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="results-filter-card">


                <select
                    id="courseFilter"
                    class="results-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <?php foreach ($courseFilterOptions as $courseId => $courseName): ?>

                        <option value="course-<?php echo (int) $courseId; ?>">
                            <?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?>
                        </option>

                    <?php endforeach; ?>

                </select>



                <select
                    id="examFilter"
                    class="results-filter"
                >

                    <option value="all">
                        All Exams
                    </option>

                    <?php foreach ($examFilterOptions as $examSlug => $examTitle): ?>

                        <option value="<?php echo htmlspecialchars($examSlug, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($examTitle, ENT_QUOTES, 'UTF-8'); ?>
                        </option>

                    <?php endforeach; ?>

                </select>


            </div>



            <!-- RESULTS TABLE -->

            <div
                class="results-table-card"
                <?php echo !$hasResults ? 'style="display: none;"' : ''; ?>
            >


                <table
                    class="results-table"
                    id="resultsTable"
                >


                    <thead>

                        <tr>

                            <th>Exam</th>

                            <th>Course</th>

                            <th>Marks</th>

                            <th>Grade</th>

                            <th>Class Average</th>

                            <th>Result</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($resultRows as $row): ?>

                            <tr
                                class="results-row"
                                data-course="<?php echo htmlspecialchars($row['course_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-exam="<?php echo htmlspecialchars($row['exam_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                            >

                                <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td>

                                    <span class="results-course">

                                        <i class="fa-solid <?php echo htmlspecialchars($row['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                        <?php echo htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'); ?>

                                    </span>

                                </td>

                                <td><?php echo htmlspecialchars($row['marks_label'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?php echo htmlspecialchars($row['grade'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?php echo htmlspecialchars($row['class_avg_label'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td>

                                    <span class="status-badge status-<?php echo htmlspecialchars($row['status_slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($row['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>


                </table>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noResults"
                class="no-results"
                <?php echo !$hasResults ? 'style="display: flex;"' : ''; ?>
            >

                <i class="fa-solid fa-award"></i>

                <h3>
                    No Results Found
                </h3>

                <p>
                    <?php echo $hasResults
                        ? 'Try changing your filter options.'
                        : 'No examination results are available for your child yet.'; ?>
                </p>

            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Results JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const courseFilter =
            document.getElementById("courseFilter");


        const examFilter =
            document.getElementById("examFilter");


        const resultRows =
            document.querySelectorAll(".results-row");


        const resultsTableCard =
            document.querySelector(".results-table-card");


        const noResults =
            document.getElementById("noResults");


        const hasRows = resultRows.length > 0;



        function filterResults() {


            if (!hasRows) {
                return;
            }


            const selectedCourse =
                courseFilter.value;


            const selectedExam =
                examFilter.value;


            let visibleCount = 0;



            resultRows.forEach(
                function (row) {


                    const course =
                        row.getAttribute("data-course");


                    const exam =
                        row.getAttribute("data-exam");


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesExam =
                        selectedExam === "all" ||
                        exam === selectedExam;


                    if (matchesCourse && matchesExam) {

                        row.style.display = "";

                        visibleCount++;

                    }

                    else {

                        row.style.display = "none";

                    }


                }
            );



            if (visibleCount === 0) {

                resultsTableCard.style.display = "none";

                noResults.style.display = "flex";

            }

            else {

                resultsTableCard.style.display = "block";

                noResults.style.display = "none";

            }


        }



        courseFilter.addEventListener("change", filterResults);


        examFilter.addEventListener("change", filterResults);


    }
);


</script>


</body>

</html>
