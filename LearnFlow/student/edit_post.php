<?php
session_start();
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/login.php");
    exit();
}
$userId = (int)$_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';
$projectName = 'LearnFlow';
$postId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($postId <= 0) { header("Location: discussions.php"); exit(); }
function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
$errorMessage = '';

$stmt = $conn->prepare("
    SELECT
        dp.post_id, dp.forum_id, dp.title, dp.content, dp.created_at,
        df.title AS forum_title, df.status AS forum_status,
        b.batch_name, c.course_name,
        e.enrollment_status
    FROM discussion_posts dp
    INNER JOIN discussion_forums df ON df.forum_id = dp.forum_id
    INNER JOIN batches b ON b.batch_id = df.batch_id
    INNER JOIN courses c ON c.course_id = b.course_id
    INNER JOIN enrollments e ON e.batch_id = b.batch_id AND e.student_id = ?
    WHERE dp.post_id = ? AND dp.user_id = ?
      AND e.enrollment_status = 'Active'
    LIMIT 1
");
$stmt->bind_param("iii", $userId, $postId, $userId);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$post) { header("Location: discussions.php"); exit(); }
$forumId = (int)$post['forum_id'];
if ($post['forum_status'] !== 'open') { header("Location: discussion_details.php?id=".$forumId); exit(); }
$postTitle = $post['title'];
$postContent = $post['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    $token = $_POST['csrf_token'] ?? '';
    $newTitle = trim($_POST['post_title'] ?? '');
    $newContent = trim($_POST['post_content'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $errorMessage = 'Invalid request. Please refresh and try again.';
    } elseif ($newTitle === '' || $newContent === '') {
        $errorMessage = 'Please enter both the post title and message.';
    } elseif (mb_strlen($newTitle) > 180) {
        $errorMessage = 'Post title cannot exceed 180 characters.';
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE discussion_posts dp
                INNER JOIN discussion_forums df ON df.forum_id = dp.forum_id
                INNER JOIN batches b ON b.batch_id = df.batch_id
                INNER JOIN enrollments e ON e.batch_id = b.batch_id AND e.student_id = ?
                SET dp.title = ?, dp.content = ?
                WHERE dp.post_id = ? AND dp.user_id = ?
                  AND df.status = 'open'
                  AND e.enrollment_status = 'Active'
            ");
            $stmt->bind_param("issii", $userId, $newTitle, $newContent, $postId, $userId);
            $stmt->execute();
            $stmt->close();
            header("Location: discussion_details.php?id=".$forumId."&updated=1");
            exit();
        } catch (Throwable $ex) {
            $errorMessage = 'Unable to update the discussion post.';
        }
    }
    $postTitle = $newTitle;
    $postContent = $newContent;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | Edit Post</title>
    <link rel="stylesheet" href="../css/student.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
                <a href="exams.php" class="menu-link"><i class="fas fa-clipboard-list"></i> Exams</a>
                <a href="grades.php" class="menu-link"><i class="fas fa-chart-column"></i> Grades</a>
                <a href="progress.php" class="menu-link"><i class="fas fa-chart-line"></i> Progress</a>
                <a href="attendance.php" class="menu-link"><i class="fas fa-calendar-check"></i> Attendance</a>
                <a href="schedule.php" class="menu-link"><i class="fas fa-calendar-days"></i> Schedule</a>
                <a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i> Announcements</a>
                <a href="discussions.php" class="menu-link active"><i class="fas fa-comments"></i> Discussions</a>
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
                    <p class="small-label">Community</p>
                    <h1>Edit Discussion Post</h1>
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
            <?php if ($errorMessage !== ''): ?><div style="margin-bottom:20px;padding:14px 16px;border-radius:12px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;"><i class="fas fa-circle-exclamation"></i> <?php echo e($errorMessage); ?></div><?php endif; ?>
            <section class="overview-cards">
                <article class="summary-card"><div><p class="card-label">Edit Discussion</p><h2><?php echo e($post['forum_title']); ?></h2></div><div class="summary-icon"><i class="fas fa-pen"></i></div></article>
                <article class="stat-card"><div><p class="card-label">Course</p><h3 class="edit-post-stat"><?php echo e($post['course_name']); ?></h3></div><span class="stat-badge"><?php echo e($post['batch_name']); ?></span></article>
            </section>
            <section class="discussion-detail-section">
                <div class="discussion-detail-header"><div><p class="card-label">Your Post</p><h2>Edit Post</h2><p>You can edit only posts created by your own Student account.</p></div><a href="discussion_details.php?id=<?php echo $forumId; ?>" class="secondary-btn"><i class="fas fa-arrow-left"></i> Cancel</a></div>
                <form method="POST" action="edit_post.php?id=<?php echo $postId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                    <div class="form-group"><label for="postTitle">Post Title</label><input class="discussion-input" id="postTitle" name="post_title" type="text" maxlength="180" value="<?php echo e($postTitle); ?>" required></div>
                    <div class="form-group" style="margin-top:14px;"><label for="postContent">Message</label><textarea class="discussion-textarea" id="postContent" name="post_content" required><?php echo e($postContent); ?></textarea></div>
                    <div class="edit-post-note"><i class="fas fa-circle-info"></i><p>Editing updates the existing post. It does not create a new discussion record.</p></div>
                    <div class="form-actions"><a href="discussion_details.php?id=<?php echo $forumId; ?>" class="secondary-btn">Cancel</a><button type="submit" name="update_post" class="primary-btn"><i class="fas fa-floppy-disk"></i> Save Changes</button></div>
                </form>
            </section>
        </main>
    </div>
</div>
<script src="../js/student.js"></script>
</body>
</html>
