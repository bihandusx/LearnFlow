<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';

/* =========================================================
   ACCESS CONTROL
   ========================================================= */
if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

/* =========================================================
   HELPERS
   ========================================================= */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function prettyStatus(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/* =========================================================
   FILTERS
   ========================================================= */
$selectedCourse = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$selectedDate = trim($_GET['date'] ?? '');

if ($selectedDate !== '') {
    $dateObject = DateTime::createFromFormat('Y-m-d', $selectedDate);

    if (
        !$dateObject ||
        $dateObject->format('Y-m-d') !== $selectedDate
    ) {
        $selectedDate = '';
    }
}

/* =========================================================
   LOAD STUDENT COURSES FOR FILTER
   ========================================================= */
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
$stmt->bind_param('i', $studentId);
$stmt->execute();
$courseOptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================================================
   LOAD CLASS SESSIONS
   ========================================================= */
$sql = "
    SELECT
        cs.session_id,
        cs.class_date,
        cs.start_time,
        cs.end_time,
        cs.venue,
        cs.topic,
        cs.status,
        b.batch_id,
        b.batch_name,
        c.course_id,
        c.course_name,
        u.fullname AS teacher_name
    FROM class_sessions cs
    INNER JOIN batches b
        ON b.batch_id = cs.batch_id
    INNER JOIN courses c
        ON c.course_id = b.course_id
    INNER JOIN enrollments e
        ON e.batch_id = b.batch_id
        AND e.student_id = ?
        AND (
            e.enrollment_status = 'Active'
            OR (
                e.enrollment_status = 'Completed'
                AND TIMESTAMP(cs.class_date, cs.end_time) <= NOW()
            )
        )
    INNER JOIN user_accounts u
        ON u.user_id = cs.teacher_id
    WHERE
        (? = 0 OR c.course_id = ?)
        AND (? = '' OR cs.class_date = ?)
    ORDER BY
        cs.class_date,
        cs.start_time
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'iiiss',
    $studentId,
    $selectedCourse,
    $selectedCourse,
    $selectedDate,
    $selectedDate
);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================================================
   LOAD EXAMS
   ========================================================= */
$sql = "
    SELECT
        a.assessment_id,
        a.title,
        c.course_id,
        c.course_name,
        b.batch_name,
        ex.exam_date,
        ex.start_time,
        ex.end_time,
        ex.venue,
        a.status
    FROM assessments a
    INNER JOIN exams ex
        ON ex.exam_id = a.assessment_id
    INNER JOIN batches b
        ON b.batch_id = a.batch_id
    INNER JOIN courses c
        ON c.course_id = b.course_id
    INNER JOIN enrollments e
        ON e.batch_id = b.batch_id
        AND e.student_id = ?
        AND (
            e.enrollment_status = 'Active'
            OR (
                e.enrollment_status = 'Completed'
                AND TIMESTAMP(ex.exam_date, ex.end_time) <= NOW()
            )
        )
    WHERE
        a.assessment_type = 'exam'
        AND a.status IN ('published', 'closed')
        AND (? = 0 OR c.course_id = ?)
        AND (? = '' OR ex.exam_date = ?)
    ORDER BY
        ex.exam_date,
        ex.start_time
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'iiiss',
    $studentId,
    $selectedCourse,
    $selectedCourse,
    $selectedDate,
    $selectedDate
);
$stmt->execute();
$exams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* =========================================================
   SUMMARY COUNTS
   ========================================================= */
$today = date('Y-m-d');
$now = date('Y-m-d H:i:s');

$todayClasses = 0;
$upcomingClasses = 0;
$upcomingExams = 0;

foreach ($classes as $class) {
    if (
        $class['class_date'] === $today &&
        $class['status'] !== 'cancelled'
    ) {
        $todayClasses++;
    }

    if (
        ($class['class_date'] . ' ' . $class['start_time']) > $now &&
        $class['status'] === 'scheduled'
    ) {
        $upcomingClasses++;
    }
}

