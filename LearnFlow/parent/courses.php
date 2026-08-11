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

function courses_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

function courses_course_icon($courseName)
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

function courses_banner_class($courseName)
{
    $name = strtolower((string) $courseName);

    if (strpos($name, 'math') !== false) {
        return 'mathematics-banner';
    }
    if (strpos($name, 'physics') !== false) {
        return 'physics-banner';
    }
    if (strpos($name, 'chemistry') !== false) {
        return 'chemistry-banner';
    }
    if (strpos($name, 'english') !== false) {
        return 'english-banner';
    }

    return 'english-banner';
}

/**
 * Static Course Progress placeholders (no institute-wide formula yet).
 * See scripts/academic_progress_calculation.md
 */
function courses_static_progress_pct($courseId)
{
    $placeholders = [
        1 => 70,
        2 => 80,
        3 => 72,
        4 => 65,
        5 => 100,
    ];

    return $placeholders[(int) $courseId] ?? 75;
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$studentFirstName = 'Student';
$courseCards = [];
$enrolledCount = 0;
$avgAttendanceDisplay = '—';
$assignmentCompletionDisplay = '—';
// Static Average Progress placeholder (deferred formula)
$averageProgressDisplay = '79%';

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = courses_first_name($linkedStudent['StudentName']);

    $enrollStmt = $conn->prepare(
        "SELECT e.EnrollmentStatus, e.BatchID,
                c.CourseID, c.CourseName, c.Stream,
                tu.Name AS TeacherName,
                (SELECT COUNT(*) FROM module m WHERE m.BatchID = e.BatchID) AS ModuleCount,
                (SELECT COUNT(*) FROM attendance a
                  WHERE a.StudentID = ? AND a.CourseID = c.CourseID) AS AttTotal,
                (SELECT COUNT(*) FROM attendance a
                  WHERE a.StudentID = ? AND a.CourseID = c.CourseID
                    AND a.Status = 'Present') AS AttPresent,
                (SELECT COUNT(*) FROM assignment a
                  INNER JOIN test t ON t.TestID = a.TestID
                  WHERE t.BatchID = e.BatchID) AS AssignTotal,
                (SELECT COUNT(*) FROM assignment a
                  INNER JOIN test t ON t.TestID = a.TestID
                  INNER JOIN assignment_submission s
                    ON s.TestID = a.TestID AND s.StudentID = ?
                  WHERE t.BatchID = e.BatchID) AS AssignSubmitted,
                (SELECT COUNT(*) FROM discussion_post dp
                  INNER JOIN discussion_forum df ON df.ForumID = dp.ForumID
                  WHERE df.BatchID = e.BatchID) AS ForumPosts
         FROM enrollment e
         INNER JOIN batch b ON b.BatchID = e.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         LEFT JOIN users tu ON tu.UserID = c.TeacherID
         WHERE e.StudentID = ?
         ORDER BY c.CourseName ASC"
    );
    $enrollStmt->bind_param("iiii", $studentId, $studentId, $studentId, $studentId);
    $enrollStmt->execute();
    $enrollResult = $enrollStmt->get_result();

    $attPresentSum = 0;
    $attTotalSum = 0;
    $assignSubmittedSum = 0;
    $assignTotalSum = 0;

    while ($row = $enrollResult->fetch_assoc()) {
        $courseId = (int) $row['CourseID'];
        $attTotal = (int) $row['AttTotal'];
        $attPresent = (int) $row['AttPresent'];
        $assignTotal = (int) $row['AssignTotal'];
        $assignSubmitted = (int) $row['AssignSubmitted'];

        $attPresentSum += $attPresent;
        $attTotalSum += $attTotal;
        $assignSubmittedSum += $assignSubmitted;
        $assignTotalSum += $assignTotal;

        $attendancePct = $attTotal > 0
            ? (int) round(($attPresent / $attTotal) * 100)
            : null;

        $statusRaw = trim((string) ($row['EnrollmentStatus'] ?? 'Active'));
        $isCompleted = strcasecmp($statusRaw, 'Completed') === 0;
        $stream = trim((string) ($row['Stream'] ?? ''));
        $teacherName = trim((string) ($row['TeacherName'] ?? ''));
        $progressPct = courses_static_progress_pct($courseId);

        $courseCards[] = [
            'course_id' => $courseId,
            'course_name' => $row['CourseName'],
            'stream' => $stream !== '' ? strtoupper($stream) : 'GENERAL',
            'teacher_name' => $teacherName !== '' ? $teacherName : '—',
            'module_count' => (int) $row['ModuleCount'],
            'status_label' => $isCompleted ? 'Completed' : ($statusRaw !== '' ? $statusRaw : 'Active'),
            'is_completed' => $isCompleted,
            'banner_class' => courses_banner_class($row['CourseName']),
            'icon' => courses_course_icon($row['CourseName']),
            'progress_pct' => $progressPct,
            'attendance_display' => $attendancePct !== null ? $attendancePct . '%' : '—',
            'assignments_display' => $assignTotal > 0
                ? $assignSubmitted . '/' . $assignTotal
                : '0/0',
            'forum_posts' => (int) $row['ForumPosts'],
        ];
    }

    $enrollStmt->close();

    $enrolledCount = count($courseCards);

    if ($attTotalSum > 0) {
        $avgAttendanceDisplay = (int) round(($attPresentSum / $attTotalSum) * 100) . '%';
    }

    if ($assignTotalSum > 0) {
        $assignmentCompletionDisplay = (int) round(($assignSubmittedSum / $assignTotalSum) * 100) . '%';
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Course Participation | LEARNFLOW</title>


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
                class="nav-link"
            >

                <i class="fa-solid fa-award"></i>

                <span>Examination Results</span>

            </a>


            <a
                href="courses.php"
                class="nav-link active"
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

                        <h2>Course Participation</h2>

                        <p>
                            Monitor your child's engagement across enrolled courses.
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
             COURSE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="courses-page-header">


                <div>

                    <h1>
                        <?php echo htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8'); ?>'s Course Participation
                    </h1>

                    <p>
                        Track enrollment, progress and engagement in every course.
                    </p>

                </div>


                <button
                    type="button"
                    class="download-report-btn"
                    id="downloadReportButton"
                >

                    <i class="fa-solid fa-download"></i>

                    Download Report

                </button>


            </div>



            <!-- PARTICIPATION STATISTICS -->

            <div class="stats-grid">


                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo (int) $enrolledCount; ?>
                        </span>

                        <span class="stat-label">
                            Enrolled Courses
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-calendar-check"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo htmlspecialchars($avgAttendanceDisplay, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="stat-label">
                            Average Attendance
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-file-circle-check"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo htmlspecialchars($assignmentCompletionDisplay, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="stat-label">
                            Assignment Completion
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo htmlspecialchars($averageProgressDisplay, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="stat-label">
                            Average Progress
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 ENROLLED COURSES
            ================================================== -->

            <div class="course-grid">

                <?php if (!$linkedStudent): ?>

                    <p>
                        No linked student found for this parent account.
                    </p>

                <?php elseif (empty($courseCards)): ?>

                    <p>
                        No course enrollments found for
                        <?php echo htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8'); ?>.
                    </p>

                <?php else: ?>

                    <?php foreach ($courseCards as $card): ?>

                        <div class="course-card">


                            <div class="course-card-banner <?php echo htmlspecialchars($card['banner_class'], ENT_QUOTES, 'UTF-8'); ?>">

                                <i class="fa-solid <?php echo htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                <span>
                                    <?php echo htmlspecialchars($card['stream'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>

                            </div>


                            <div class="course-card-body">


                                <div class="course-status<?php echo $card['is_completed'] ? ' completed-status' : ''; ?>">

                                    <span<?php echo $card['is_completed'] ? '' : ' class="course-active"'; ?>>
                                        <?php echo htmlspecialchars($card['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>

                                </div>


                                <h3>
                                    <?php echo htmlspecialchars($card['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </h3>


                                <div class="course-meta">

                                    <span>

                                        <i class="fa-solid fa-user-tie"></i>

                                        <?php echo htmlspecialchars($card['teacher_name'], ENT_QUOTES, 'UTF-8'); ?>

                                    </span>

                                    <span>

                                        <i class="fa-solid fa-layer-group"></i>

                                        <?php echo (int) $card['module_count']; ?> Modules

                                    </span>

                                </div>


                                <div class="course-progress">


                                    <div class="course-progress-label">

                                        <span>
                                            Course Progress
                                        </span>

                                        <strong>
                                            <?php echo (int) $card['progress_pct']; ?>%
                                        </strong>

                                    </div>


                                    <div class="course-progress-bar">

                                        <div
                                            class="course-progress-fill<?php echo $card['is_completed'] ? ' completed-fill' : ''; ?>"
                                            style="width: <?php echo (int) $card['progress_pct']; ?>%;"
                                        ></div>

                                    </div>


                                </div>


                                <div class="participation-metrics">


                                    <div class="participation-metric">

                                        <strong><?php echo htmlspecialchars($card['attendance_display'], ENT_QUOTES, 'UTF-8'); ?></strong>

                                        <span>Attendance</span>

                                    </div>


                                    <div class="participation-metric">

                                        <strong><?php echo htmlspecialchars($card['assignments_display'], ENT_QUOTES, 'UTF-8'); ?></strong>

                                        <span>Assignments</span>

                                    </div>


                                    <div class="participation-metric">

                                        <strong><?php echo (int) $card['forum_posts']; ?></strong>

                                        <span>Forum Posts</span>

                                    </div>


                                </div>


                                <a
                                    href="progress.php"
                                    class="view-details-btn"
                                >

                                    View Details

                                    <i class="fa-solid fa-arrow-right"></i>

                                </a>


                            </div>


                        </div>

                    <?php endforeach; ?>

                <?php endif; ?>

            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Course Participation JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const downloadButton =
            document.getElementById("downloadReportButton");


        if (downloadButton) {


            downloadButton.addEventListener(
                "click",
                function () {


                    alert(
                        "The course participation report download will be connected to the backend later."
                    );


                }
            );


        }


    }
);


</script>


</body>

</html>
