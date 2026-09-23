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
function clampPercent($value): float { return max(0, min(100, round((float)$value, 1))); }

/* One progress row per active/completed enrollment */
$stmt = $conn->prepare("\n    SELECT\n        e.enrollment_id, e.enrollment_status,\n        c.course_id, c.course_name,\n        b.batch_id, b.batch_name,\n        (SELECT COUNT(*) FROM modules m WHERE m.course_id = c.course_id) AS total_modules,\n        (SELECT COUNT(*)\n           FROM modules m\n           JOIN student_module_progress smp\n             ON smp.module_id = m.module_id AND smp.student_id = ?\n          WHERE m.course_id = c.course_id\n            AND smp.progress_status = 'completed') AS completed_modules,\n        COALESCE((SELECT AVG(COALESCE(smp2.progress_percentage,0))\n           FROM modules m2\n           LEFT JOIN student_module_progress smp2\n             ON smp2.module_id = m2.module_id AND smp2.student_id = ?\n          WHERE m2.course_id = c.course_id),0) AS module_progress,\n        (SELECT COUNT(*)\n           FROM assessments a\n          WHERE a.batch_id = b.batch_id\n            AND a.status IN ('published','closed','archived')) AS total_assessments,\n        (SELECT COUNT(*)\n           FROM assessments a2\n          WHERE a2.batch_id = b.batch_id\n            AND a2.status IN ('published','closed','archived')\n            AND (\n                (a2.assessment_type = 'assignment' AND EXISTS (\n                    SELECT 1 FROM assignment_submissions s\n                     WHERE s.assignment_id = a2.assessment_id AND s.student_id = ?\n                ))\n                OR\n                (a2.assessment_type IN ('quiz','exam') AND EXISTS (\n                    SELECT 1 FROM assessment_attempts aa\n                     WHERE aa.assessment_id = a2.assessment_id\n                       AND aa.student_id = ?\n                       AND aa.attempt_status IN ('submitted','graded')\n                ))\n            )) AS completed_assessments\n    FROM enrollments e\n    JOIN batches b ON b.batch_id = e.batch_id\n    JOIN courses c ON c.course_id = b.course_id\n    WHERE e.student_id = ?\n      AND e.enrollment_status IN ('Active','Completed')\n    ORDER BY c.course_name, b.batch_name\n");
$stmt->bind_param('iiiii', $studentId, $studentId, $studentId, $studentId, $studentId);
$stmt->execute();
$courseProgressData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$totalModules = 0;
$completedModules = 0;
$totalAssessments = 0;
$completedAssessments = 0;
$courseProgressValues = [];

foreach ($courseProgressData as &$course) {
    $tm = (int)$course['total_modules'];
    $cm = (int)$course['completed_modules'];
    $ta = (int)$course['total_assessments'];
    $ca = (int)$course['completed_assessments'];
    $moduleProgress = clampPercent($course['module_progress']);
    $assessmentProgress = $ta > 0 ? clampPercent(($ca / $ta) * 100) : 0;

    if ($tm > 0 && $ta > 0) $overall = ($moduleProgress + $assessmentProgress) / 2;
    elseif ($tm > 0) $overall = $moduleProgress;
    elseif ($ta > 0) $overall = $assessmentProgress;
    else $overall = 0;

    $course['module_progress'] = clampPercent($moduleProgress);
    $course['assessment_progress'] = clampPercent($assessmentProgress);
    $course['progress'] = clampPercent($overall);

    $totalModules += $tm;
    $completedModules += $cm;
    $totalAssessments += $ta;
    $completedAssessments += $ca;
    $courseProgressValues[] = $course['progress'];
}
unset($course);

