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

function formatDateTimeValue(?string $value): string
{
    if (!$value) {
        return 'Not specified';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y, h:i A', $timestamp) : $value;
}

$courseFilter = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$statusFilter = trim($_GET['status'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$allowedStatuses = ['all', 'upcoming', 'available', 'in_progress', 'completed', 'closed'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'all';
}

/* --------------------------------------------------------------------------
   Course filter options: only courses the student belongs to.
   -------------------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT DISTINCT
        c.course_id,
        c.course_name
    FROM enrollments e
    INNER JOIN batches b ON b.batch_id = e.batch_id
    INNER JOIN courses c ON c.course_id = b.course_id
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
   Load quizzes from enrolled batches plus this student's attempt summary.
   -------------------------------------------------------------------------- */
$sql = "
    SELECT
        a.assessment_id AS quiz_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status AS assessment_status,

        q.duration_minutes,
        q.attempt_limit,
        q.passing_marks,
        q.randomize_questions,

        b.batch_name,
        c.course_id,
        c.course_name,
        ua.fullname AS teacher_name,
        e.enrollment_status,

        COUNT(DISTINCT qq.question_id) AS question_count,

        COALESCE(ast.attempts_used, 0) AS attempts_used,
        COALESCE(ast.completed_attempts, 0) AS completed_attempts,
        ast.best_score,
        ast.latest_score,
        ast.latest_grade,
        ast.in_progress_attempt_id

    FROM assessments a
    INNER JOIN quizzes q
        ON q.quiz_id = a.assessment_id
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
    LEFT JOIN quiz_questions qq
        ON qq.quiz_id = a.assessment_id
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
        a.assessment_type = 'quiz'
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
    $sql .= " AND (a.title LIKE ? OR c.course_name LIKE ? OR b.batch_name LIKE ?) ";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
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
        q.duration_minutes,
        q.attempt_limit,
        q.passing_marks,
        q.randomize_questions,
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
        CASE WHEN a.status = 'published' THEN 0 ELSE 1 END,
        a.open_date DESC,
        a.assessment_id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$quizRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$now = time();
$quizzes = [];
$totalQuizzes = 0;
$upcomingQuizzes = 0;
$availableQuizzes = 0;
$completedQuizzes = 0;
$inProgressQuizzes = 0;

foreach ($quizRows as $quiz) {
    $openTimestamp = $quiz['open_date'] ? strtotime($quiz['open_date']) : null;
    $closeTimestamp = $quiz['close_date'] ? strtotime($quiz['close_date']) : null;

    $beforeOpen = $openTimestamp !== null && $now < $openTimestamp;
    $afterClose = $closeTimestamp !== null && $now > $closeTimestamp;
    $isPublished = $quiz['assessment_status'] === 'published';
    $isEnrollmentActive = $quiz['enrollment_status'] === 'Active';
    $hasInProgress = !empty($quiz['in_progress_attempt_id']);
    $attemptsUsed = (int) $quiz['attempts_used'];
    $attemptLimit = (int) $quiz['attempt_limit'];
    $completedAttempts = (int) $quiz['completed_attempts'];

    /*
    |--------------------------------------------------------------------------
    | STUDENT-FACING QUIZ STATUS
    |--------------------------------------------------------------------------
    | Completed enrollments may still view historical quiz records, but only an
    | actively enrolled Student may see a quiz as upcoming/available/in-progress.
    | Old in-progress attempts are not shown as active after the quiz closes.
    */
    if (
        $hasInProgress &&
        $isEnrollmentActive &&
        $isPublished &&
        !$beforeOpen &&
        !$afterClose
    ) {
        $displayStatus = 'in_progress';
    } elseif ($completedAttempts > 0 && $attemptsUsed >= $attemptLimit) {
        $displayStatus = 'completed';
    } elseif (
        $isEnrollmentActive &&
        $isPublished &&
        $beforeOpen
    ) {
        $displayStatus = 'upcoming';
    } elseif (
        $isEnrollmentActive &&
        $isPublished &&
        !$beforeOpen &&
        !$afterClose &&
        $attemptsUsed < $attemptLimit
    ) {
        $displayStatus = 'available';
    } elseif ($completedAttempts > 0) {
        $displayStatus = 'completed';
    } else {
        $displayStatus = 'closed';
    }

    $quiz['display_status'] = $displayStatus;
    $quiz['remaining_attempts'] = max(0, $attemptLimit - $attemptsUsed);

    if ($statusFilter !== 'all' && $displayStatus !== $statusFilter) {
        continue;
    }

    $quizzes[] = $quiz;
    $totalQuizzes++;

    if ($displayStatus === 'upcoming') {
        $upcomingQuizzes++;
    }
    if ($displayStatus === 'available') {
        $availableQuizzes++;
    }
    if ($displayStatus === 'completed') {
        $completedQuizzes++;
    }
    if ($displayStatus === 'in_progress') {
        $inProgressQuizzes++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | Quizzes</title>
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
            <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a>
            <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a>
            <a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i>Materials</a>
            <a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i>Assignments</a>
            <a href="quizzes.php" class="menu-link active"><i class="fas fa-check-square"></i>Quizzes</a>
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
                <div class="dashboard-title">
                    <p class="small-label">Quizzes</p>
                    <h1>My Quizzes</h1>
                </div>
            </div>
            <div class="topbar-right">
                <div class="project-pill">LearnFlow</div>
                <a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a>
                <div class="profile-chip">
                    <div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div>
                    <div><span>Hello,</span><strong><?php echo e($studentName); ?></strong></div>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <section class="overview-cards">
                <article class="summary-card">
                    <div>
                        <p class="card-label">Online Assessments</p>
                        <h2>Attempt quizzes from your enrolled batches.</h2>
                    </div>
                    <div class="summary-icon"><i class="fas fa-check-square"></i></div>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">Available</p><h3><?php echo $availableQuizzes; ?></h3></div>
                    <span class="stat-badge">Ready to attempt</span>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">In Progress</p><h3><?php echo $inProgressQuizzes; ?></h3></div>
                    <span class="stat-badge">Resume attempts</span>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">Completed</p><h3><?php echo $completedQuizzes; ?></h3></div>
                    <span class="stat-badge">Submitted / graded</span>
                </article>
            </section>

            <section class="course-detail-section">
                <div class="course-detail-header">
                    <div>
                        <p class="card-label">Filter</p>
                        <h2>Find a Quiz</h2>
                    </div>
                </div>

                <form method="GET" action="quizzes.php" class="profile-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="course_id">Course</label>
                            <div class="input-wrapper">
                                <i class="fas fa-book"></i>
                                <select id="course_id" name="course_id" style="width:100%;padding:13px 14px 13px 44px;border:1px solid #dfe4ea;border-radius:14px;background:#fff;">
                                    <option value="0">All Courses</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo (int) $course['course_id']; ?>" <?php echo $courseFilter === (int) $course['course_id'] ? 'selected' : ''; ?>>
                                            <?php echo e($course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">Status</label>
                            <div class="input-wrapper">
                                <i class="fas fa-filter"></i>
                                <select id="status" name="status" style="width:100%;padding:13px 14px 13px 44px;border:1px solid #dfe4ea;border-radius:14px;background:#fff;">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="upcoming" <?php echo $statusFilter === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                                    <option value="available" <?php echo $statusFilter === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="search">Search</label>
                            <div class="input-wrapper">
                                <i class="fas fa-search"></i>
                                <input type="text" id="search" name="search" value="<?php echo e($search); ?>" placeholder="Quiz, course or batch">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="quizzes.php" class="secondary-btn"><i class="fas fa-rotate-left"></i>Clear</a>
                        <button type="submit" class="primary-btn"><i class="fas fa-filter"></i>Apply Filters</button>
                    </div>
                </form>
            </section>

            <section class="course-detail-section" style="margin-top:24px;">
                <div class="course-detail-header">
                    <div>
                        <p class="card-label">Quiz List</p>
                        <h2>Quizzes</h2>
                        <p>Only quizzes from your enrolled batches are shown.</p>
                    </div>
                </div>

                <?php if (empty($quizzes)): ?>
                    <div class="course-empty-state">
                        <div class="course-empty-icon"><i class="fas fa-check-square"></i></div>
                        <h3>No Quizzes Found</h3>
                        <p>No quizzes match the current filters.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($quizzes as $quiz): ?>
                            <?php
                            $statusLabels = [
                                'upcoming' => 'Upcoming',
                                'available' => 'Available',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'closed' => 'Closed'
                            ];
                            $label = $statusLabels[$quiz['display_status']] ?? 'Quiz';
                            ?>
                            <article class="dashboard-card">
                                <div class="card-icon bg-purple"><i class="fas fa-list-check"></i></div>
                                <p class="card-label"><?php echo e($quiz['course_name']); ?> · <?php echo e($quiz['batch_name']); ?></p>
                                <h3><?php echo e($quiz['title']); ?></h3>
                                <p><?php echo e($quiz['description'] ?: 'No description provided.'); ?></p>
                                <p><strong>Teacher:</strong> <?php echo e($quiz['teacher_name'] ?: 'Not assigned'); ?></p>
                                <p><strong>Questions:</strong> <?php echo (int) $quiz['question_count']; ?></p>
                                <p><strong>Marks:</strong> <?php echo e($quiz['total_marks']); ?> · <strong>Pass:</strong> <?php echo e($quiz['passing_marks']); ?></p>
                                <p><strong>Duration:</strong> <?php echo (int) $quiz['duration_minutes']; ?> minutes</p>
                                <p><strong>Attempts:</strong> <?php echo (int) $quiz['attempts_used']; ?> / <?php echo (int) $quiz['attempt_limit']; ?></p>
                                <p><strong>Opens:</strong> <?php echo e(formatDateTimeValue($quiz['open_date'])); ?></p>
                                <p><strong>Closes:</strong> <?php echo e(formatDateTimeValue($quiz['close_date'])); ?></p>
                                <p><strong>Status:</strong> <?php echo e($label); ?></p>

                                <?php if ($quiz['best_score'] !== null): ?>
                                    <p><strong>Best Score:</strong> <?php echo e($quiz['best_score']); ?> / <?php echo e($quiz['total_marks']); ?></p>
                                <?php endif; ?>

                                <div class="form-actions" style="justify-content:flex-start;flex-wrap:wrap;">
                                    <?php if ($quiz['display_status'] === 'in_progress'): ?>
                                        <a class="primary-btn" href="quiz_attempt.php?id=<?php echo (int) $quiz['quiz_id']; ?>">
                                            <i class="fas fa-play"></i>Resume Quiz
                                        </a>
                                    <?php elseif ($quiz['display_status'] === 'available'): ?>
                                        <a class="primary-btn" href="quiz_attempt.php?id=<?php echo (int) $quiz['quiz_id']; ?>">
                                            <i class="fas fa-play"></i><?php echo (int) $quiz['completed_attempts'] > 0 ? 'Attempt Again' : 'Open Quiz'; ?>
                                        </a>
                                    <?php else: ?>
                                        <a class="secondary-btn" href="quiz_attempt.php?id=<?php echo (int) $quiz['quiz_id']; ?>">
                                            <i class="fas fa-eye"></i>View Details
                                        </a>
                                    <?php endif; ?>
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
