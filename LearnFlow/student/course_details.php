<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/
if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

/*
|--------------------------------------------------------------------------
| REQUIRED URL PARAMETERS
| course_details.php?id=COURSE_ID&batch_id=BATCH_ID
|--------------------------------------------------------------------------
*/
$courseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;

if ($courseId <= 0 || $batchId <= 0) {
    header("Location: courses.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    return number_format((float) $amount, 2);
}

function prettyStatus(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/*
|--------------------------------------------------------------------------
| LOAD COURSE ONLY IF THIS STUDENT IS ENROLLED IN THE REQUESTED BATCH
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        e.enrollment_id,
        e.enrollment_date,
        e.enrollment_status,

        b.batch_id,
        b.batch_name,
        b.start_date AS batch_start_date,
        b.end_date AS batch_end_date,
        b.status AS batch_status,

        c.course_id,
        c.course_name,
        c.description,
        c.course_fee,
        c.status AS course_status,

        s.subject_code,
        s.subject_name,

        at.term_name,
        at.start_date AS term_start_date,
        at.end_date AS term_end_date,
        at.status AS term_status,

        GROUP_CONCAT(
            DISTINCT ua.fullname
            ORDER BY ua.fullname
            SEPARATOR ', '
        ) AS teacher_names

    FROM enrollments e

    INNER JOIN batches b
        ON b.batch_id = e.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    INNER JOIN academic_terms at
        ON at.term_id = b.term_id

    LEFT JOIN subjects s
        ON s.subject_id = c.subject_id

    LEFT JOIN teacher_courses tc
        ON tc.course_id = c.course_id

    LEFT JOIN user_accounts ua
        ON ua.user_id = tc.teacher_id
        AND ua.role = 'Teacher'

    WHERE
        e.student_id = ?
        AND c.course_id = ?
        AND b.batch_id = ?
        AND e.enrollment_status IN (
            'Pending',
            'Active',
            'Completed'
        )

    GROUP BY
        e.enrollment_id,
        e.enrollment_date,
        e.enrollment_status,

        b.batch_id,
        b.batch_name,
        b.start_date,
        b.end_date,
        b.status,

        c.course_id,
        c.course_name,
        c.description,
        c.course_fee,
        c.status,

        s.subject_code,
        s.subject_name,

        at.term_name,
        at.start_date,
        at.end_date,
        at.status

    LIMIT 1
");

$stmt->bind_param(
    "iii",
    $studentId,
    $courseId,
    $batchId
);

$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
$stmt->close();

/*
|--------------------------------------------------------------------------
| SECURITY: STUDENT MUST BE ENROLLED IN THIS EXACT COURSE/BATCH
|--------------------------------------------------------------------------
*/
if (!$course) {
    header("Location: courses.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| LOAD COURSE MODULES + STUDENT PROGRESS + APPROVED RESOURCE COUNTS
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        m.module_id,
        m.module_name,
        m.module_order,
        m.description,

        COALESCE(
            smp.progress_percentage,
            0
        ) AS progress_percentage,

        COALESCE(
            smp.progress_status,
            'not_started'
        ) AS progress_status,

        smp.last_accessed_at,
        smp.completed_at,

        COUNT(
            DISTINCT CASE
                WHEN lr.approval_status = 'approved'
                THEN lr.resource_id
            END
        ) AS resource_count

    FROM modules m

    LEFT JOIN student_module_progress smp
        ON smp.module_id = m.module_id
        AND smp.student_id = ?

    LEFT JOIN learning_resources lr
        ON lr.module_id = m.module_id

    WHERE
        m.course_id = ?

    GROUP BY
        m.module_id,
        m.module_name,
        m.module_order,
        m.description,
        smp.progress_percentage,
        smp.progress_status,
        smp.last_accessed_at,
        smp.completed_at

    ORDER BY
        m.module_order ASC,
        m.module_id ASC
");

$stmt->bind_param(
    "ii",
    $studentId,
    $courseId
);

$stmt->execute();
$modules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/*
|--------------------------------------------------------------------------
| COURSE COUNTS AND PROGRESS
|--------------------------------------------------------------------------
*/
$moduleCount = count($modules);
$resourceCount = 0;
$completedModuleCount = 0;
$progressTotal = 0.0;

foreach ($modules as $module) {
    $resourceCount += (int) $module['resource_count'];
    $progressTotal += (float) $module['progress_percentage'];

    if ($module['progress_status'] === 'completed') {
        $completedModuleCount++;
    }
}

$courseProgress = $moduleCount > 0
    ? round($progressTotal / $moduleCount, 2)
    : 0;

/*
|--------------------------------------------------------------------------
| LOAD ASSESSMENT COUNTS FOR THIS EXACT BATCH
| Only published/closed assessments are visible to students.
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    SELECT
        SUM(
            CASE
                WHEN assessment_type = 'assignment'
                THEN 1
                ELSE 0
            END
        ) AS assignment_count,

        SUM(
            CASE
                WHEN assessment_type = 'quiz'
                THEN 1
                ELSE 0
            END
        ) AS quiz_count,

        SUM(
            CASE
                WHEN assessment_type = 'exam'
                THEN 1
                ELSE 0
            END
        ) AS exam_count

    FROM assessments

    WHERE
        batch_id = ?
        AND status IN (
            'published',
            'closed'
        )
");

$stmt->bind_param(
    "i",
    $batchId
);

$stmt->execute();
$assessmentCounts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$assignmentCount = (int) ($assessmentCounts['assignment_count'] ?? 0);
$quizCount = (int) ($assessmentCounts['quiz_count'] ?? 0);
$examCount = (int) ($assessmentCounts['exam_count'] ?? 0);

/*
|--------------------------------------------------------------------------
| DISPLAY VALUES
|--------------------------------------------------------------------------
*/
$courseName = $course['course_name'];
$courseCode = $course['subject_code'] ?: ('COURSE-' . $course['course_id']);
$subjectName = $course['subject_name'] ?: 'General';
$batchName = $course['batch_name'];
$teacherName = $course['teacher_names'] ?: 'Not assigned yet';
$courseDescription = $course['description'] ?: 'No course description has been added yet.';
$enrollmentStatus = $course['enrollment_status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($projectName); ?> | Course Details</title>

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

            <a href="courses.php" class="menu-link active">
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

            <a href="schedule.php" class="menu-link">
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
         CONTENT AREA
         ===================================================== -->
    <div class="content-area">

        <!-- =================================================
             TOPBAR
             ================================================= -->
        <header class="topbar">

            <div class="topbar-left">

                <button class="mobile-menu-btn" type="button">
                    <i class="fas fa-bars"></i>
                </button>

                <div class="dashboard-title">
                    <p class="small-label">Courses</p>
                    <h1>Course Details</h1>
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

        <!-- =================================================
             MAIN CONTENT
             ================================================= -->
        <main class="dashboard-main">

            <?php if ($enrollmentStatus === 'Pending'): ?>
                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#fff7ed;
                        color:#9a3412;
                        border:1px solid #fed7aa;
                    "
                >
                    <i class="fas fa-clock"></i>
                    Your enrollment is currently pending.
                </div>
            <?php endif; ?>

            <!-- =================================================
                 COURSE SUMMARY
                 ================================================= -->
            <section class="overview-cards">

                <article class="summary-card">
                    <div>
                        <p class="card-label">
                            <?php echo e($courseCode); ?>
                        </p>

                        <h2>
                            <?php echo e($courseName); ?>
                        </h2>
                    </div>

                    <div class="summary-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Modules</p>
                        <h3><?php echo $moduleCount; ?></h3>
                    </div>

                    <span class="stat-badge">
                        <?php echo $completedModuleCount; ?> completed
                    </span>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Progress</p>
                        <h3><?php echo e($courseProgress); ?>%</h3>
                    </div>

                    <span class="stat-badge">
                        Course completion
                    </span>
                </article>

            </section>

            <!-- =================================================
                 COURSE INFORMATION
                 ================================================= -->
            <section class="course-detail-section">

                <div class="course-detail-header">
                    <div>
                        <p class="card-label">Course Information</p>
                        <h2><?php echo e($courseName); ?></h2>
                        <p><?php echo e($subjectName); ?></p>
                    </div>

                    <a href="courses.php" class="secondary-btn">
                        <i class="fas fa-arrow-left"></i>
                        Back to Courses
                    </a>
                </div>

                <div class="course-info-grid">

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>

                        <div>
                            <span>Batch</span>
                            <strong><?php echo e($batchName); ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>

                        <div>
                            <span>Teacher</span>
                            <strong><?php echo e($teacherName); ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>

                        <div>
                            <span>Academic Term</span>
                            <strong><?php echo e($course['term_name']); ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-user-check"></i>
                        </div>

                        <div>
                            <span>Enrollment</span>
                            <strong><?php echo e($enrollmentStatus); ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>

                        <div>
                            <span>Approved Resources</span>
                            <strong><?php echo $resourceCount; ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>

                        <div>
                            <span>Assignments</span>
                            <strong><?php echo $assignmentCount; ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>

                        <div>
                            <span>Quizzes</span>
                            <strong><?php echo $quizCount; ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>

                        <div>
                            <span>Exams</span>
                            <strong><?php echo $examCount; ?></strong>
                        </div>
                    </div>

                </div>

                <div class="course-description">
                    <h3>About This Course</h3>
                    <p><?php echo nl2br(e($courseDescription)); ?></p>
                </div>

                <div class="course-info-grid" style="margin-top:20px;">

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>

                        <div>
                            <span>Course Fee</span>
                            <strong>LKR <?php echo money($course['course_fee']); ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>

                        <div>
                            <span>Enrolled On</span>
                            <strong><?php echo e($course['enrollment_date']); ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-play"></i>
                        </div>

                        <div>
                            <span>Batch Start</span>
                            <strong><?php echo e($course['batch_start_date']); ?></strong>
                        </div>
                    </div>

                    <div class="course-info-item">
                        <div class="course-info-icon">
                            <i class="fas fa-flag-checkered"></i>
                        </div>

                        <div>
                            <span>Batch End</span>
                            <strong>
                                <?php echo e($course['batch_end_date'] ?: 'Not specified'); ?>
                            </strong>
                        </div>
                    </div>

                </div>

            </section>

            <!-- =================================================
                 MODULES
                 ================================================= -->
            <section class="course-detail-section">

                <div class="course-detail-header">
                    <div>
                        <p class="card-label">Course Content</p>
                        <h2>Modules</h2>
                        <p>
                            Your module progress and approved resource counts
                            are loaded directly from the database.
                        </p>
                    </div>
                </div>

                <?php if (empty($modules)): ?>

                    <div class="course-empty-state">
                        <div class="course-empty-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>

                        <h3>No Modules Available</h3>

                        <p>
                            No modules have been added to this course yet.
                        </p>
                    </div>

                <?php else: ?>

                    <div class="card-grid">

                        <?php foreach ($modules as $module): ?>

                            <?php
                            $moduleProgress = (float) $module['progress_percentage'];
                            $moduleStatus = prettyStatus($module['progress_status']);
                            ?>

                            <article class="dashboard-card">

                                <div class="card-icon bg-blue">
                                    <i class="fas fa-layer-group"></i>
                                </div>

                                <p class="card-label">
                                    Module <?php echo (int) $module['module_order']; ?>
                                </p>

                                <h3>
                                    <?php echo e($module['module_name']); ?>
                                </h3>

                                <p>
                                    <?php
                                    echo e(
                                        $module['description']
                                        ?: 'No description has been added for this module.'
                                    );
                                    ?>
                                </p>

                                <p>
                                    <strong>Resources:</strong>
                                    <?php echo (int) $module['resource_count']; ?>
                                </p>

                                <p>
                                    <strong>Status:</strong>
                                    <?php echo e($moduleStatus); ?>
                                </p>

                                <div style="margin-top:14px;">
                                    <div
                                        style="
                                            display:flex;
                                            justify-content:space-between;
                                            gap:10px;
                                            margin-bottom:6px;
                                            font-size:13px;
                                        "
                                    >
                                        <span>Progress</span>
                                        <strong><?php echo e($moduleProgress); ?>%</strong>
                                    </div>

                                    <div
                                        style="
                                            width:100%;
                                            height:9px;
                                            border-radius:999px;
                                            overflow:hidden;
                                            background:#e5e7eb;
                                        "
                                    >
                                        <div
                                            style="
                                                width:<?php echo max(0, min(100, $moduleProgress)); ?>%;
                                                height:100%;
                                                background:currentColor;
                                            "
                                        ></div>
                                    </div>
                                </div>

                                <div
                                    class="form-actions"
                                    style="justify-content:flex-start; margin-top:16px;"
                                >
                                    <a
                                        class="primary-btn"
                                        href="materials.php?course_id=<?php echo $courseId; ?>&batch_id=<?php echo $batchId; ?>&module_id=<?php echo (int) $module['module_id']; ?>"
                                    >
                                        <i class="fas fa-folder-open"></i>
                                        View Resources
                                    </a>
                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

            <!-- =================================================
                 QUICK COURSE ACTIONS
                 ================================================= -->
            <section class="card-grid">

                <a
                    href="materials.php?course_id=<?php echo $courseId; ?>&batch_id=<?php echo $batchId; ?>"
                    class="dashboard-card"
                >
                    <div class="card-icon bg-blue">
                        <i class="fas fa-folder-open"></i>
                    </div>

                    <h3>Materials</h3>

                    <p>
                        View approved notes, tutes, recordings and other resources.
                    </p>
                </a>

                <a
                    href="assignments.php?course_id=<?php echo $courseId; ?>&batch_id=<?php echo $batchId; ?>"
                    class="dashboard-card"
                >
                    <div class="card-icon bg-green">
                        <i class="fas fa-file-alt"></i>
                    </div>

                    <h3>Assignments</h3>

                    <p>
                        View and submit assignments for this batch.
                    </p>
                </a>

                <a
                    href="quizzes.php?course_id=<?php echo $courseId; ?>&batch_id=<?php echo $batchId; ?>"
                    class="dashboard-card"
                >
                    <div class="card-icon bg-purple">
                        <i class="fas fa-question-circle"></i>
                    </div>

                    <h3>Quizzes</h3>

                    <p>
                        View available quizzes for this batch.
                    </p>
                </a>

                <a
                    href="discussions.php?course_id=<?php echo $courseId; ?>&batch_id=<?php echo $batchId; ?>"
                    class="dashboard-card"
                >
                    <div class="card-icon bg-teal">
                        <i class="fas fa-comments"></i>
                    </div>

                    <h3>Discussions</h3>

                    <p>
                        Participate in discussions for your enrolled batch.
                    </p>
                </a>

            </section>

        </main>
    </div>

</div>

<script src="../js/student.js"></script>

</body>
</html>
