<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (!isset($_SESSION['user_id'], $_SESSION['role']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';
$submissionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($submissionId <= 0) {
    header("Location: assignments.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function canonicalSubmissionPath(string $storedPath): ?string
{
    if ($storedPath === '') return null;
    $base = basename($storedPath);
    return $base !== '' ? '../uploads/assignment_submissions/' . $base : null;
}

$stmt = $conn->prepare("
    SELECT
        sub.submission_id,
        sub.assignment_id,
        sub.student_id,
        sub.file_url,
        sub.text_answer,
        sub.submission_date,
        sub.submission_status,
        sub.marks,
        sub.feedback,

        a.title,
        a.status AS assessment_status,
        a.open_date,
        a.close_date,
        ass.due_date,
        ass.submission_type,
        ass.max_file_size_mb,
        ass.allow_late_submission,

        b.batch_name,
        c.course_name,
        e.enrollment_status

    FROM assignment_submissions sub
    INNER JOIN assignments ass ON ass.assignment_id = sub.assignment_id
    INNER JOIN assessments a ON a.assessment_id = ass.assignment_id
    INNER JOIN batches b ON b.batch_id = a.batch_id
    INNER JOIN courses c ON c.course_id = b.course_id
    INNER JOIN enrollments e
        ON e.batch_id = a.batch_id
       AND e.student_id = sub.student_id

    WHERE sub.submission_id = ?
      AND sub.student_id = ?
      AND a.assessment_type = 'assignment'
    LIMIT 1
");
$stmt->bind_param("ii", $submissionId, $studentId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$submission) {
    header("Location: assignments.php");
    exit();
}

$assignmentId = (int) $submission['assignment_id'];
$now = new DateTime();

$openReached =
    empty($submission['open_date']) ||
    $now >= new DateTime($submission['open_date']);

$closeNotReached =
    empty($submission['close_date']) ||
    $now <= new DateTime($submission['close_date']);

$duePassed =
    !empty($submission['due_date']) &&
    $now > new DateTime($submission['due_date']);

$allowLate = (bool) $submission['allow_late_submission'];

$canEdit =
    $submission['submission_status'] !== 'Graded' &&
    $submission['assessment_status'] === 'published' &&
    $submission['enrollment_status'] === 'Active' &&
    $openReached &&
    $closeNotReached &&
    (!$duePassed || $allowLate);

if (!$canEdit) {
    header("Location: assignment_details.php?id=" . $assignmentId);
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_submission'])) {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } else {
        $textAnswer = trim($_POST['submission_text'] ?? '');
        $filePath = $submission['file_url'];
        $newPhysicalFile = null;
        $maxMb = (int) ($submission['max_file_size_mb'] ?: 10);

        if (isset($_FILES['assignment_file']) && ($_FILES['assignment_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['assignment_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'The new file upload failed.';
            } elseif ((int) $_FILES['assignment_file']['size'] > $maxMb * 1024 * 1024) {
                $error = "The new file is too large. Maximum size is {$maxMb} MB.";
            } else {
                $extension = strtolower(pathinfo(basename($_FILES['assignment_file']['name']), PATHINFO_EXTENSION));
                $allowed = ['pdf', 'doc', 'docx', 'zip', 'txt'];

                if (!in_array($extension, $allowed, true)) {
                    $error = 'Allowed file types: PDF, DOC, DOCX, ZIP and TXT.';
                } else {
                    $dir = '../uploads/assignment_submissions/';
                    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
                        $error = 'Unable to create the upload directory.';
                    } else {
                        $name = sprintf('submission_%d_%d_%s.%s', $studentId, $assignmentId, bin2hex(random_bytes(8)), $extension);
                        $newPhysicalFile = $dir . $name;
                        if (!move_uploaded_file($_FILES['assignment_file']['tmp_name'], $newPhysicalFile)) {
                            $error = 'Unable to save the new file.';
                            $newPhysicalFile = null;
                        } else {
                            $filePath = $newPhysicalFile;
                        }
                    }
                }
            }
        }

        if ($error === '') {
            $type = $submission['submission_type'];
            if ($type === 'file' && !$filePath) {
                $error = 'This assignment requires a file.';
            } elseif ($type === 'text' && $textAnswer === '') {
                $error = 'This assignment requires a text answer.';
            } elseif ($type === 'both' && $textAnswer === '' && !$filePath) {
                $error = 'Enter a text answer or upload a file.';
            }
        }

        if ($error === '') {
            try {
                $status = $duePassed ? 'Late' : 'Submitted';
                $update = $conn->prepare("
                    UPDATE assignment_submissions
                    SET text_answer = ?,
                        file_url = ?,
                        submission_status = ?,
                        submission_date = CURRENT_TIMESTAMP
                    WHERE submission_id = ?
                      AND student_id = ?
                      AND submission_status <> 'Graded'
                ");
                $update->bind_param("sssii", $textAnswer, $filePath, $status, $submissionId, $studentId);
                $update->execute();

                if ($update->affected_rows < 0) {
                    throw new RuntimeException('Unable to update submission.');
                }
                $update->close();

                if ($newPhysicalFile && !empty($submission['file_url'])) {
                    $oldFile = canonicalSubmissionPath($submission['file_url']);
                    if ($oldFile && file_exists($oldFile) && is_file($oldFile)) {
                        @unlink($oldFile);
                    }
                }

                $_SESSION['assignment_success'] = 'Assignment submission updated successfully.';
                header("Location: assignment_details.php?id=" . $assignmentId);
                exit();
            } catch (Throwable $e) {
                if ($newPhysicalFile && file_exists($newPhysicalFile)) {
                    @unlink($newPhysicalFile);
                }
                $error = 'The submission could not be updated. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnFlow | Edit Submission</title>
    <link rel="stylesheet" href="../css/student.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="dashboard-shell">
    <aside class="sidebar">
        <div class="brand-panel"><div class="brand-icon"><i class="fas fa-graduation-cap"></i></div><div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Student Portal</p></div></div>
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
        <header class="topbar"><div class="topbar-left"><button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Assignments</p><h1>Edit Submission</h1></div></div><div class="topbar-right"><div class="project-pill">LearnFlow</div><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?= e($studentName) ?></strong></div></div></div></header>

        <main class="dashboard-main">
            <?php if ($error !== ''): ?><div style="margin-bottom:20px;padding:14px 16px;border-radius:12px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;"><i class="fas fa-circle-exclamation"></i> <?= e($error) ?></div><?php endif; ?>

            <section class="assignment-detail-section">
                <div class="assignment-detail-header"><div><p class="card-label"><?= e($submission['course_name']) ?> / <?= e($submission['batch_name']) ?></p><h2><?= e($submission['title']) ?></h2><p>Update your submission before the assignment becomes unavailable.</p></div><a href="assignment_details.php?id=<?= $assignmentId ?>" class="secondary-btn"><i class="fas fa-arrow-left"></i>Cancel</a></div>

                <form method="POST" enctype="multipart/form-data" class="assignment-submit-form">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <?php if (in_array($submission['submission_type'], ['text', 'both'], true)): ?>
                        <div><label for="submission_text"><strong>Text Answer</strong></label><textarea id="submission_text" name="submission_text" class="assignment-textarea" rows="9"><?= e($submission['text_answer'] ?? '') ?></textarea></div>
                    <?php endif; ?>

                    <?php if (!empty($submission['file_url'])): ?>
                        <div class="current-submission-file"><div><i class="fas fa-file"></i></div><div><strong>Current file</strong><p><?= e(basename($submission['file_url'])) ?></p></div></div>
                    <?php endif; ?>

                    <?php if (in_array($submission['submission_type'], ['file', 'both'], true)): ?>
                        <div class="assignment-upload-box"><i class="fas fa-cloud-arrow-up"></i><div><strong>Replace File</strong><p>Leave empty to keep the current file. PDF, DOC, DOCX, ZIP or TXT.</p></div><input type="file" name="assignment_file" accept=".pdf,.doc,.docx,.zip,.txt"></div>
                    <?php endif; ?>

                    <div class="form-actions"><a href="assignment_details.php?id=<?= $assignmentId ?>" class="secondary-btn">Cancel</a><button type="submit" name="update_submission" class="primary-btn"><i class="fas fa-floppy-disk"></i>Save Changes</button></div>
                </form>
            </section>
        </main>
    </div>
</div>
<script src="../js/student.js"></script>
</body>
</html>
