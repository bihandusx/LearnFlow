<?php
session_start();
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header('Location: ../auth/login.php');
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function pct($score, $total): string {
    if ($score === null || (float)$total <= 0) return '-';
    return number_format(((float)$score / (float)$total) * 100, 1) . '%';
}

/* Enrolled course filter options */
$stmt = $conn->prepare("\n    SELECT DISTINCT c.course_id, c.course_name\n    FROM enrollments e\n    JOIN batches b ON b.batch_id = e.batch_id\n    JOIN courses c ON c.course_id = b.course_id\n    WHERE e.student_id = ?\n      AND e.enrollment_status IN ('Active','Completed')\n    ORDER BY c.course_name\n");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$courseOptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Assignment results */
$stmt = $conn->prepare("\n    SELECT\n        a.assessment_id, a.title, 'Assignment' AS result_type,\n        c.course_id, c.course_name, b.batch_name, a.total_marks,\n        s.marks AS score, s.submission_status AS result_status,\n        s.feedback, s.graded_at AS result_date, NULL AS attempt_number,\n        NULL AS grade_text\n    FROM assignment_submissions s\n    JOIN assignments ass ON ass.assignment_id = s.assignment_id\n    JOIN assessments a ON a.assessment_id = ass.assignment_id\n    JOIN batches b ON b.batch_id = a.batch_id\n    JOIN courses c ON c.course_id = b.course_id\n    JOIN enrollments e ON e.batch_id = b.batch_id AND e.student_id = s.student_id\n    WHERE s.student_id = ?\n      AND e.enrollment_status IN ('Active','Completed')\n      AND a.assessment_type = 'assignment'\n      AND a.status <> 'archived'\n");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$assignmentRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Quiz and exam results */
$stmt = $conn->prepare("\n    SELECT\n        a.assessment_id, a.title,\n        CASE WHEN a.assessment_type='quiz' THEN 'Quiz' ELSE 'Exam' END AS result_type,\n        c.course_id, c.course_name, b.batch_name, a.total_marks,\n        aa.score, aa.attempt_status AS result_status,\n        (SELECT GROUP_CONCAT(DISTINCT an.feedback SEPARATOR ' | ')\n           FROM attempt_answers an\n          WHERE an.attempt_id = aa.attempt_id\n            AND an.feedback IS NOT NULL\n            AND an.feedback <> '') AS feedback,\n        COALESCE(aa.submitted_at, aa.started_at) AS result_date,\n        aa.attempt_number, aa.grade AS grade_text\n    FROM assessment_attempts aa\n    JOIN assessments a ON a.assessment_id = aa.assessment_id\n    JOIN batches b ON b.batch_id = a.batch_id\n    JOIN courses c ON c.course_id = b.course_id\n    JOIN enrollments e ON e.batch_id = b.batch_id AND e.student_id = aa.student_id\n    WHERE aa.student_id = ?\n      AND e.enrollment_status IN ('Active','Completed')\n      AND a.assessment_type IN ('quiz','exam')\n      AND aa.attempt_status IN ('submitted','graded')\n      AND a.status <> 'archived'\n");
$stmt->bind_param('i', $studentId);
$stmt->execute();
$attemptRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$allResults = array_merge($assignmentRows, $attemptRows);
usort($allResults, function($a, $b) {
    return strcmp((string)($b['result_date'] ?? ''), (string)($a['result_date'] ?? ''));
});

/* Summary statistics */
$assignmentResults = 0;
$quizResults = 0;
$examResults = 0;
$totalGradedAssessments = 0;
$percentages = [];
foreach ($allResults as $row) {
    $isGraded = $row['score'] !== null;
    if ($row['result_type'] === 'Assignment' && $isGraded) $assignmentResults++;
    if ($row['result_type'] === 'Quiz' && $isGraded) $quizResults++;
    if ($row['result_type'] === 'Exam' && $isGraded) $examResults++;
    if ($isGraded) {
        $totalGradedAssessments++;
        if ((float)$row['total_marks'] > 0) {
            $percentages[] = ((float)$row['score'] / (float)$row['total_marks']) * 100;
        }
    }
}
$averagePercentage = count($percentages) ? round(array_sum($percentages) / count($percentages), 1) : 0;

