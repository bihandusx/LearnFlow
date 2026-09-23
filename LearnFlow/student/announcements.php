<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
| Only authenticated Students may open this page.
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
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$selectedScope = trim($_GET['scope'] ?? 'all');
$selectedCourseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$searchTerm = trim($_GET['q'] ?? '');

$allowedScopes = ['all', 'system', 'course'];

if (!in_array($selectedScope, $allowedScopes, true)) {
    $selectedScope = 'all';
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

function announcementDate(?string $date): string
{
    if (empty($date)) {
        return 'Not specified';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return $date;
    }

    return date('d M Y, h:i A', $timestamp);
}

function roleLabel(?string $role): string
{
    switch ($role) {
        case 'Teacher':
            return 'Teacher';
        case 'AcademicCoordinator':
            return 'Academic Coordinator';
        case 'Admin':
            return 'System Administrator';
        case 'Student':
            return 'Student';
        case 'Parent':
            return 'Parent / Guardian';
        default:
            return $role ?: 'LearnFlow';
    }
}

/*
|--------------------------------------------------------------------------
| LOAD VISIBLE ANNOUNCEMENTS
|--------------------------------------------------------------------------
| A Student can see:
| 1. System-wide announcements where batch_id IS NULL.
| 2. Batch announcements only for a batch the Student belongs to.
| 3. Published announcements only.
| 4. Announcements that have not expired.
*/

$announcements = [];

try {
    $stmt = $conn->prepare("
        SELECT
            a.announcement_id,
            a.batch_id,
            a.author_user_id,
            a.title,
            a.content,
            a.posted_at,
            a.expiry_date,
            a.status,

            b.batch_name,

            c.course_id,
            c.course_name,

            u.fullname AS author_name,
            u.role AS author_role

        FROM announcements a

        LEFT JOIN batches b
            ON b.batch_id = a.batch_id

        LEFT JOIN courses c
            ON c.course_id = b.course_id

        INNER JOIN user_accounts u
            ON u.user_id = a.author_user_id

        WHERE
            a.status = 'published'

            AND (
                a.expiry_date IS NULL
                OR a.expiry_date >= NOW()
            )

AND (
    a.batch_id IS NULL

    OR EXISTS (
        SELECT 1
        FROM enrollments e2
        WHERE
            e2.student_id = ?
            AND e2.batch_id = a.batch_id
            AND e2.enrollment_status IN (
                'Active',
                'Completed'
            )
    )
)

        ORDER BY
            a.posted_at DESC,
            a.announcement_id DESC
    ");

    $stmt->bind_param("i", $studentId);
    $stmt->execute();

    $result = $stmt->get_result();
    $announcements = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} catch (Throwable $exception) {
    $error = 'Announcements could not be loaded at the moment.';
}

/*
|--------------------------------------------------------------------------
| BUILD COURSE FILTER OPTIONS
|--------------------------------------------------------------------------
*/

$courseOptions = [];

foreach ($announcements as $announcement) {
    if (
        !empty($announcement['course_id']) &&
        !empty($announcement['course_name'])
    ) {
        $courseOptions[(int) $announcement['course_id']] = $announcement['course_name'];
    }
}

asort($courseOptions);

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalAnnouncements = count($announcements);
$systemAnnouncements = 0;
$courseAnnouncements = 0;

foreach ($announcements as $announcement) {
    if (empty($announcement['batch_id'])) {
        $systemAnnouncements++;
    } else {
        $courseAnnouncements++;
    }
}

/*
|--------------------------------------------------------------------------
| APPLY PAGE FILTERS
|--------------------------------------------------------------------------
*/

$filteredAnnouncements = array_filter(
    $announcements,
    function ($announcement) use ($selectedScope, $selectedCourseId, $searchTerm) {

        if ($selectedScope === 'system' && !empty($announcement['batch_id'])) {
            return false;
        }

        if ($selectedScope === 'course' && empty($announcement['batch_id'])) {
            return false;
        }

        if (
            $selectedCourseId > 0 &&
            (int) ($announcement['course_id'] ?? 0) !== $selectedCourseId
        ) {
            return false;
        }

        if ($searchTerm !== '') {
            $searchableText = implode(' ', [
                $announcement['title'] ?? '',
                $announcement['content'] ?? '',
                $announcement['course_name'] ?? '',
                $announcement['batch_name'] ?? '',
                $announcement['author_name'] ?? '',
                $announcement['author_role'] ?? ''
            ]);

            if (stripos($searchableText, $searchTerm) === false) {
                return false;
            }
        }

        return true;
    }
);

$filteredAnnouncements = array_values($filteredAnnouncements);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($projectName); ?> | Announcements</title>

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

            <a href="schedule.php" class="menu-link">
                <i class="fas fa-calendar-days"></i>
                Schedule
            </a>

            <a href="announcements.php" class="menu-link active">
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
                    <p class="small-label">Communication</p>
                    <h1>Announcements</h1>
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

            <!-- =================================================
                 OVERVIEW
                 ================================================= -->

            <section class="overview-cards">

                <article class="summary-card">
                    <div>
                        <p class="card-label">Communication</p>
                        <h2>Stay updated with LearnFlow.</h2>
                    </div>

                    <div class="summary-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Total Announcements</p>
                        <h3><?php echo $totalAnnouncements; ?></h3>
                    </div>

                    <span class="stat-badge">Visible to you</span>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Course Updates</p>
                        <h3><?php echo $courseAnnouncements; ?></h3>
                    </div>

                    <span class="stat-badge">
                        <?php echo $systemAnnouncements; ?> system-wide
                    </span>
                </article>

            </section>

            <!-- =================================================
                 DATABASE ERROR
                 ================================================= -->

            <?php if ($error !== ''): ?>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#fef2f2;
                        color:#991b1b;
                        border:1px solid #fecaca;
                    "
                >
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo e($error); ?>
                </div>

            <?php endif; ?>

            <!-- =================================================
                 FILTERS
                 ================================================= -->

            <section class="announcement-section">

                <div class="announcement-section-header">
                    <div>
                        <p class="card-label">Find Updates</p>
                        <h2>Announcement Filters</h2>
                        <p>Filter system-wide and course announcements.</p>
                    </div>
                </div>

                <form method="GET" action="announcements.php" class="announcement-filter-row">

                    <div class="announcement-filter-group">
                        <label for="scope">Type</label>

                        <select id="scope" name="scope">
                            <option value="all" <?php echo $selectedScope === 'all' ? 'selected' : ''; ?>>
                                All Announcements
                            </option>

                            <option value="system" <?php echo $selectedScope === 'system' ? 'selected' : ''; ?>>
                                System Announcements
                            </option>

                            <option value="course" <?php echo $selectedScope === 'course' ? 'selected' : ''; ?>>
                                Course Announcements
                            </option>
                        </select>
                    </div>

                    <div class="announcement-filter-group">
                        <label for="course_id">Course</label>

                        <select id="course_id" name="course_id">
                            <option value="0">All Courses</option>

                            <?php foreach ($courseOptions as $courseId => $courseName): ?>
                                <option
                                    value="<?php echo (int) $courseId; ?>"
                                    <?php echo $selectedCourseId === (int) $courseId ? 'selected' : ''; ?>
                                >
                                    <?php echo e($courseName); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="announcement-search-box">
                        <i class="fas fa-search"></i>

                        <input
                            type="search"
                            name="q"
                            value="<?php echo e($searchTerm); ?>"
                            placeholder="Search announcements..."
                        >
                    </div>

                    <div class="form-actions" style="margin-top:0;">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-filter"></i>
                            Apply
                        </button>

                        <a href="announcements.php" class="secondary-btn">
                            <i class="fas fa-rotate-left"></i>
                            Reset
                        </a>
                    </div>

                </form>

            </section>

            <!-- =================================================
                 ANNOUNCEMENT LIST
                 ================================================= -->

            <section class="announcement-section">

                <div class="announcement-section-header">
                    <div>
                        <p class="card-label">Latest Updates</p>
                        <h2>Available Announcements</h2>
                        <p>
                            Showing <?php echo count($filteredAnnouncements); ?>
                            announcement<?php echo count($filteredAnnouncements) === 1 ? '' : 's'; ?>.
                        </p>
                    </div>
                </div>

                <?php if (empty($filteredAnnouncements)): ?>

                    <div class="announcement-empty-state">

                        <div class="announcement-empty-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>

                        <h3>No Announcements Found</h3>

                        <p>
                            There are no published announcements matching the selected filters.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="announcement-list">

                        <?php foreach ($filteredAnnouncements as $announcement): ?>

                            <?php $isSystemAnnouncement = empty($announcement['batch_id']); ?>

                            <article class="announcement-card">

                                <div class="announcement-card-icon">
                                    <i class="fas <?php echo $isSystemAnnouncement ? 'fa-building' : 'fa-book-open'; ?>"></i>
                                </div>

                                <div class="announcement-card-content">

                                    <div class="announcement-card-header">

                                        <div>
                                            <div class="announcement-labels">

                                                <span class="announcement-type-badge">
                                                    <?php echo $isSystemAnnouncement ? 'System' : 'Course'; ?>
                                                </span>

                                                <?php if (!$isSystemAnnouncement): ?>
                                                    <span class="announcement-course-badge">
                                                        <?php echo e($announcement['course_name'] ?? 'Course'); ?>
                                                        <?php if (!empty($announcement['batch_name'])): ?>
                                                            • <?php echo e($announcement['batch_name']); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endif; ?>

                                            </div>

                                            <h3 style="margin-top:10px;">
                                                <?php echo e($announcement['title']); ?>
                                            </h3>
                                        </div>

                                        <span class="stat-badge">
                                            <?php echo announcementDate($announcement['posted_at']); ?>
                                        </span>

                                    </div>

                                    <div class="announcement-message">
                                        <?php echo nl2br(e($announcement['content'])); ?>
                                    </div>

                                    <div class="announcement-footer">

                                        <span>
                                            <i class="fas fa-user"></i>
                                            <?php echo e($announcement['author_name']); ?>
                                            — <?php echo e(roleLabel($announcement['author_role'])); ?>
                                        </span>

                                        <span>
                                            <i class="fas fa-calendar"></i>
                                            Posted <?php echo announcementDate($announcement['posted_at']); ?>
                                        </span>

                                        <?php if (!empty($announcement['expiry_date'])): ?>
                                            <span>
                                                <i class="fas fa-hourglass-end"></i>
                                                Expires <?php echo announcementDate($announcement['expiry_date']); ?>
                                            </span>
                                        <?php endif; ?>

                                    </div>

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
