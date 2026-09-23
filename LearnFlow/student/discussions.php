<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';
$projectName = 'LearnFlow';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$courseId = isset($_GET['course_id']) ? max(0, (int) $_GET['course_id']) : 0;
$batchId  = isset($_GET['batch_id']) ? max(0, (int) $_GET['batch_id']) : 0;
$search   = trim($_GET['search'] ?? '');
$like     = '%' . $search . '%';

/* Courses available to this student */
$stmt = $conn->prepare("
    SELECT DISTINCT c.course_id, c.course_name
    FROM enrollments e
    INNER JOIN batches b ON b.batch_id = e.batch_id
    INNER JOIN courses c ON c.course_id = b.course_id
    WHERE e.student_id = ?
      AND e.enrollment_status IN ('Active','Completed')
    ORDER BY c.course_name
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Batches available to this student */
$stmt = $conn->prepare("
    SELECT DISTINCT b.batch_id, b.batch_name, c.course_id, c.course_name
    FROM enrollments e
    INNER JOIN batches b ON b.batch_id = e.batch_id
    INNER JOIN courses c ON c.course_id = b.course_id
    WHERE e.student_id = ?
      AND e.enrollment_status IN ('Active','Completed')
      AND (? = 0 OR c.course_id = ?)
    ORDER BY c.course_name, b.batch_name
");
$stmt->bind_param("iii", $userId, $courseId, $courseId);
$stmt->execute();
$batches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* Overview statistics */
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT df.forum_id) AS total_forums,
        COUNT(DISTINCT CASE WHEN dp.user_id = ? THEN dp.post_id END) AS my_posts,
        COUNT(DISTINCT CASE WHEN dr.user_id = ? THEN dr.reply_id END) AS my_replies
    FROM enrollments e
    INNER JOIN batches b ON b.batch_id = e.batch_id
    INNER JOIN discussion_forums df ON df.batch_id = b.batch_id
    LEFT JOIN discussion_posts dp ON dp.forum_id = df.forum_id
    LEFT JOIN discussion_replies dr ON dr.post_id = dp.post_id
    WHERE e.student_id = ?
      AND e.enrollment_status IN ('Active','Completed')
");
$stmt->bind_param("iii", $userId, $userId, $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
$totalForums  = (int)($stats['total_forums'] ?? 0);
$totalPosts   = (int)($stats['my_posts'] ?? 0);
$totalReplies = (int)($stats['my_replies'] ?? 0);

/* Accessible forums */
$stmt = $conn->prepare("
    SELECT DISTINCT
        df.forum_id,
        df.title,
        df.description,
        df.created_at,
        df.status,
        b.batch_id,
        b.batch_name,
        c.course_id,
        c.course_name,
        e.enrollment_status,
        (SELECT COUNT(*) FROM discussion_posts p WHERE p.forum_id = df.forum_id) AS post_count,
        (SELECT COUNT(*)
           FROM discussion_replies r
           INNER JOIN discussion_posts p2 ON p2.post_id = r.post_id
          WHERE p2.forum_id = df.forum_id) AS reply_count,
        (SELECT MAX(p3.created_at) FROM discussion_posts p3 WHERE p3.forum_id = df.forum_id) AS last_post_at
    FROM enrollments e
    INNER JOIN batches b ON b.batch_id = e.batch_id
    INNER JOIN courses c ON c.course_id = b.course_id
    INNER JOIN discussion_forums df ON df.batch_id = b.batch_id
    WHERE e.student_id = ?
      AND e.enrollment_status IN ('Active','Completed')
      AND (? = 0 OR c.course_id = ?)
      AND (? = 0 OR b.batch_id = ?)
      AND (
            ? = '' OR
            df.title LIKE ? OR
            COALESCE(df.description, '') LIKE ? OR
            c.course_name LIKE ? OR
            b.batch_name LIKE ?
          )
    ORDER BY
        CASE df.status WHEN 'open' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END,
        COALESCE(last_post_at, df.created_at) DESC,
        df.title
");
$stmt->bind_param(
    "iiiiisssss",
    $userId,
    $courseId, $courseId,
    $batchId, $batchId,
    $search, $like, $like, $like, $like
);
$stmt->execute();
$forums = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | Discussions</title>
    <link rel="stylesheet" href="../css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
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
                    <h1>Discussions</h1>
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
                    <div><p class="card-label">Student Community</p><h2>Ask questions and join course discussions.</h2></div>
                    <div class="summary-icon"><i class="fas fa-comments"></i></div>
                </article>
                <article class="stat-card"><div><p class="card-label">Forums</p><h3><?php echo $totalForums; ?></h3></div><span class="stat-badge">Available forums</span></article>
                <article class="stat-card"><div><p class="card-label">My Activity</p><h3><?php echo $totalPosts + $totalReplies; ?></h3></div><span class="stat-badge"><?php echo $totalPosts; ?> posts · <?php echo $totalReplies; ?> replies</span></article>
            </section>

            <section class="discussion-section">
                <div class="discussion-section-header">
                    <div><p class="card-label">Discussion Forums</p><h2>Browse Forums</h2><p>Only forums belonging to your enrolled batches are shown.</p></div>
                </div>
                <form method="GET" action="discussions.php" class="discussion-filter-row">
                    <div class="discussion-filter-group">
                        <label for="discussionCourse">Course</label>
                        <select id="discussionCourse" name="course_id" onchange="this.form.submit()">
                            <option value="0">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo (int)$course['course_id']; ?>" <?php echo $courseId === (int)$course['course_id'] ? 'selected' : ''; ?>><?php echo e($course['course_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="discussion-filter-group">
                        <label for="discussionBatch">Batch</label>
                        <select id="discussionBatch" name="batch_id">
                            <option value="0">All Batches</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?php echo (int)$batch['batch_id']; ?>" <?php echo $batchId === (int)$batch['batch_id'] ? 'selected' : ''; ?>><?php echo e($batch['batch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="discussion-filter-group">
                        <label for="discussionSearch">Search</label>
                        <div class="discussion-search-box"><i class="fas fa-search"></i><input type="text" id="discussionSearch" name="search" value="<?php echo e($search); ?>" placeholder="Search discussions"></div>
                    </div>
                    <div class="discussion-filter-group" style="justify-content:flex-end;">
                        <label>&nbsp;</label>
                        <button type="submit" class="primary-btn"><i class="fas fa-filter"></i> Apply</button>
                    </div>
                </form>
            </section>

            <section class="discussion-section">
                <div class="discussion-section-header"><div><p class="card-label">Forums</p><h2>Available Discussions</h2></div></div>
                <?php if (empty($forums)): ?>
                    <div class="discussion-empty-state">
                        <div class="discussion-empty-icon"><i class="fas fa-comments"></i></div>
                        <h3>No Discussion Forums Available</h3>
                        <p>No matching forum is available for your enrolled batches.</p>
                    </div>
                <?php else: ?>
                    <div class="discussion-forum-grid">
                        <?php foreach ($forums as $forum): ?>
                            <article class="discussion-forum-card">
                                <div class="discussion-forum-icon"><i class="fas fa-comments"></i></div>
                                <div class="discussion-forum-content">
                                    <span class="discussion-course-name"><?php echo e($forum['course_name']); ?></span>
                                    <h3><?php echo e($forum['title']); ?></h3>
                                    <p><?php echo e($forum['description'] ?: 'No description has been added for this forum.'); ?></p>
                                    <div class="discussion-forum-meta">
                                        <span><i class="fas fa-layer-group"></i> <?php echo e($forum['batch_name']); ?></span>
                                        <span><i class="fas fa-message"></i> <?php echo (int)$forum['post_count']; ?> posts</span>
                                        <span><i class="fas fa-reply"></i> <?php echo (int)$forum['reply_count']; ?> replies</span>
                                        <span><i class="fas fa-circle"></i> <?php echo e(ucfirst($forum['status'])); ?></span>
                                    </div>
                                    <a class="discussion-open-btn" href="discussion_details.php?id=<?php echo (int)$forum['forum_id']; ?>"><i class="fas fa-arrow-right"></i> Open Forum</a>
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