/* Server-side filters */
$filterCourse = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$filterType = $_GET['type'] ?? '';
$search = trim($_GET['q'] ?? '');
$allowedTypes = ['Assignment','Quiz','Exam'];
if (!in_array($filterType, $allowedTypes, true)) $filterType = '';

$filteredResults = array_values(array_filter($allResults, function($row) use ($filterCourse, $filterType, $search) {
    if ($filterCourse > 0 && (int)$row['course_id'] !== $filterCourse) return false;
    if ($filterType !== '' && $row['result_type'] !== $filterType) return false;
    if ($search !== '') {
        $haystack = strtolower($row['title'] . ' ' . $row['course_name'] . ' ' . $row['batch_name']);
        if (strpos($haystack, strtolower($search)) === false) return false;
    }
    return true;
}));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($projectName) ?> | Grades</title>
<link rel="stylesheet" href="../css/student.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="dashboard-shell">
<aside class="sidebar">
    <div class="brand-panel"><div class="brand-icon"><i class="fas fa-graduation-cap"></i></div><div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Student Portal</p></div></div>
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
        <a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a>
        <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a>
        <a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i>Materials</a>
        <a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i>Assignments</a>
        <a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i>Quizzes</a>
        <a href="exams.php" class="menu-link"><i class="fas fa-clipboard-list"></i>Exams</a>
        <a href="grades.php" class="menu-link active"><i class="fas fa-chart-column"></i>Grades</a>
        <a href="progress.php" class="menu-link"><i class="fas fa-chart-line"></i>Progress</a>
        <a href="attendance.php" class="menu-link"><i class="fas fa-calendar-check"></i>Attendance</a>
        <a href="schedule.php" class="menu-link"><i class="fas fa-calendar-days"></i>Schedule</a>
        <a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i>Announcements</a>
        <a href="discussions.php" class="menu-link"><i class="fas fa-comments"></i>Discussions</a>
        <a href="tute_store.php" class="menu-link"><i class="fas fa-store"></i>Tute Store</a>
        <a href="payments.php" class="menu-link"><i class="fas fa-credit-card"></i>Payments</a>
        <a href="notifications.php" class="menu-link"><i class="fas fa-bell"></i>Notifications</a>
        <a href="../auth/logout.php" class="menu-link logout-link"><i class="fas fa-sign-out-alt"></i>Logout</a>
    </nav>
</aside>
<div class="content-area">
<header class="topbar">
    <div class="topbar-left"><button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Academic Performance</p><h1>Grades & Feedback</h1></div></div>
    <div class="topbar-right"><div class="project-pill">LearnFlow</div><a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?= e($studentName) ?></strong></div></div></div>
</header>
<main class="dashboard-main">
<section class="overview-cards">
    <article class="summary-card"><div><p class="card-label">Academic Results</p><h2>Review your grades and teacher feedback.</h2></div><div class="summary-icon"><i class="fas fa-chart-column"></i></div></article>
    <article class="stat-card"><div><p class="card-label">Graded Results</p><h3><?= $totalGradedAssessments ?></h3></div><span class="stat-badge">Results available</span></article>
    <article class="stat-card"><div><p class="card-label">Average</p><h3><?= e($averagePercentage) ?>%</h3></div><span class="stat-badge">Across graded records</span></article>
</section>