$overallProgress = count($courseProgressValues) ? round(array_sum($courseProgressValues) / count($courseProgressValues), 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($projectName) ?> | Learning Progress</title>
<link rel="stylesheet" href="../css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="dashboard-shell">
<aside class="sidebar">
<div class="brand-panel"><div class="brand-icon"><i class="fas fa-graduation-cap"></i></div><div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Student Portal</p></div></div>
<nav class="sidebar-menu">
<a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a><a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a><a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a><a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i>Materials</a><a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i>Assignments</a><a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i>Quizzes</a><a href="exams.php" class="menu-link"><i class="fas fa-clipboard-list"></i>Exams</a><a href="grades.php" class="menu-link"><i class="fas fa-chart-column"></i>Grades</a><a href="progress.php" class="menu-link active"><i class="fas fa-chart-line"></i>Progress</a><a href="attendance.php" class="menu-link"><i class="fas fa-calendar-check"></i>Attendance</a><a href="schedule.php" class="menu-link"><i class="fas fa-calendar-days"></i>Schedule</a><a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i>Announcements</a><a href="discussions.php" class="menu-link"><i class="fas fa-comments"></i>Discussions</a><a href="tute_store.php" class="menu-link"><i class="fas fa-store"></i>Tute Store</a><a href="payments.php" class="menu-link"><i class="fas fa-credit-card"></i>Payments</a><a href="notifications.php" class="menu-link"><i class="fas fa-bell"></i>Notifications</a><a href="../auth/logout.php" class="menu-link logout-link"><i class="fas fa-sign-out-alt"></i>Logout</a>
</nav>
</aside>
<div class="content-area">
<header class="topbar"><div class="topbar-left"><button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Learning Analytics</p><h1>Learning Progress</h1></div></div><div class="topbar-right"><div class="project-pill">LearnFlow</div><a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?= e($studentName) ?></strong></div></div></div></header>
<main class="dashboard-main">
<section class="overview-cards">
<article class="summary-card"><div><p class="card-label">Overall Progress</p><h2>Track your learning journey.</h2></div><div class="summary-icon"><i class="fas fa-chart-line"></i></div></article>
<article class="stat-card"><div><p class="card-label">Overall Completion</p><h3><?= e($overallProgress) ?>%</h3></div><span class="stat-badge">Module + assessment progress</span></article>
<article class="stat-card"><div><p class="card-label">Completed Modules</p><h3><?= $completedModules ?></h3></div><span class="stat-badge">of <?= $totalModules ?> modules</span></article>
</section>

<section class="progress-section">
<div class="progress-section-header"><div><p class="card-label">Progress Summary</p><h2>Learning Completion</h2><p>Progress is calculated from stored module progress and completed assessments.</p></div></div>
<div class="progress-stat-grid">
<div class="progress-stat-item"><div class="progress-stat-icon"><i class="fas fa-layer-group"></i></div><div><span>Modules</span><strong><?= $completedModules ?> / <?= $totalModules ?></strong></div></div>
<div class="progress-stat-item"><div class="progress-stat-icon"><i class="fas fa-list-check"></i></div><div><span>Assessments</span><strong><?= $completedAssessments ?> / <?= $totalAssessments ?></strong></div></div>
<div class="progress-stat-item"><div class="progress-stat-icon"><i class="fas fa-book-open"></i></div><div><span>Enrolled Courses</span><strong><?= count($courseProgressData) ?></strong></div></div>
</div>
</section>

<section class="progress-section">
<div class="progress-section-header"><div><p class="card-label">Course Progress</p><h2>Progress by Course</h2><p>Each enrolled batch shows module progress and assessment completion separately.</p></div></div>
<?php if (empty($courseProgressData)): ?>
<div class="progress-empty-state"><div class="progress-empty-icon"><i class="fas fa-chart-line"></i></div><h3>No Progress Data Available</h3><p>Enroll in a course to begin tracking learning progress.</p></div>
<?php else: ?>
<div class="course-progress-list">
<?php foreach ($courseProgressData as $course): ?>
<div class="course-progress-card">
<div class="course-progress-header"><div><h3><?= e($course['course_name']) ?></h3><p><?= e($course['batch_name']) ?> · <?= e($course['enrollment_status']) ?></p></div><strong><?= e($course['progress']) ?>%</strong></div>
<div style="margin-top:14px;"><div class="course-progress-footer"><span>Overall progress</span><strong><?= e($course['progress']) ?>%</strong></div><div class="progress-bar"><div class="progress-bar-fill" style="width:<?= e($course['progress']) ?>%;"></div></div></div>
<div style="margin-top:14px;"><div class="course-progress-footer"><span>Modules: <?= (int)$course['completed_modules'] ?> / <?= (int)$course['total_modules'] ?></span><strong><?= e($course['module_progress']) ?>%</strong></div><div class="progress-bar"><div class="progress-bar-fill" style="width:<?= e($course['module_progress']) ?>%;"></div></div></div>
<div style="margin-top:14px;"><div class="course-progress-footer"><span>Assessments: <?= (int)$course['completed_assessments'] ?> / <?= (int)$course['total_assessments'] ?></span><strong><?= e($course['assessment_progress']) ?>%</strong></div><div class="progress-bar"><div class="progress-bar-fill" style="width:<?= e($course['assessment_progress']) ?>%;"></div></div></div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>
</main>
</div>
</div>
<script src="../js/student.js"></script>
</body>
</html>
