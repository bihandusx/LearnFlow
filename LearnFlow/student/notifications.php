<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';
$success = $_SESSION['notification_success'] ?? '';
unset($_SESSION['notification_success']);

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
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

function notificationDate(?string $date): string
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

function safeNotificationLink(?string $url): ?string
{
    $url = trim((string) $url);

    if ($url === '') {
        return null;
    }

    /*
     * Notifications in this Student portal should point to pages in the
     * application. Reject script/data URLs and external schemes.
     */
    if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
        return null;
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
        return null;
    }

    return $url;
}

/*
|--------------------------------------------------------------------------
| MARK NOTIFICATION(S) AS READ
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid request. Please refresh the page and try again.';

    } else {

        try {

            /*
            |------------------------------------------------------------------
            | MARK ONE NOTIFICATION AS READ
            |------------------------------------------------------------------
            */

            if (isset($_POST['mark_read'])) {

                $notificationId = (int) ($_POST['notification_id'] ?? 0);

                if ($notificationId <= 0) {
                    throw new RuntimeException('Invalid notification selected.');
                }

                $stmt = $conn->prepare("
                    UPDATE notifications
                    SET is_read = TRUE
                    WHERE
                        notification_id = ?
                        AND user_id = ?
                ");

                $stmt->bind_param(
                    "ii",
                    $notificationId,
                    $studentId
                );

                $stmt->execute();

                if ($stmt->affected_rows > 0) {
                    $_SESSION['notification_success'] = 'Notification marked as read.';
                }

                $stmt->close();

                header("Location: notifications.php");
                exit();
            }

            /*
            |------------------------------------------------------------------
            | MARK ALL NOTIFICATIONS AS READ
            |------------------------------------------------------------------
            */

            if (isset($_POST['mark_all_read'])) {

                $stmt = $conn->prepare("
                    UPDATE notifications
                    SET is_read = TRUE
                    WHERE
                        user_id = ?
                        AND is_read = FALSE
                ");

                $stmt->bind_param("i", $studentId);
                $stmt->execute();

                $_SESSION['notification_success'] = 'All notifications marked as read.';

                $stmt->close();

                header("Location: notifications.php");
                exit();
            }

        } catch (RuntimeException $exception) {
            $error = $exception->getMessage();

        } catch (Throwable $exception) {
            $error = 'The notification operation could not be completed. Please try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$selectedStatus = trim($_GET['status'] ?? 'all');
$searchTerm = trim($_GET['q'] ?? '');

$allowedStatuses = ['all', 'unread', 'read'];

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

/*
|--------------------------------------------------------------------------
| LOAD STUDENT NOTIFICATIONS
|--------------------------------------------------------------------------
| A notification is private to its user_id. The query never exposes another
| user's notifications.
*/

$notifications = [];

try {
    $stmt = $conn->prepare("
        SELECT
            notification_id,
            user_id,
            title,
            message,
            link_url,
            is_read,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY
            is_read ASC,
            created_at DESC,
            notification_id DESC
    ");

    $stmt->bind_param("i", $studentId);
    $stmt->execute();

    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();

} catch (Throwable $exception) {
    $error = 'Notifications could not be loaded at the moment.';
}

/*
|--------------------------------------------------------------------------
| COUNTS
|--------------------------------------------------------------------------
*/

$totalNotifications = count($notifications);
$unreadNotifications = 0;
$readNotifications = 0;

foreach ($notifications as $notification) {
    if ((int) $notification['is_read'] === 1) {
        $readNotifications++;
    } else {
        $unreadNotifications++;
    }
}

/*
|--------------------------------------------------------------------------
| APPLY PAGE FILTERS
|--------------------------------------------------------------------------
*/

$filteredNotifications = array_filter(
    $notifications,
    function ($notification) use ($selectedStatus, $searchTerm) {

        $isRead = (int) $notification['is_read'] === 1;

        if ($selectedStatus === 'unread' && $isRead) {
            return false;
        }

        if ($selectedStatus === 'read' && !$isRead) {
            return false;
        }

        if ($searchTerm !== '') {
            $searchableText = implode(' ', [
                $notification['title'] ?? '',
                $notification['message'] ?? ''
            ]);

            if (stripos($searchableText, $searchTerm) === false) {
                return false;
            }
        }

        return true;
    }
);

$filteredNotifications = array_values($filteredNotifications);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($projectName); ?> | Notifications</title>

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

            <a href="notifications.php" class="menu-link active">
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
                    <p class="small-label">Notifications</p>
                    <h1>My Notifications</h1>
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
                        <p class="card-label">Student Notifications</p>
                        <h2>Keep track of important activity.</h2>
                    </div>

                    <div class="summary-icon">
                        <i class="fas fa-bell"></i>
                    </div>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Unread</p>
                        <h3><?php echo $unreadNotifications; ?></h3>
                    </div>

                    <span class="stat-badge">Needs attention</span>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Total</p>
                        <h3><?php echo $totalNotifications; ?></h3>
                    </div>

                    <span class="stat-badge">
                        <?php echo $readNotifications; ?> read
                    </span>
                </article>

            </section>

            <!-- =================================================
                 SUCCESS MESSAGE
                 ================================================= -->

            <?php if ($success !== ''): ?>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#ecfdf5;
                        color:#166534;
                        border:1px solid #bbf7d0;
                    "
                >
                    <i class="fas fa-circle-check"></i>
                    <?php echo e($success); ?>
                </div>

            <?php endif; ?>

            <!-- =================================================
                 ERROR MESSAGE
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
                 FILTERS + MARK ALL READ
                 ================================================= -->

            <section class="notification-section">

                <div class="notification-section-header">

                    <div>
                        <p class="card-label">Notification Center</p>
                        <h2>Filter Notifications</h2>
                        <p>Search your notifications or show only unread/read items.</p>
                    </div>

                    <?php if ($unreadNotifications > 0): ?>

                        <form method="POST" action="notifications.php">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php echo e($_SESSION['csrf_token']); ?>"
                            >

                            <button
                                type="submit"
                                name="mark_all_read"
                                class="secondary-btn"
                            >
                                <i class="fas fa-check-double"></i>
                                Mark All Read
                            </button>

                        </form>

                    <?php endif; ?>

                </div>

                <form method="GET" action="notifications.php" class="notification-filter-row">

                    <div class="notification-filter-group">
                        <label for="status">Status</label>

                        <select id="status" name="status">
                            <option value="all" <?php echo $selectedStatus === 'all' ? 'selected' : ''; ?>>
                                All Notifications
                            </option>

                            <option value="unread" <?php echo $selectedStatus === 'unread' ? 'selected' : ''; ?>>
                                Unread
                            </option>

                            <option value="read" <?php echo $selectedStatus === 'read' ? 'selected' : ''; ?>>
                                Read
                            </option>
                        </select>
                    </div>

                    <div class="notification-search-box">
                        <i class="fas fa-search"></i>

                        <input
                            type="search"
                            name="q"
                            value="<?php echo e($searchTerm); ?>"
                            placeholder="Search notifications..."
                        >
                    </div>

                    <div class="form-actions" style="margin-top:0;">
                        <button type="submit" class="primary-btn">
                            <i class="fas fa-filter"></i>
                            Apply
                        </button>

                        <a href="notifications.php" class="secondary-btn">
                            <i class="fas fa-rotate-left"></i>
                            Reset
                        </a>
                    </div>

                </form>

            </section>

            <!-- =================================================
                 NOTIFICATION LIST
                 ================================================= -->

            <section class="notification-section">

                <div class="notification-section-header">
                    <div>
                        <p class="card-label">Your Activity</p>
                        <h2>Notifications</h2>
                        <p>
                            Showing <?php echo count($filteredNotifications); ?>
                            notification<?php echo count($filteredNotifications) === 1 ? '' : 's'; ?>.
                        </p>
                    </div>
                </div>

                <?php if (empty($filteredNotifications)): ?>

                    <div class="notification-empty-state">

                        <div class="notification-empty-icon">
                            <i class="fas fa-bell-slash"></i>
                        </div>

                        <h3>No Notifications Found</h3>

                        <p>
                            There are no notifications matching the selected filters.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="student-notification-list">

                        <?php foreach ($filteredNotifications as $notification): ?>

                            <?php
                            $isRead = (int) $notification['is_read'] === 1;
                            $notificationLink = safeNotificationLink($notification['link_url']);
                            ?>

                            <article
                                class="student-notification-card <?php echo $isRead ? '' : 'notification-unread'; ?>"
                            >

                                <div class="student-notification-icon">
                                    <i class="fas <?php echo $isRead ? 'fa-envelope-open' : 'fa-envelope'; ?>"></i>
                                </div>

                                <div class="student-notification-content">

                                    <div class="student-notification-header">

                                        <div>
                                            <span class="notification-type-label">
                                                <?php echo $isRead ? 'Read' : 'New Notification'; ?>
                                            </span>

                                            <h3>
                                                <?php echo e($notification['title']); ?>
                                            </h3>
                                        </div>

                                        <?php if (!$isRead): ?>
                                            <span class="notification-new-badge">New</span>
                                        <?php endif; ?>

                                    </div>

                                    <p>
                                        <?php echo nl2br(e($notification['message'])); ?>
                                    </p>

                                    <div class="student-notification-footer">

                                        <span>
                                            <i class="fas fa-clock"></i>
                                            <?php echo notificationDate($notification['created_at']); ?>
                                        </span>

                                        <div
                                            style="
                                                display:flex;
                                                align-items:center;
                                                gap:10px;
                                                flex-wrap:wrap;
                                            "
                                        >

                                            <?php if ($notificationLink !== null): ?>
                                                <a href="<?php echo e($notificationLink); ?>" class="secondary-btn">
                                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                                    Open
                                                </a>
                                            <?php endif; ?>

                                            <?php if (!$isRead): ?>

                                                <form method="POST" action="notifications.php">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?php echo e($_SESSION['csrf_token']); ?>"
                                                    >

                                                    <input
                                                        type="hidden"
                                                        name="notification_id"
                                                        value="<?php echo (int) $notification['notification_id']; ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        name="mark_read"
                                                        class="notification-read-btn"
                                                    >
                                                        <i class="fas fa-check"></i>
                                                        Mark as Read
                                                    </button>

                                                </form>

                                            <?php endif; ?>

                                        </div>

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