foreach ($exams as $exam) {
    if (
        ($exam['exam_date'] . ' ' . $exam['start_time']) > $now &&
        $exam['status'] === 'published'
    ) {
        $upcomingExams++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | Schedule</title>

    <link rel="stylesheet" href="../css/student.css">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >
</head>
<body>
<div class="dashboard-shell">

    <!-- =====================================================
         SIDEBAR
         ===================================================== -->
    <aside class="sidebar">

        <div class="brand-panel">
            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>

            <div>
                <p class="brand-label">LearnFlow</p>
                <p class="brand-subtitle">Student Portal</p>
            </div>
        </div>

        <nav class="sidebar-menu">

            <a href="dashboard.php" class="menu-link">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>

            <a href="profile.php" class="menu-link">
                <i class="fas fa-user"></i>
                Profile
            </a>

            <a href="courses.php" class="menu-link">
                <i class="fas fa-book-open"></i>
                Courses
            </a>

            <a href="materials.php" class="menu-link">
                <i class="fas fa-folder-open"></i>
                Materials
            </a>

            <a href="assignments.php" class="menu-link">
                <i class="fas fa-file-alt"></i>
                Assignments
            </a>

            <a href="quizzes.php" class="menu-link">
                <i class="fas fa-check-square"></i>
                Quizzes
            </a>

            <a href="exams.php" class="menu-link">
                <i class="fas fa-clipboard-list"></i>
                Exams
            </a>

            <a href="grades.php" class="menu-link">
                <i class="fas fa-chart-column"></i>
                Grades
            </a>

            <a href="progress.php" class="menu-link">
                <i class="fas fa-chart-line"></i>
                Progress
            </a>

            <a href="attendance.php" class="menu-link">
                <i class="fas fa-calendar-check"></i>
                Attendance
            </a>

            <a href="schedule.php" class="menu-link active">
                <i class="fas fa-calendar-days"></i>
                Schedule
            </a>

            <a href="announcements.php" class="menu-link">
                <i class="fas fa-bullhorn"></i>
                Announcements
            </a>

            <a href="discussions.php" class="menu-link">
                <i class="fas fa-comments"></i>
                Discussions
            </a>

            <a href="tute_store.php" class="menu-link">
                <i class="fas fa-store"></i>
                Tute Store
            </a>

            <a href="payments.php" class="menu-link">
                <i class="fas fa-credit-card"></i>
                Payments
            </a>

            <a href="notifications.php" class="menu-link">
                <i class="fas fa-bell"></i>
                Notifications
            </a>

            <a href="../auth/logout.php" class="menu-link logout-link">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>

        </nav>
    </aside>

    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->
    <div class="content-area">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="topbar-left">
                <button class="mobile-menu-btn" type="button">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="dashboard-title">
                    <p class="small-label">Schedule</p>
                    <h1>Class & Exam Schedule</h1>
                </div>
            </div>

            <div class="topbar-right">
                <div class="project-pill">LearnFlow</div>

                <a href="notifications.php" class="icon-btn">
                    <i class="fas fa-bell"></i>
                </a>

                <div class="profile-chip">
                    <div class="avatar-placeholder">
                        <i class="fas fa-user-circle"></i>
                    </div>

                    <div>
                        <span>Hello,</span>
                        <strong><?php echo e($studentName); ?></strong>
                    </div>
                </div>
            </div>

        </header>

        <main class="dashboard-main">

            <!-- =================================================
                 OVERVIEW CARDS
                 ================================================= -->
            <section class="overview-cards">

                <article class="summary-card">
                    <div>
                        <p class="card-label">Schedule</p>
                        <h2>Your class and examination timetable.</h2>
                    </div>

                    <div class="summary-icon">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Today's Classes</p>
                        <h3><?php echo $todayClasses; ?></h3>
                    </div>
                    <span class="stat-badge">classes today</span>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Upcoming</p>
                        <h3><?php echo $upcomingClasses + $upcomingExams; ?></h3>
                    </div>
                    <span class="stat-badge">classes + exams</span>
                </article>

            </section>

            <!-- =================================================
                 FILTERS
                 ================================================= -->
            <section class="schedule-section">

                <div class="schedule-section-header">
                    <div>
                        <p class="card-label">Filters</p>
                        <h2>Find Schedule Items</h2>
                        <p>Filter your timetable by course or date.</p>
                    </div>
                </div>

                <form method="GET" action="schedule.php" class="schedule-filter-row">

                    <div class="schedule-filter-group">
                        <label for="course_id">Course</label>

                        <select id="course_id" name="course_id">
                            <option value="0">All Courses</option>

                            <?php foreach ($courseOptions as $course): ?>
                                <option
                                    value="<?php echo (int) $course['course_id']; ?>"
                                    <?php
                                    echo $selectedCourse === (int) $course['course_id']
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    <?php echo e($course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="schedule-filter-group">
                        <label for="date">Date</label>
                        <input
                            type="date"
                            id="date"
                            name="date"
                            value="<?php echo e($selectedDate); ?>"
                        >
                    </div>

                    <div class="form-actions">
                        <button class="primary-btn" type="submit">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>

                        <a class="secondary-btn" href="schedule.php">
                            <i class="fas fa-rotate-left"></i>
                            Reset
                        </a>
                    </div>

                </form>

            </section>

            <!-- =================================================
                 CLASS SESSIONS
                 ================================================= -->
            <section class="schedule-section">

                <div class="schedule-section-header">
                    <div>
                        <p class="card-label">Classes</p>
                        <h2>Class Sessions</h2>
                        <p>Scheduled sessions for your enrolled batches.</p>
                    </div>
                </div>

                <?php if (empty($classes)): ?>

                    <div class="schedule-empty-state">
                        <div class="schedule-empty-icon">
                            <i class="fas fa-calendar-xmark"></i>
                        </div>

                        <h3>No Class Sessions Found</h3>
                        <p>
                            Your enrolled batches do not have any class
                            sessions matching the selected filters.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="schedule-list">

                        <?php foreach ($classes as $class): ?>

                            <article class="schedule-item">

                                <div class="schedule-date-box">
                                    <strong>
                                        <?php
                                        echo e(
                                            date(
                                                'd',
                                                strtotime($class['class_date'])
                                            )
                                        );
                                        ?>
                                    </strong>

                                    <span>
                                        <?php
                                        echo e(
                                            date(
                                                'M',
                                                strtotime($class['class_date'])
                                            )
                                        );
                                        ?>
                                    </span>
                                </div>

                                <div class="schedule-item-content">

                                    <h3>
                                        <?php echo e($class['course_name']); ?>
                                        —
                                        <?php
                                        echo e(
                                            $class['topic'] ?: 'Class Session'
                                        );
                                        ?>
                                    </h3>

                                    <div class="schedule-meta">

                                        <span>
                                            <i class="fas fa-layer-group"></i>
                                            <?php echo e($class['batch_name']); ?>
                                        </span>

                                        <span>
                                            <i class="fas fa-clock"></i>
                                            <?php echo e(substr($class['start_time'], 0, 5)); ?>
                                            -
                                            <?php echo e(substr($class['end_time'], 0, 5)); ?>
                                        </span>

                                        <span>
                                            <i class="fas fa-chalkboard-teacher"></i>
                                            <?php echo e($class['teacher_name']); ?>
                                        </span>

                                        <span>
                                            <i class="fas fa-location-dot"></i>
                                            <?php echo e($class['venue'] ?: 'TBA'); ?>
                                        </span>

                                        <span>
                                            <i class="fas fa-circle-info"></i>
                                            <?php echo e(prettyStatus($class['status'])); ?>
                                        </span>

                                    </div>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

            <!-- =================================================
                 EXAM SCHEDULE
                 ================================================= -->
            <section class="schedule-section">

                <div class="schedule-section-header">
                    <div>
                        <p class="card-label">Examinations</p>
                        <h2>Exam Schedule</h2>
                        <p>Published and historical examinations for your enrolled batches.</p>
                    </div>
                </div>

                <?php if (empty($exams)): ?>

                    <div class="schedule-empty-state">
                        <div class="schedule-empty-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>

                        <h3>No Exams Found</h3>
                        <p>No matching examinations are currently scheduled.</p>
                    </div>

                <?php else: ?>

                    <div class="schedule-table-wrapper">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Course</th>
                                    <th>Exam</th>
                                    <th>Time</th>
                                    <th>Venue</th>
                                </tr>
                            </thead>

                            <tbody>

                                <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td>
                                            <?php echo e($exam['exam_date']); ?>
                                        </td>

                                        <td>
                                            <?php echo e($exam['course_name']); ?>
                                            <br>
                                            <small>
                                                <?php echo e($exam['batch_name']); ?>
                                            </small>
                                        </td>

                                        <td>
                                            <?php echo e($exam['title']); ?>
                                        </td>

                                        <td>
                                            <?php echo e(substr($exam['start_time'], 0, 5)); ?>
                                            -
                                            <?php echo e(substr($exam['end_time'], 0, 5)); ?>
                                        </td>

                                        <td>
                                            <?php echo e($exam['venue'] ?: 'TBA'); ?>
                                        </td>
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
