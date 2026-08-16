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

function dashboard_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

function dashboard_course_icon($courseName)
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

/**
 * Batch module completion % for an enrolled course.
 * completed/total modules for BatchID; null when the batch has no modules.
 * Same formula as courses.php / progress.php.
 * See scripts/academic_progress_calculation.md
 */
function dashboard_module_pct($moduleTotal, $moduleCompleted)
{
    $total = (int) $moduleTotal;
    if ($total <= 0) {
        return null;
    }

    return (int) round(((int) $moduleCompleted / $total) * 100);
}

/**
 * Standard letter grade from marks out of 100.
 * A >= 90, B+ >= 80, B >= 70, C+ >= 65, C >= 55, S >= 40, F < 40
 */
function dashboard_marks_to_grade($marks)
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

function dashboard_format_lkr($amount)
{
    return 'LKR ' . number_format((float) $amount, 0);
}

function dashboard_format_date($date, $format = 'd M Y')
{
    $ts = $date ? strtotime((string) $date) : false;
    return $ts ? date($format, $ts) : '';
}

function dashboard_display_payment_status($status, $dueDate)
{
    if ($status === 'Paid') {
        return 'Paid';
    }

    $dueTs = $dueDate ? strtotime((string) $dueDate) : false;
    $today = strtotime(date('Y-m-d'));

    if ($dueTs && $dueTs < $today) {
        return 'Overdue';
    }

    return 'Due';
}

/**
 * @return array{status:string,label:string,is_late:bool}
 */
function dashboard_assignment_derive_status($dueDate, $submittedDate, $marks)
{
    $today = date('Y-m-d');
    $hasSubmission = $submittedDate !== null && $submittedDate !== '';
    $isLate = false;

    if ($hasSubmission && $dueDate !== null && $dueDate !== '' && $submittedDate > $dueDate) {
        $isLate = true;
    }

    if ($hasSubmission) {
        if ($marks === null || $marks === '') {
            return ['status' => 'submitted', 'label' => 'Submitted', 'is_late' => $isLate];
        }
        return ['status' => 'graded', 'label' => 'Graded', 'is_late' => $isLate];
    }

    if ($dueDate !== null && $dueDate !== '' && $dueDate < $today) {
        return ['status' => 'overdue', 'label' => 'Overdue', 'is_late' => false];
    }

    return ['status' => 'pending', 'label' => 'Pending', 'is_late' => false];
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);
$parentFirstName = dashboard_first_name($parentName);