<section class="grade-section">
<div class="grade-section-header"><div><p class="card-label">Assessment Results</p><h2>Grade Categories</h2><p>Assignment, quiz and examination results from the database.</p></div></div>
<div class="grade-category-grid">
    <div class="grade-category-card"><div class="grade-category-icon"><i class="fas fa-file-alt"></i></div><div class="grade-category-content"><span>Assignments</span><strong><?= $assignmentResults ?></strong><p>Graded assignments</p></div></div>
    <div class="grade-category-card"><div class="grade-category-icon"><i class="fas fa-circle-question"></i></div><div class="grade-category-content"><span>Quizzes</span><strong><?= $quizResults ?></strong><p>Graded quiz attempts</p></div></div>
    <div class="grade-category-card"><div class="grade-category-icon"><i class="fas fa-clipboard-list"></i></div><div class="grade-category-content"><span>Exams</span><strong><?= $examResults ?></strong><p>Graded exam attempts</p></div></div>
</div>
</section>

<section class="grade-section">
<div class="grade-section-header"><div><p class="card-label">Results</p><h2>View Grades</h2><p>Filter results by course, assessment type or title.</p></div></div>
<form method="GET" class="grade-filter-row">
    <div class="grade-filter-group"><label for="gradeCourseFilter">Course</label><select id="gradeCourseFilter" name="course"><option value="0">All Courses</option><?php foreach ($courseOptions as $c): ?><option value="<?= (int)$c['course_id'] ?>" <?= $filterCourse === (int)$c['course_id'] ? 'selected' : '' ?>><?= e($c['course_name']) ?></option><?php endforeach; ?></select></div>
    <div class="grade-filter-group"><label for="assessmentFilter">Assessment Type</label><select id="assessmentFilter" name="type"><option value="">All Assessments</option><?php foreach ($allowedTypes as $t): ?><option value="<?= e($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?></select></div>
    <div class="grade-filter-group grade-search-group"><label for="gradeSearch">Search</label><div class="grade-search-box"><i class="fas fa-search"></i><input type="text" id="gradeSearch" name="q" value="<?= e($search) ?>" placeholder="Search results"></div></div>
    <div class="grade-filter-group" style="justify-content:end;"><label>&nbsp;</label><button class="primary-btn" type="submit"><i class="fas fa-filter"></i> Apply</button></div>
</form>
</section>

<section class="grade-section">
<div class="grade-section-header"><div><p class="card-label">Grade Records</p><h2>Results & Feedback</h2><p><?= count($filteredResults) ?> result record(s) shown.</p></div></div>
<?php if (empty($filteredResults)): ?>
<div class="grade-empty-state"><div class="grade-empty-icon"><i class="fas fa-chart-column"></i></div><h3>No Results Available</h3><p>Graded assignments, quizzes and exams will appear here.</p></div>
<?php else: ?>
<div class="attendance-table-wrapper">
<table class="attendance-table">
<thead><tr><th>Assessment</th><th>Course</th><th>Type</th><th>Attempt</th><th>Score</th><th>Percentage</th><th>Status / Grade</th><th>Feedback</th><th>Date</th></tr></thead>
<tbody>
<?php foreach ($filteredResults as $r): ?>
<tr>
<td><strong><?= e($r['title']) ?></strong><br><small><?= e($r['batch_name']) ?></small></td>
<td><?= e($r['course_name']) ?></td>
<td><?= e($r['result_type']) ?></td>
<td><?= $r['attempt_number'] !== null ? '#' . (int)$r['attempt_number'] : '-' ?></td>
<td><?= $r['score'] !== null ? e(number_format((float)$r['score'],2)) . ' / ' . e(number_format((float)$r['total_marks'],2)) : 'Pending' ?></td>
<td><?= e(pct($r['score'], $r['total_marks'])) ?></td>
<td><?= e($r['grade_text'] ?: $r['result_status']) ?></td>
<td><?= e($r['feedback'] ?: 'No feedback yet') ?></td>
<td><?= $r['result_date'] ? e(date('Y-m-d H:i', strtotime($r['result_date']))) : '-' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
</main>
</div>
</div>
<script src="../js/student.js"></script>
</body>
</html>
