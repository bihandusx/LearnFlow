<?php
session_start();
require_once "../config/db.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/login.php"); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: discussions.php"); exit();
}
$userId = (int)$_SESSION['user_id'];
$postId = (int)($_POST['post_id'] ?? 0);
$token = $_POST['csrf_token'] ?? '';
if ($postId <= 0 || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
    header("Location: discussions.php"); exit();
}

$stmt = $conn->prepare("
    SELECT dp.forum_id
    FROM discussion_posts dp
    INNER JOIN discussion_forums df ON df.forum_id = dp.forum_id
    INNER JOIN batches b ON b.batch_id = df.batch_id
    INNER JOIN enrollments e ON e.batch_id = b.batch_id AND e.student_id = ?
    WHERE dp.post_id = ? AND dp.user_id = ?
      AND df.status = 'open'
      AND e.enrollment_status = 'Active'
    LIMIT 1
");
$stmt->bind_param("iii", $userId, $postId, $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) { header("Location: discussions.php"); exit(); }
$forumId = (int)$row['forum_id'];

try {
    /* Replies are removed automatically by ON DELETE CASCADE. */
    $stmt = $conn->prepare("DELETE FROM discussion_posts WHERE post_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $postId, $userId);
    $stmt->execute();
    $deleted = $stmt->affected_rows === 1;
    $stmt->close();
    if ($deleted) {
        header("Location: discussion_details.php?id=".$forumId."&deleted=1");
    } else {
        $_SESSION['discussion_error'] = 'Discussion post could not be deleted.';
        header("Location: discussion_details.php?id=".$forumId);
    }
    exit();
} catch (Throwable $ex) {
    $_SESSION['discussion_error'] = 'Unable to delete the discussion post.';
    header("Location: discussion_details.php?id=".$forumId);
    exit();
}
?>