$studentFirstName = 'Student';
$attendanceRateDisplay = '0%';
$pendingAssignmentsCount = 0;
$averageGradeDisplay = '—';
$paymentDueDisplay = dashboard_format_lkr(0);
$coursePreview = [];
$upcomingItems = [];
$announcementPreview = [];
$paymentPreview = [];

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = dashboard_first_name($linkedStudent['StudentName']);
    $today = date('Y-m-d');

    // --- Attendance rate ---
    $attStmt = $conn->prepare(
        "SELECT a.Status
         FROM attendance a
         WHERE a.StudentID = ?"
    );
    $attStmt->bind_param("i", $studentId);
    $attStmt->execute();
    $attResult = $attStmt->get_result();
    $presentCount = 0;
    $absentCount = 0;
    while ($row = $attResult->fetch_assoc()) {
        if ($row['Status'] === 'Absent') {
            $absentCount++;
        } else {
            $presentCount++;
        }
    }
    $attStmt->close();
    $attTotal = $presentCount + $absentCount;
    $attendanceRateDisplay = ($attTotal > 0
        ? (int) round(($presentCount / $attTotal) * 100)
        : 0) . '%';

    // --- Pending assignments count + upcoming pending list ---
    $assignStmt = $conn->prepare(
        "SELECT t.TestID, t.Title, a.DueDate, s.SubmittedDate, s.Marks
         FROM assignment a
         INNER JOIN test t ON t.TestID = a.TestID
         INNER JOIN batch b ON b.BatchID = t.BatchID
         INNER JOIN enrollment e ON e.BatchID = b.BatchID AND e.StudentID = ?
         LEFT JOIN assignment_submission s
           ON s.TestID = a.TestID AND s.StudentID = ?
         ORDER BY a.DueDate ASC, t.Title ASC"
    );
    $assignStmt->bind_param("ii", $studentId, $studentId);
    $assignStmt->execute();
    $assignResult = $assignStmt->get_result();

    while ($row = $assignResult->fetch_assoc()) {
        $derived = dashboard_assignment_derive_status(
            $row['DueDate'],
            $row['SubmittedDate'],
            $row['Marks']
        );

        if ($derived['status'] === 'pending') {
            $pendingAssignmentsCount++;
            $upcomingItems[] = [
                'sort_date' => (string) ($row['DueDate'] ?? '9999-12-31'),
                'title' => $row['Title'],
                'date_label' => 'Due: ' . dashboard_format_date($row['DueDate'], 'd F Y'),
                'status_label' => 'Pending',
                'status_slug' => 'pending',
                'icon' => 'fa-file-pen',
            ];
        }
    }
    $assignStmt->close();

    // --- Average grade (percent + letter) ---
    $examMarksStmt = $conn->prepare(
        "SELECT er.Marks, t.TotalMarks
         FROM exam_result er
         INNER JOIN exam ex ON ex.TestID = er.TestID
         INNER JOIN test t ON t.TestID = er.TestID
         WHERE er.StudentID = ?"
    );
    $examMarksStmt->bind_param("i", $studentId);
    $examMarksStmt->execute();
    $examMarksResult = $examMarksStmt->get_result();
    $allMarks = [];
    while ($row = $examMarksResult->fetch_assoc()) {
        if ($row['Marks'] === null) {
            continue;
        }
        $total = (int) ($row['TotalMarks'] ?? 100);
        if ($total <= 0) {
            $total = 100;
        }
        $allMarks[] = (int) round(((int) $row['Marks'] / $total) * 100);
    }
    $examMarksStmt->close();

    if (count($allMarks) > 0) {
        $mean = array_sum($allMarks) / count($allMarks);
        $pct = (int) round($mean);
        $letter = dashboard_marks_to_grade($mean);
        $averageGradeDisplay = $pct . '% · ' . $letter;
    }

    // --- Payment outstanding + preview ---
    $paymentStmt = $conn->prepare(
        "SELECT p.Amount, p.PaymentDate, p.Status, p.ItemTitle, p.DueDate
         FROM payment p
         WHERE p.StudentID = ?
         ORDER BY COALESCE(p.PaymentDate, p.DueDate) DESC, p.PaymentID DESC"
    );
    $paymentStmt->bind_param("i", $studentId);
    $paymentStmt->execute();
    $paymentResult = $paymentStmt->get_result();
    $outstanding = 0.0;
    $paymentRows = [];

    while ($row = $paymentResult->fetch_assoc()) {
        $displayStatus = dashboard_display_payment_status($row['Status'], $row['DueDate']);
        $amount = (float) $row['Amount'];

        if ($displayStatus !== 'Paid') {
            $outstanding += $amount;
            $dateLabel = 'Due: ' . dashboard_format_date($row['DueDate'], 'd F Y');
        } else {
            $dateLabel = 'Paid: ' . dashboard_format_date($row['PaymentDate'], 'd F Y');
        }

        $paymentRows[] = [
            'title' => $row['ItemTitle'] ?: 'Tuition Fee',
            'date_label' => $dateLabel,
            'status_label' => $displayStatus,
            'status_slug' => strtolower($displayStatus),
            'icon' => $displayStatus === 'Paid' ? 'fa-receipt' : 'fa-credit-card',
        ];
    }
    $paymentStmt->close();

    $paymentDueDisplay = dashboard_format_lkr($outstanding);
    $paymentPreview = array_slice($paymentRows, 0, 3);

    // --- Course participation preview ---
    $enrollStmt = $conn->prepare(
        "SELECT c.CourseID, c.CourseName, c.Stream,
                (SELECT COUNT(*) FROM module m WHERE m.BatchID = e.BatchID) AS ModuleCount,
                (SELECT COUNT(*) FROM module m
                  WHERE m.BatchID = e.BatchID AND m.IsCompleted = 1) AS ModuleCompleted
         FROM enrollment e
         INNER JOIN batch b ON b.BatchID = e.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         WHERE e.StudentID = ?
         ORDER BY c.CourseName ASC
         LIMIT 3"
    );
    $enrollStmt->bind_param("i", $studentId);
    $enrollStmt->execute();
    $enrollResult = $enrollStmt->get_result();

    while ($row = $enrollResult->fetch_assoc()) {
        $stream = trim((string) ($row['Stream'] ?? ''));
        if ($stream === '') {
            $stream = 'General';
        }
        $progressPct = dashboard_module_pct($row['ModuleCount'], $row['ModuleCompleted']);

        $coursePreview[] = [
            'course_name' => $row['CourseName'],
            'stream_label' => 'Advanced Level • ' . $stream,
            'icon' => dashboard_course_icon($row['CourseName']),
            'progress_pct' => $progressPct,
        ];
    }
    $enrollStmt->close();

    // --- Upcoming / overdue exams ---
    $examStmt = $conn->prepare(
        "SELECT t.Title, ex.ExamDate, er.ResultID
         FROM exam ex
         INNER JOIN test t ON t.TestID = ex.TestID
         INNER JOIN batch b ON b.BatchID = t.BatchID
         INNER JOIN enrollment e ON e.BatchID = b.BatchID AND e.StudentID = ?
         LEFT JOIN exam_result er ON er.TestID = ex.TestID AND er.StudentID = ?
         ORDER BY ex.ExamDate ASC, t.Title ASC"
    );
    $examStmt->bind_param("ii", $studentId, $studentId);
    $examStmt->execute();
    $examResult = $examStmt->get_result();

    while ($row = $examResult->fetch_assoc()) {
        $examDate = (string) ($row['ExamDate'] ?? '');
        if ($examDate === '') {
            continue;
        }

        $hasResult = $row['ResultID'] !== null && $row['ResultID'] !== '';

        if ($examDate >= $today) {
            $upcomingItems[] = [
                'sort_date' => $examDate,
                'title' => $row['Title'],
                'date_label' => 'Date: ' . dashboard_format_date($examDate, 'd F Y'),
                'status_label' => 'Upcoming',
                'status_slug' => 'pending',
                'icon' => 'fa-clipboard-check',
            ];
        } elseif (!$hasResult) {
            $upcomingItems[] = [
                'sort_date' => $examDate,
                'title' => $row['Title'],
                'date_label' => 'Date: ' . dashboard_format_date($examDate, 'd F Y'),
                'status_label' => 'Overdue',
                'status_slug' => 'due',
                'icon' => 'fa-clipboard-check',
            ];
        }
    }
    $examStmt->close();

    usort($upcomingItems, static function ($a, $b) {
        return strcmp($a['sort_date'], $b['sort_date']);
    });
    $upcomingItems = array_slice($upcomingItems, 0, 5);

    // --- Announcements preview ---
    $annStmt = $conn->prepare(
        "SELECT a.Title, a.Content, a.PublishDate
         FROM announcement a
         WHERE a.BatchID IS NULL
            OR a.BatchID IN (
                SELECT e.BatchID FROM enrollment e WHERE e.StudentID = ?
            )
         ORDER BY a.PublishDate DESC, a.AnnouncementID DESC
         LIMIT 3"
    );
    $annStmt->bind_param("i", $studentId);
    $annStmt->execute();
    $annResult = $annStmt->get_result();

    while ($row = $annResult->fetch_assoc()) {
        $announcementPreview[] = [
            'title' => $row['Title'],
            'content' => $row['Content'],
            'date_label' => dashboard_format_date($row['PublishDate'], 'j F Y'),
        ];
    }
    $annStmt->close();
}

