<?php
session_start();
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header('Location: ../auth/login.php');
    exit();
}
$studentId = (int)$_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

/* Course filter options */
$stmt = $conn->prepare("\n    SELECT DISTINCT c.course_id, c.course_name\n    FROM enrollments e\n    JOIN batches b ON b.batch_id = e.batch_id\n    JOIN courses c ON c.course_id = b.course_id\n    WHERE e.student_id = ?\n      AND e.enrollment_status IN ('Active','Completed')\n    ORDER BY c.course_name\n");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$courseOptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* All marked attendance rows for this student */
$stmt = $conn->prepare("\n    SELECT\n        att.attendance_id, att.attendance_status, att.marked_at,\n        cs.session_id, cs.class_date, cs.start_time, cs.end_time, cs.venue, cs.topic, cs.status AS session_status,\n        b.batch_id, b.batch_name,\n        c.course_id, c.course_name,\n        ua.fullname AS teacher_name\n    FROM attendance att\n    JOIN class_sessions cs ON cs.session_id = att.session_id\n    JOIN batches b ON b.batch_id = cs.batch_id\n    JOIN courses c ON c.course_id = b.course_id\n    JOIN user_accounts ua ON ua.user_id = cs.teacher_id\n    JOIN enrollments e ON e.batch_id = b.batch_id AND e.student_id = att.student_id\n    WHERE att.student_id = ?\n      AND e.enrollment_status IN ('Active','Completed')\n    ORDER BY cs.class_date DESC, cs.start_time DESC\n");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$attendanceRecords = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalClasses = count($attendanceRecords);
$presentCount = 0; $absentCount = 0; $lateCount = 0; $excusedCount = 0;
foreach ($attendanceRecords as $r) {
    switch ($r['attendance_status']) {
        case 'present': $presentCount++; break;
        case 'absent': $absentCount++; break;
        case 'late': $lateCount++; break;
        case 'excused': $excusedCount++; break;
    }
}
$rateDenominator = $presentCount + $absentCount + $lateCount; // excused records do not reduce the rate
$attendanceRate = $rateDenominator > 0 ? round((($presentCount + $lateCount) / $rateDenominator) * 100, 1) : 0;

/* Server-side filters */
$filterCourse = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$filterStatus = strtolower(trim($_GET['status'] ?? ''));
$filterDate = trim($_GET['date'] ?? '');
$allowedStatuses = ['present','absent','late','excused'];
if (!in_array($filterStatus, $allowedStatuses, true)) $filterStatus = '';
if ($filterDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) $filterDate = '';

