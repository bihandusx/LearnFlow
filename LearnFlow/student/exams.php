<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function formatExamDate(?string $date): string
{
    if (!$date) {
        return 'Not specified';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('d M Y', $timestamp) : $date;
}

function formatExamTime(?string $time): string
{
    if (!$time) {
        return 'Not specified';
    }

    $timestamp = strtotime($time);
    return $timestamp ? date('h:i A', $timestamp) : $time;
}

function formatDateTimeValue(?string $value): string
{
    if (!$value) {
        return 'Not specified';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : $value;
}

/* --------------------------------------------------------------------------
   Filters
   -------------------------------------------------------------------------- */
$courseFilter = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$statusFilter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$allowedStatuses = ['all', 'upcoming', 'available', 'in_progress', 'completed', 'closed'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

/* --------------------------------------------------------------------------
   Courses the student belongs to.
   -------------------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT DISTINCT
        c.course_id,
        c.course_name
    FROM enrollments e
    INNER JOIN batches b
        ON b.batch_id = e.batch_id
    INNER JOIN courses c
        ON c.course_id = b.course_id
    WHERE
        e.student_id = ?
        AND e.enrollment_status IN ('Active', 'Completed')
    ORDER BY c.course_name
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* --------------------------------------------------------------------------
   Load exams belonging to enrolled batches.
   -------------------------------------------------------------------------- */
$sql = "
    SELECT
        a.assessment_id AS exam_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status AS assessment_status,

        ex.exam_date,
        ex.start_time,
        ex.end_time,
        ex.venue,
        ex.duration_minutes,
        ex.attempt_limit,
        ex.passing_marks,

        b.batch_name,
        c.course_id,
        c.course_name,
        ua.fullname AS teacher_name,
        e.enrollment_status,

        COUNT(DISTINCT eq.question_id) AS question_count,

        COALESCE(ast.attempts_used, 0) AS attempts_used,
        COALESCE(ast.completed_attempts, 0) AS completed_attempts,
        ast.best_score,
        ast.latest_score,
        ast.latest_grade,
        ast.in_progress_attempt_id

    FROM assessments a

    INNER JOIN exams ex
        ON ex.exam_id = a.assessment_id

    INNER JOIN batches b
        ON b.batch_id = a.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    INNER JOIN enrollments e
        ON e.batch_id = a.batch_id
        AND e.student_id = ?
        AND e.enrollment_status IN ('Active', 'Completed')

    LEFT JOIN user_accounts ua
        ON ua.user_id = a.teacher_id

    LEFT JOIN exam_questions eq
        ON eq.exam_id = a.assessment_id

    LEFT JOIN (
        SELECT
            aa.assessment_id,
            COUNT(*) AS attempts_used,
            SUM(aa.attempt_status IN ('submitted', 'graded')) AS completed_attempts,
            MAX(CASE WHEN aa.attempt_status = 'graded' THEN aa.score END) AS best_score,
            SUBSTRING_INDEX(
                GROUP_CONCAT(
                    IFNULL(aa.score, '')
                    ORDER BY aa.attempt_number DESC
                    SEPARATOR ','
                ),
                ',',
                1
            ) AS latest_score,
            SUBSTRING_INDEX(
                GROUP_CONCAT(
                    IFNULL(aa.grade, '')
                    ORDER BY aa.attempt_number DESC
                    SEPARATOR ','
                ),
                ',',
                1
            ) AS latest_grade,
            MAX(
                CASE
                    WHEN aa.attempt_status = 'in_progress'
                    THEN aa.attempt_id
                END
            ) AS in_progress_attempt_id
        FROM assessment_attempts aa
        WHERE aa.student_id = ?
        GROUP BY aa.assessment_id
    ) ast
        ON ast.assessment_id = a.assessment_id

    WHERE
        a.assessment_type = 'exam'
        AND a.status IN ('published', 'closed')
";

$params = [$studentId, $studentId];
$types = "ii";

if ($courseFilter > 0) {
    $sql .= " AND c.course_id = ? ";
    $params[] = $courseFilter;
    $types .= "i";
}

if ($search !== '') {
    $sql .= " AND (a.title LIKE ? OR c.course_name LIKE ? OR b.batch_name LIKE ? OR ex.venue LIKE ?) ";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql .= "
    GROUP BY
        a.assessment_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status,
        ex.exam_date,
        ex.start_time,
        ex.end_time,
        ex.venue,
        ex.duration_minutes,
        ex.attempt_limit,
        ex.passing_marks,
        b.batch_name,
        c.course_id,
        c.course_name,
        ua.fullname,
        e.enrollment_status,
        ast.attempts_used,
        ast.completed_attempts,
        ast.best_score,
        ast.latest_score,
        ast.latest_grade,
        ast.in_progress_attempt_id
    ORDER BY
        ex.exam_date ASC,
        ex.start_time ASC,
        a.assessment_id ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$examRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* --------------------------------------------------------------------------
   Determine Student-facing exam status.
   Exam date/time is the primary examination window. Assessment open/close
   dates further restrict access when they are supplied.
   -------------------------------------------------------------------------- */
$now = time();
$exams = [];

$totalExams = 0;
$upcomingExams = 0;
$availableExams = 0;
$inProgressExams = 0;
$completedExams = 0;
$closedExams = 0;

foreach ($examRows as $exam) {
    $examStart = strtotime($exam['exam_date'] . ' ' . $exam['start_time']);
    $examEnd = strtotime($exam['exam_date'] . ' ' . $exam['end_time']);

    if ($exam['open_date']) {
        $assessmentOpen = strtotime($exam['open_date']);
        if ($assessmentOpen && $assessmentOpen > $examStart) {
            $examStart = $assessmentOpen;
        }
    }

    if ($exam['close_date']) {
        $assessmentClose = strtotime($exam['close_date']);
        if ($assessmentClose && $assessmentClose < $examEnd) {
            $examEnd = $assessmentClose;
        }
    }

    $isPublished = $exam['assessment_status'] === 'published';
    $isEnrollmentActive = $exam['enrollment_status'] === 'Active';
    $hasInProgress = !empty($exam['in_progress_attempt_id']);
    $attemptsUsed = (int) $exam['attempts_used'];
    $attemptLimit = (int) $exam['attempt_limit'];
    $completedAttempts = (int) $exam['completed_attempts'];

    /*
    |--------------------------------------------------------------------------
    | STUDENT-FACING EXAM STATUS
    |--------------------------------------------------------------------------
    | Completed enrollments may still view historical exam records, but only an
    | actively enrolled Student may see an exam as upcoming/available/in-progress.
    | An old in-progress attempt is not shown as active after the exam window ends.
    */
    if (
        $hasInProgress &&
        $isEnrollmentActive &&
        $isPublished &&
        $now >= $examStart &&
        $now <= $examEnd
    ) {
        $displayStatus = 'in_progress';
    } elseif ($completedAttempts > 0 && $attemptsUsed >= $attemptLimit) {
        $displayStatus = 'completed';
    } elseif (
        $isEnrollmentActive &&
        $isPublished &&
        $now < $examStart
    ) {
        $displayStatus = 'upcoming';
    } elseif (
        $isEnrollmentActive &&
        $isPublished &&
        $now >= $examStart &&
        $now <= $examEnd &&
        $attemptsUsed < $attemptLimit
    ) {
        $displayStatus = 'available';
    } elseif ($completedAttempts > 0) {
        $displayStatus = 'completed';
    } else {
        $displayStatus = 'closed';
    }

    $exam['display_status'] = $displayStatus;
    $exam['remaining_attempts'] = max(0, $attemptLimit - $attemptsUsed);
    $exam['effective_start_timestamp'] = $examStart;
    $exam['effective_end_timestamp'] = $examEnd;

    if ($statusFilter !== 'all' && $displayStatus !== $statusFilter) {
        continue;
    }

    $exams[] = $exam;
    $totalExams++;

    if ($displayStatus === 'upcoming') {
        $upcomingExams++;
    } elseif ($displayStatus === 'available') {
        $availableExams++;
    } elseif ($displayStatus === 'in_progress') {
        $inProgressExams++;
    } elseif ($displayStatus === 'completed') {
        $completedExams++;
    } else {
        $closedExams++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | Exams</title>
    <link rel="stylesheet" href="../css/student.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous"
          referrerpolicy="no-referrer">
</head>
<body>
<div class="dashboard-shell">

    <aside class="sidebar">
        <div class="brand-panel">
            <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
            <div>
                <p class="brand-label">LearnFlow</p>
                <p class="brand-subtitle">Student Portal</p>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="profile.php" class="menu-link"><i class="fas fa-user"></i> Profile</a>
            <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i> Courses</a>
            <a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i> Materials</a>
            <a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i> Assignments</a>
            <a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i> Quizzes</a>
            <a href="exams.php" class="menu-link active"><i class="fas fa-clipboard-list"></i> Exams</a>
            <a href="grades.php" class="menu-link"><i class="fas fa-chart-column"></i> Grades</a>
            <a href="progress.php" class="menu-link"><i class="fas fa-chart-line"></i> Progress</a>
            <a href="attendance.php" class="menu-link"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="schedule.php" class="menu-link"><i class="fas fa-calendar-days"></i> Schedule</a>
            <a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="discussions.php" class="menu-link"><i class="fas fa-comments"></i> Discussions</a>
            <a href="tute_store.php" class="menu-link"><i class="fas fa-store"></i> Tute Store</a>
            <a href="payments.php" class="menu-link"><i class="fas fa-credit-card"></i> Payments</a>
            <a href="notifications.php" class="menu-link"><i class="fas fa-bell"></i> Notifications</a>
            <a href="../auth/logout.php" class="menu-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </aside>

    <div class="content-area">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button>
                <div class="dashboard-title">
                    <p class="small-label">Exams</p>
                    <h1>My Exams</h1>
                </div>
            </div>

            <div class="topbar-right">
                <div class="project-pill">LearnFlow</div>
                <a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a>
                <div class="profile-chip">
                    <div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div>
                    <div><span>Hello,</span> <strong><?php echo e($studentName); ?></strong></div>
                </div>
            </div>
        </header>

        <main class="dashboard-main">

            <section class="overview-cards">
                <article class="summary-card">
                    <div>
                        <p class="card-label">Examinations</p>
                        <h2>View your real exam schedule and attempt online exams only during the allowed examination window.</h2>
                    </div>
                    <div class="summary-icon"><i class="fas fa-clipboard-list"></i></div>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">Upcoming</p><h3><?php echo $upcomingExams; ?></h3></div>
                    <span class="stat-badge">Prepare now</span>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">Available Now</p><h3><?php echo $availableExams + $inProgressExams; ?></h3></div>
                    <span class="stat-badge">Exam window open</span>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">Completed</p><h3><?php echo $completedExams; ?></h3></div>
                    <span class="stat-badge">Submitted exams</span>
                </article>
            </section>

            <section class="exam-section">
                <div class="exam-section-header">
                    <div>
                        <p class="card-label">Examination Schedule</p>
                        <h2>Find Exams</h2>
                        <p>Filter exams by enrolled course, current status, title, batch or venue.</p>
                    </div>
                </div>

                <form method="GET" action="exams.php" class="exam-filter-row">
                    <div class="exam-filter-group">
                        <label for="courseFilter">Course</label>
                        <select id="courseFilter" name="course_id">
                            <option value="0">All Courses</option>
                            <?php foreach ($courses as $courseOption): ?>
                                <option value="<?php echo (int) $courseOption['course_id']; ?>"
                                    <?php echo $courseFilter === (int) $courseOption['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($courseOption['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="exam-filter-group">
                        <label for="examStatus">Status</label>
                        <select id="examStatus" name="status">
                            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="upcoming" <?php echo $statusFilter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <div class="exam-filter-group exam-search-group">
                        <label for="examSearch">Search</label>
                        <div class="exam-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="examSearch" name="search" value="<?php echo e($search); ?>" placeholder="Search exams">
                        </div>
                    </div>

                    <div class="form-actions" style="align-items:flex-end;">
                        <button type="submit" class="primary-btn"><i class="fas fa-filter"></i> Apply</button>
                        <a href="exams.php" class="secondary-btn"><i class="fas fa-rotate-left"></i> Clear</a>
                    </div>
                </form>
            </section>

            <section class="exam-section">
                <div class="exam-section-header">
                    <div>
                        <p class="card-label">Exams</p>
                        <h2>Examination List</h2>
                        <p><?php echo $totalExams; ?> exam(s) match the current filter.</p>
                    </div>
                </div>

                <?php if (empty($exams)): ?>
                    <div class="exam-empty-state">
                        <div class="exam-empty-icon"><i class="fas fa-clipboard-list"></i></div>
                        <h3>No Exams Available</h3>
                        <p>Published examinations for your enrolled batches will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($exams as $exam): ?>
                            <?php
                            $statusLabels = [
                                'upcoming' => 'Upcoming',
                                'available' => 'Available Now',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'closed' => 'Closed'
                            ];
                            $displayStatus = $exam['display_status'];
                            ?>
                            <article class="dashboard-card">
                                <div class="card-icon bg-purple"><i class="fas fa-clipboard-list"></i></div>
                                <p class="card-label"><?php echo e($exam['course_name']); ?></p>
                                <h3><?php echo e($exam['title']); ?></h3>

                                <p><strong>Batch:</strong> <?php echo e($exam['batch_name']); ?></p>
                                <p><strong>Teacher:</strong> <?php echo e($exam['teacher_name'] ?: 'Not assigned'); ?></p>
                                <p><strong>Date:</strong> <?php echo e(formatExamDate($exam['exam_date'])); ?></p>
                                <p><strong>Time:</strong> <?php echo e(formatExamTime($exam['start_time'])); ?> - <?php echo e(formatExamTime($exam['end_time'])); ?></p>
                                <p><strong>Venue:</strong> <?php echo e($exam['venue'] ?: 'Online / Not specified'); ?></p>
                                <p><strong>Questions:</strong> <?php echo (int) $exam['question_count']; ?></p>
                                <p><strong>Total Marks:</strong> <?php echo e($exam['total_marks']); ?></p>
                                <p><strong>Pass Mark:</strong> <?php echo e($exam['passing_marks']); ?></p>
                                <p><strong>Status:</strong> <?php echo e($statusLabels[$displayStatus]); ?></p>

                                <?php if ((int) $exam['attempt_limit'] > 1): ?>
                                    <p>
                                        <strong>Attempts:</strong>
                                        <?php echo (int) $exam['attempts_used']; ?> / <?php echo (int) $exam['attempt_limit']; ?>
                                    </p>
                                <?php endif; ?>

                                <?php if ($exam['latest_score'] !== null && $exam['latest_score'] !== ''): ?>
                                    <p><strong>Latest Score:</strong> <?php echo e($exam['latest_score']); ?> / <?php echo e($exam['total_marks']); ?></p>
                                <?php endif; ?>

                                <?php if ($exam['latest_grade']): ?>
                                    <p><strong>Latest Result:</strong> <?php echo e($exam['latest_grade']); ?></p>
                                <?php endif; ?>

                                <div class="form-actions" style="justify-content:flex-start; flex-wrap:wrap; margin-top:16px;">
                                    <a href="exam_attempt.php?id=<?php echo (int) $exam['exam_id']; ?>" class="primary-btn">
                                        <i class="fas <?php echo $displayStatus === 'in_progress' ? 'fa-play' : 'fa-eye'; ?>"></i>
                                        <?php
                                        if ($displayStatus === 'in_progress') {
                                            echo 'Continue Exam';
                                        } elseif ($displayStatus === 'available') {
                                            echo 'Open Exam';
                                        } elseif ($displayStatus === 'completed') {
                                            echo 'View Result';
                                        } else {
                                            echo 'View Details';
                                        }
                                        ?>
                                    </a>

                                    <a href="course_details.php?id=<?php echo (int) $exam['course_id']; ?>&batch_id=<?php echo (int) $exam['batch_id']; ?>" class="secondary-btn">
                                        <i class="fas fa-book-open"></i> Course
                                    </a>
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