$parentNameSafe = htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8');
$parentInitialsSafe = htmlspecialchars($parentInitials, ENT_QUOTES, 'UTF-8');
$parentFirstNameSafe = htmlspecialchars($parentFirstName, ENT_QUOTES, 'UTF-8');
$studentFirstNameSafe = htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Parent Dashboard | LEARNFLOW</title>


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
                class="nav-link active"
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


                <div style="display: flex; align-items: center; gap: 15px;">


                    <button
                        class="menu-toggle"
                        id="menuToggle"
                    >

                        <i class="fa-solid fa-bars"></i>

                    </button>


                    <div>

                        <h2>Parent Dashboard</h2>

                        <p>
                            Stay connected with your child's learning journey.
                        </p>

                    </div>


                </div>


            </div>



            <div class="user-area">


                <!-- Notification (stub — left static) -->

                <button
                    class="notification-btn"
                    id="notificationButton"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-badge"></span>

                </button>


                <!-- User -->

                <div class="user-profile">


                    <div class="user-avatar">

                        <?php echo $parentInitialsSafe; ?>

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            <?php echo $parentNameSafe; ?>
                        </span>

                        <span class="user-role">
                            Parent
                        </span>

                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             DASHBOARD CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- WELCOME BANNER -->

            <div class="welcome-banner">


                <div class="welcome-content">


                    <h1>
                        Welcome back, <?php echo $parentFirstNameSafe; ?>! 👋
                    </h1>


                    <p>
                        <?php if ($linkedStudent): ?>
                            Here's how <?php echo $studentFirstNameSafe; ?> is progressing in their studies.
                        <?php else: ?>
                            Link a student to this account to see learning progress here.
                        <?php endif; ?>
                    </p>


                </div>


                <div class="welcome-icon">

                    <i class="fa-solid fa-people-roof"></i>

                </div>


            </div>


            <?php if (!$linkedStudent): ?>

                <div class="dashboard-card">

                    <p>
                        No linked student found for this parent account.
                    </p>

                </div>

            <?php else: ?>


            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <!-- Attendance -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-calendar-check"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo htmlspecialchars($attendanceRateDisplay, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="stat-label">
                            Attendance Rate
                        </span>

                    </div>


                </div>



                <!-- Assignments -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-file-pen"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo (int) $pendingAssignmentsCount; ?>
                        </span>

                        <span class="stat-label">
                            Pending Assignments
                        </span>

                    </div>


                </div>



                <!-- Results -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-award"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo htmlspecialchars($averageGradeDisplay, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="stat-label">
                            Average Grade
                        </span>

                    </div>


                </div>



                <!-- Payments -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-credit-card"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            <?php echo htmlspecialchars($paymentDueDisplay, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="stat-label">
                            Payment Due
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 DASHBOARD GRID
            ================================================== -->

            <div class="dashboard-grid">


                <!-- LEFT COLUMN -->

                <div>


                    <!-- COURSE PARTICIPATION -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Course Participation
                            </h3>


                            <a
                                href="courses.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>


                        <?php if (empty($coursePreview)): ?>

                            <p>
                                No course enrollments found.
                            </p>

                        <?php else: ?>

                            <?php foreach ($coursePreview as $course): ?>

                                <div class="course-item">


                                    <div class="course-icon">

                                        <i class="fa-solid <?php echo htmlspecialchars($course['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                    </div>


                                    <div class="course-info">

                                        <h4>
                                            <?php echo htmlspecialchars($course['course_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </h4>

                                        <p>
                                            <?php echo htmlspecialchars($course['stream_label'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>

                                    </div>


                                    <div class="progress-container">

                                        <?php if ($course['progress_pct'] !== null): ?>

                                        <div class="progress-label">

                                            <span>
                                                Progress
                                            </span>

                                            <span>
                                                <?php echo (int) $course['progress_pct']; ?>%
                                            </span>

                                        </div>


                                        <div class="progress-bar">

                                            <div
                                                class="progress-fill"
                                                style="width: <?php echo (int) $course['progress_pct']; ?>%;"
                                            ></div>

                                        </div>

                                        <?php endif; ?>


                                    </div>


                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


                    </div>



                    <!-- UPCOMING ASSIGNMENTS & EXAMS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Upcoming Assignments & Exams
                            </h3>


                            <a
                                href="assignments.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>


                        <?php if (empty($upcomingItems)): ?>

                            <p>
                                No upcoming assignments or exams.
                            </p>

                        <?php else: ?>

                            <?php foreach ($upcomingItems as $item): ?>

                                <div class="assignment-item">


                                    <div class="assignment-icon">

                                        <i class="fa-solid <?php echo htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                    </div>


                                    <div class="assignment-info">

                                        <h4>
                                            <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </h4>

                                        <p>
                                            <?php echo htmlspecialchars($item['date_label'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>

                                    </div>


                                    <span class="status-badge status-<?php echo htmlspecialchars($item['status_slug'], ENT_QUOTES, 'UTF-8'); ?>">

                                        <?php echo htmlspecialchars($item['status_label'], ENT_QUOTES, 'UTF-8'); ?>

                                    </span>


                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


                    </div>


                </div>



                <!-- RIGHT COLUMN -->

                <div>


                    <!-- ANNOUNCEMENTS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Teacher Announcements
                            </h3>


                            <a
                                href="announcements.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>


                        <?php if (empty($announcementPreview)): ?>

                            <p>
                                No announcements yet.
                            </p>

                        <?php else: ?>

                            <?php foreach ($announcementPreview as $ann): ?>

                                <div class="announcement-item">


                                    <h4>
                                        <?php echo htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h4>


                                    <p>
                                        <?php echo htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>


                                    <div class="announcement-date">

                                        <?php echo htmlspecialchars($ann['date_label'], ENT_QUOTES, 'UTF-8'); ?>

                                    </div>


                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


                    </div>



                    <!-- PAYMENT STATUS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Payment Status
                            </h3>


                            <a
                                href="payments.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>


                        <?php if (empty($paymentPreview)): ?>

                            <p>
                                No payment records found.
                            </p>

                        <?php else: ?>

                            <?php foreach ($paymentPreview as $pay): ?>

                                <div class="assignment-item">


                                    <div class="assignment-icon">

                                        <i class="fa-solid <?php echo htmlspecialchars($pay['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                    </div>


                                    <div class="assignment-info">

                                        <h4>
                                            <?php echo htmlspecialchars($pay['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        </h4>

                                        <p>
                                            <?php echo htmlspecialchars($pay['date_label'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>

                                    </div>


                                    <span class="status-badge status-<?php echo htmlspecialchars($pay['status_slug'], ENT_QUOTES, 'UTF-8'); ?>">

                                        <?php echo htmlspecialchars($pay['status_label'], ENT_QUOTES, 'UTF-8'); ?>

                                    </span>


                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


                    </div>



                    <!-- QUICK ACTIONS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Quick Actions
                            </h3>


                        </div>



                        <div class="quick-actions">


                            <a
                                href="results.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-award"></i>

                                Exam Results

                            </a>


                            <a
                                href="attendance.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-calendar-check"></i>

                                Attendance

                            </a>


                            <a
                                href="contact.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-comments"></i>

                                Contact Teacher

                            </a>


                            <a
                                href="meetings.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-handshake"></i>

                                Request Meeting

                            </a>


                        </div>


                    </div>


                </div>


            </div>


            <?php endif; ?>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


</body>

</html>
