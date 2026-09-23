<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$projectName = 'LearnFlow';
$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function assignmentDisplayStatus(array $row): string
{
    /*
    |--------------------------------------------------------------------------
    | Existing submission
    |--------------------------------------------------------------------------
    */

    if (!empty($row['submission_id'])) {

        if ($row['submission_status'] === 'Graded') {
            return 'Graded';
        }

        if ($row['submission_status'] === 'Late') {
            return 'Late';
        }

        return 'Submitted';
    }

    $now = new DateTime();

    /*
    |--------------------------------------------------------------------------
    | Assignment has not opened yet
    |--------------------------------------------------------------------------
    */

    if (
        !empty($row['open_date']) &&
        $now < new DateTime($row['open_date'])
    ) {
        return 'Upcoming';
    }

    /*
    |--------------------------------------------------------------------------
    | Assessment manually closed
    |--------------------------------------------------------------------------
    */

    if ($row['assessment_status'] === 'closed') {
        return 'Closed';
    }

    /*
    |--------------------------------------------------------------------------
    | Assessment close date has passed
    |--------------------------------------------------------------------------
    */

    if (
        !empty($row['close_date']) &&
        $now > new DateTime($row['close_date'])
    ) {
        return 'Closed';
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment due date passed and late submissions are disabled
    |--------------------------------------------------------------------------
    */

    if (
        !empty($row['due_date']) &&
        $now > new DateTime($row['due_date']) &&
        !(bool) $row['allow_late_submission']
    ) {
        return 'Closed';
    }

    return 'Pending';
}

/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$courseFilter = isset($_GET['course_id'])
    ? (int) $_GET['course_id']
    : 0;

$batchFilter = isset($_GET['batch_id'])
    ? (int) $_GET['batch_id']
    : 0;

$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

/*
|--------------------------------------------------------------------------
| LOAD STUDENT'S COURSES
|--------------------------------------------------------------------------
*/

$courseStmt = $conn->prepare("
    SELECT DISTINCT
        c.course_id,
        c.course_name

    FROM enrollments e

    INNER JOIN batches b
        ON b.batch_id = e.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    WHERE e.student_id = ?
      AND e.enrollment_status IN ('Active', 'Completed')

    ORDER BY c.course_name
");

$courseStmt->bind_param("i", $studentId);
$courseStmt->execute();

$courses = $courseStmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$courseStmt->close();

/*
|--------------------------------------------------------------------------
| LOAD ASSIGNMENTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        a.assessment_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status AS assessment_status,

        ass.due_date,
        ass.instructions,
        ass.submission_type,
        ass.max_file_size_mb,
        ass.allow_late_submission,

        b.batch_name,

        c.course_id,
        c.course_name,

        s.subject_code,

        teacher.fullname AS teacher_name,

        sub.submission_id,
        sub.submission_date,
        sub.submission_status,
        sub.marks,
        sub.feedback

    FROM enrollments e

    INNER JOIN batches b
        ON b.batch_id = e.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    LEFT JOIN subjects s
        ON s.subject_id = c.subject_id

    INNER JOIN assessments a
        ON a.batch_id = b.batch_id

    INNER JOIN assignments ass
        ON ass.assignment_id = a.assessment_id

    INNER JOIN user_accounts teacher
        ON teacher.user_id = a.teacher_id

    LEFT JOIN assignment_submissions sub
        ON sub.assignment_id = a.assessment_id
       AND sub.student_id = e.student_id

    WHERE e.student_id = ?
      AND e.enrollment_status IN ('Active', 'Completed')
      AND a.assessment_type = 'assignment'
      AND a.status IN ('published', 'closed')

    ORDER BY
        CASE
            WHEN sub.submission_id IS NULL THEN 0
            ELSE 1
        END,
        ass.due_date ASC,
        a.assessment_id DESC
");

$stmt->bind_param("i", $studentId);
$stmt->execute();

$rows = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

/*
|--------------------------------------------------------------------------
| BUILD DISPLAY DATA
|--------------------------------------------------------------------------
*/

$assignments = [];

$totalAssignments = 0;
$pendingAssignments = 0;
$submittedAssignments = 0;

foreach ($rows as $row) {

    $row['display_status'] = assignmentDisplayStatus($row);

    $totalAssignments++;

    if (
        in_array(
            $row['display_status'],
            ['Pending', 'Upcoming'],
            true
        )
    ) {
        $pendingAssignments++;
    }

    if (
        in_array(
            $row['display_status'],
            ['Submitted', 'Late', 'Graded'],
            true
        )
    ) {
        $submittedAssignments++;
    }

    /*
    |--------------------------------------------------------------------------
    | Course filter
    |--------------------------------------------------------------------------
    */

    if (
        $courseFilter > 0 &&
        (int) $row['course_id'] !== $courseFilter
    ) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Batch filter
    |--------------------------------------------------------------------------
    */

    if (
        $batchFilter > 0 &&
        (int) $row['batch_id'] !== $batchFilter
    ) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Status filter
    |--------------------------------------------------------------------------
    */

    if (
        $statusFilter !== '' &&
        strcasecmp(
            $row['display_status'],
            $statusFilter
        ) !== 0
    ) {
        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $haystack = strtolower(
            ($row['title'] ?? '') . ' ' .
            ($row['course_name'] ?? '') . ' ' .
            ($row['batch_name'] ?? '') . ' ' .
            ($row['teacher_name'] ?? '')
        );

        if (
            strpos(
                $haystack,
                strtolower($search)
            ) === false
        ) {
            continue;
        }
    }

    $assignments[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($projectName) ?> | Assignments</title>
    <link rel="stylesheet" href="../css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="dashboard-shell">
    <aside class="sidebar">
        <div class="brand-panel">
            <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
            <div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Student Portal</p></div>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a>
            <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a>
            <a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i>Materials</a>
            <a href="assignments.php" class="menu-link active"><i class="fas fa-file-alt"></i>Assignments</a>
            <a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i>Quizzes</a>
            <a href="exams.php" class="menu-link"><i class="fas fa-clipboard-list"></i>Exams</a>
            <a href="grades.php" class="menu-link"><i class="fas fa-chart-column"></i>Grades</a>
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
            <div class="topbar-left">
                <button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button>
                <div class="dashboard-title"><p class="small-label">Assignments</p><h1>My Assignments</h1></div>
            </div>
            <div class="topbar-right">
                <div class="project-pill">LearnFlow</div>
                <a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a>
                <div class="profile-chip">
                    <div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div>
                    <div><span>Hello,</span><strong><?= e($studentName) ?></strong></div>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <section class="overview-cards">
                <article class="summary-card">
                    <div><p class="card-label">Coursework</p><h2>View, submit and track your assignments.</h2></div>
                    <div class="summary-icon"><i class="fas fa-file-alt"></i></div>
                </article>
                <article class="stat-card"><div><p class="card-label">Total</p><h3><?= $totalAssignments ?></h3></div><span class="stat-badge">Published work</span></article>
                <article class="stat-card"><div><p class="card-label">Pending</p><h3><?= $pendingAssignments ?></h3></div><span class="stat-badge">Needs attention</span></article>
                <article class="stat-card"><div><p class="card-label">Submitted</p><h3><?= $submittedAssignments ?></h3></div><span class="stat-badge">Submitted / graded</span></article>
            </section>

            <section class="assignment-section">
                <div class="assignment-section-header">
                    <div><p class="card-label">Filter</p><h2>Assignment List</h2><p>Filter assignments by course, status or search term.</p></div>
                </div>

                <form method="GET" action="assignments.php" class="assignment-filter-row">
                    <div class="assignment-filter-group">
                        <label for="courseFilter">Course</label>
                        <select id="courseFilter" name="course_id">
                            <option value="0">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= (int) $course['course_id'] ?>" <?= $courseFilter === (int) $course['course_id'] ? 'selected' : '' ?>><?= e($course['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="assignment-filter-group">
                        <label for="statusFilter">Status</label>
                        <select id="statusFilter" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach (['Pending', 'Upcoming', 'Submitted', 'Late', 'Graded', 'Closed'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="assignment-filter-group assignment-search-group">
                        <label for="assignmentSearch">Search</label>
                        <div class="assignment-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="assignmentSearch" name="search" value="<?= e($search) ?>" placeholder="Search assignments">
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top:24px;">
                        <button type="submit" class="primary-btn"><i class="fas fa-filter"></i>Apply</button>
                        <a href="assignments.php" class="secondary-btn"><i class="fas fa-rotate-left"></i>Reset</a>
                    </div>
                </form>
            </section>

            <section class="assignment-section">
                <div class="assignment-section-header">
                    <div><p class="card-label">Assignments</p><h2>Available Assignments</h2></div>
                </div>

                <?php if (empty($assignments)): ?>
                    <div class="assignment-empty-state">
                        <div class="assignment-empty-icon"><i class="fas fa-file-circle-check"></i></div>
                        <h3>No Assignments Found</h3>
                        <p>No assignments match the current filters, or your teachers have not published any assignments yet.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($assignments as $assignment): ?>
                            <article class="dashboard-card">
                                <div class="card-icon bg-blue"><i class="fas fa-file-alt"></i></div>
                                <p class="card-label"><?= e($assignment['subject_code'] ?: ('COURSE-' . $assignment['course_id'])) ?></p>
                                <h3><?= e($assignment['title']) ?></h3>
                                <p><strong>Course:</strong> <?= e($assignment['course_name']) ?></p>
                                <p><strong>Batch:</strong> <?= e($assignment['batch_name']) ?></p>
                                <p><strong>Teacher:</strong> <?= e($assignment['teacher_name']) ?></p>
                                <p><strong>Due:</strong> <?= e($assignment['due_date']) ?></p>
                                <p><strong>Marks:</strong> <?= e($assignment['total_marks']) ?></p>
                                <p><strong>Status:</strong> <?= e($assignment['display_status']) ?></p>

                                <?php if ($assignment['display_status'] === 'Graded'): ?>
                                    <p><strong>Your Marks:</strong> <?= e($assignment['marks']) ?> / <?= e($assignment['total_marks']) ?></p>
                                <?php endif; ?>

                                <div class="form-actions" style="justify-content:flex-start;">
                                    <a class="primary-btn" href="assignment_details.php?id=<?= (int) $assignment['assessment_id'] ?>"><i class="fas fa-eye"></i>View Assignment</a>
                                </div>
                            </article>
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