$filteredRecords = array_values(array_filter($attendanceRecords, function($r) use ($filterCourse, $filterStatus, $filterDate) {
    if ($filterCourse > 0 && (int)$r['course_id'] !== $filterCourse) return false;
    if ($filterStatus !== '' && $r['attendance_status'] !== $filterStatus) return false;
    if ($filterDate !== '' && $r['class_date'] !== $filterDate) return false;
    return true;
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($projectName) ?> | Attendance</title>
<link rel="stylesheet" href="../css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="dashboard-shell">
<aside class="sidebar">
<div class="brand-panel"><div class="brand-icon"><i class="fas fa-graduation-cap"></i></div><div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Student Portal</p></div></div>
<nav class="sidebar-menu">
<a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a><a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a><a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a><a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i>Materials</a><a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i>Assignments</a><a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i>Quizzes</a><a href="exams.php" class="menu-link"><i class="fas fa-clipboard-list"></i>Exams</a><a href="grades.php" class="menu-link"><i class="fas fa-chart-column"></i>Grades</a><a href="progress.php" class="menu-link"><i class="fas fa-chart-line"></i>Progress</a><a href="attendance.php" class="menu-link active"><i class="fas fa-calendar-check"></i>Attendance</a><a href="schedule.php" class="menu-link"><i class="fas fa-calendar-days"></i>Schedule</a><a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i>Announcements</a><a href="discussions.php" class="menu-link"><i class="fas fa-comments"></i>Discussions</a><a href="tute_store.php" class="menu-link"><i class="fas fa-store"></i>Tute Store</a><a href="payments.php" class="menu-link"><i class="fas fa-credit-card"></i>Payments</a><a href="notifications.php" class="menu-link"><i class="fas fa-bell"></i>Notifications</a><a href="../auth/logout.php" class="menu-link logout-link"><i class="fas fa-sign-out-alt"></i>Logout</a>
</nav>
</aside>
<div class="content-area">
<header class="topbar"><div class="topbar-left"><button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Attendance</p><h1>My Attendance</h1></div></div><div class="topbar-right"><div class="project-pill">LearnFlow</div><a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?= e($studentName) ?></strong></div></div></div></header>
<main class="dashboard-main">
<section class="overview-cards">
<article class="summary-card"><div><p class="card-label">Attendance Overview</p><h2>Monitor your classroom attendance.</h2></div><div class="summary-icon"><i class="fas fa-calendar-check"></i></div></article>
<article class="stat-card"><div><p class="card-label">Attendance Rate</p><h3><?= e($attendanceRate) ?>%</h3></div><span class="stat-badge">Present + late; excused excluded</span></article>
<article class="stat-card"><div><p class="card-label">Marked Classes</p><h3><?= $totalClasses ?></h3></div><span class="stat-badge">Attendance records</span></article>
</section>

<section class="attendance-section">
<div class="attendance-section-header"><div><p class="card-label">Statistics</p><h2>Attendance Summary</h2><p>Review present, absent, late and excused records.</p></div></div>
<div class="attendance-stat-grid">
<div class="attendance-stat-item"><div class="attendance-stat-icon attendance-present"><i class="fas fa-check"></i></div><div><span>Present</span><strong><?= $presentCount ?></strong></div></div>
<div class="attendance-stat-item"><div class="attendance-stat-icon attendance-absent"><i class="fas fa-xmark"></i></div><div><span>Absent</span><strong><?= $absentCount ?></strong></div></div>
<div class="attendance-stat-item"><div class="attendance-stat-icon attendance-late"><i class="fas fa-clock"></i></div><div><span>Late</span><strong><?= $lateCount ?></strong></div></div>
<div class="attendance-stat-item"><div class="attendance-stat-icon"><i class="fas fa-circle-info"></i></div><div><span>Excused</span><strong><?= $excusedCount ?></strong></div></div>
</div>
</section>

<section class="attendance-section">
<div class="attendance-section-header"><div><p class="card-label">Overall Attendance</p><h2>Attendance Percentage</h2></div></div>
<div class="attendance-progress-card"><div class="attendance-progress-info"><span>Attendance Rate</span><strong><?= e($attendanceRate) ?>%</strong></div><div class="attendance-progress-bar"><div class="attendance-progress-fill" style="width:<?= e($attendanceRate) ?>%;"></div></div></div>
</section>

<section class="attendance-section">
<div class="attendance-section-header"><div><p class="card-label">Attendance Records</p><h2>View Attendance</h2><p>Filter attendance by course, status and date.</p></div></div>
<form method="GET" class="attendance-filter-row">
<div class="attendance-filter-group"><label for="attendanceCourse">Course</label><select id="attendanceCourse" name="course"><option value="0">All Courses</option><?php foreach ($courseOptions as $c): ?><option value="<?= (int)$c['course_id'] ?>" <?= $filterCourse === (int)$c['course_id'] ? 'selected' : '' ?>><?= e($c['course_name']) ?></option><?php endforeach; ?></select></div>
<div class="attendance-filter-group"><label for="attendanceStatus">Status</label><select id="attendanceStatus" name="status"><option value="">All Statuses</option><?php foreach ($allowedStatuses as $s): ?><option value="<?= e($s) ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></div>
<div class="attendance-filter-group"><label for="attendanceDate">Date</label><input type="date" id="attendanceDate" name="date" value="<?= e($filterDate) ?>"></div>
<div class="attendance-filter-group" style="justify-content:end;"><label>&nbsp;</label><button class="primary-btn" type="submit"><i class="fas fa-filter"></i> Apply</button></div>
</form>
</section>

<section class="attendance-section">
<div class="attendance-section-header"><div><p class="card-label">History</p><h2>Attendance Records</h2><p><?= count($filteredRecords) ?> record(s) shown.</p></div></div>
<?php if (empty($filteredRecords)): ?>
<div class="attendance-empty-state"><div class="attendance-empty-icon"><i class="fas fa-calendar-check"></i></div><h3>No Attendance Records</h3><p>Attendance will appear after a teacher marks a class session.</p></div>
<?php else: ?>
<div class="attendance-table-wrapper"><table class="attendance-table"><thead><tr><th>Date</th><th>Course</th><th>Batch</th><th>Topic</th><th>Time</th><th>Teacher</th><th>Venue</th><th>Status</th></tr></thead><tbody>
<?php foreach ($filteredRecords as $r): ?>
<tr><td><?= e($r['class_date']) ?></td><td><?= e($r['course_name']) ?></td><td><?= e($r['batch_name']) ?></td><td><?= e($r['topic'] ?: 'Class Session') ?></td><td><?= e(date('H:i', strtotime($r['start_time']))) ?> - <?= e(date('H:i', strtotime($r['end_time']))) ?></td><td><?= e($r['teacher_name']) ?></td><td><?= e($r['venue'] ?: 'Not specified') ?></td><td><strong><?= e(ucfirst($r['attendance_status'])) ?></strong></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
</main>
</div>
</div>
<script src="../js/student.js"></script>
</body>
</html>
