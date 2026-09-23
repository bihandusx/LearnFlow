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
$assignmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($assignmentId <= 0) {
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

function safeStoredSubmissionPath(string $storedPath): ?string
{
    if ($storedPath === '') {
        return null;
    }

    $basename = basename($storedPath);
    if ($basename === '' || $basename === '.' || $basename === '..') {
        return null;
    }

    return '../uploads/assignment_submissions/' . $basename;
}

function uploadAssignmentFile(array $file, int $studentId, int $assignmentId, int $maxMb): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['path' => null, 'error' => 'The file upload failed.'];
    }

    $maxBytes = max(1, $maxMb) * 1024 * 1024;
    if ((int) $file['size'] > $maxBytes) {
        return ['path' => null, 'error' => "The file is too large. Maximum size is {$maxMb} MB."];
    }

    $originalName = basename((string) $file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'doc', 'docx', 'zip', 'txt'];

    if (!in_array($extension, $allowedExtensions, true)) {
        return ['path' => null, 'error' => 'Allowed file types: PDF, DOC, DOCX, ZIP and TXT.'];
    }

    $uploadDirectory = '../uploads/assignment_submissions/';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0775, true)) {
        return ['path' => null, 'error' => 'Unable to create the assignment upload directory.'];
    }

    $newFileName = sprintf(
        'submission_%d_%d_%s.%s',
        $studentId,
        $assignmentId,
        bin2hex(random_bytes(8)),
        $extension
    );

    $targetPath = $uploadDirectory . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['path' => null, 'error' => 'Unable to save the uploaded assignment file.'];
    }

    return ['path' => $targetPath, 'error' => null];
}

$stmt = $conn->prepare("
    SELECT
        a.assessment_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status AS assessment_status,

        ass.due_date,
        ass.instructions,
        ass.submission_type,
        ass.max_file_size_mb,
        ass.allow_late_submission,

        b.batch_name,
        c.course_id,
        c.course_name,
        teacher.fullname AS teacher_name,
        e.enrollment_status

    FROM assessments a
    INNER JOIN assignments ass ON ass.assignment_id = a.assessment_id
    INNER JOIN batches b ON b.batch_id = a.batch_id
    INNER JOIN courses c ON c.course_id = b.course_id
    INNER JOIN user_accounts teacher ON teacher.user_id = a.teacher_id
    INNER JOIN enrollments e
        ON e.batch_id = b.batch_id
       AND e.student_id = ?

    WHERE a.assessment_id = ?
      AND a.assessment_type = 'assignment'
      AND a.status IN ('published', 'closed')
      AND e.enrollment_status IN ('Active', 'Completed')
    LIMIT 1
");
$stmt->bind_param("ii", $studentId, $assignmentId);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$assignment) {
    header("Location: assignments.php");
    exit();
}

$submissionStmt = $conn->prepare("
    SELECT
        submission_id,
        assignment_id,
        student_id,
        file_url,
        text_answer,
        submission_date,
        submission_status,
        marks,
        feedback,
        graded_at
    FROM assignment_submissions
    WHERE assignment_id = ?
      AND student_id = ?
    LIMIT 1
");
$submissionStmt->bind_param("ii", $assignmentId, $studentId);
$submissionStmt->execute();
$currentSubmission = $submissionStmt->get_result()->fetch_assoc() ?: null;
$submissionStmt->close();

$successMessage = $_SESSION['assignment_success'] ?? '';
unset($_SESSION['assignment_success']);
$errorMessage = '';

$now = new DateTime();
$openReached = empty($assignment['open_date']) || $now >= new DateTime($assignment['open_date']);
$duePassed = !empty($assignment['due_date']) && $now > new DateTime($assignment['due_date']);
$allowLate = (bool) $assignment['allow_late_submission'];
$activeEnrollment = $assignment['enrollment_status'] === 'Active';
$published = $assignment['assessment_status'] === 'published';

$canSubmit = $activeEnrollment && $published && $openReached && (!$duePassed || $allowLate) && !$currentSubmission;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        $errorMessage = 'Invalid request. Please refresh the page and try again.';
    } elseif (!$canSubmit) {
        $errorMessage = 'This assignment is not currently accepting a new submission.';
    } else {
        $textAnswer = trim($_POST['submission_text'] ?? '');
        $submissionType = $assignment['submission_type'];
        $maxFileSizeMb = (int) ($assignment['max_file_size_mb'] ?: 10);
        $upload = uploadAssignmentFile($_FILES['assignment_file'] ?? [], $studentId, $assignmentId, $maxFileSizeMb);

        if ($upload['error']) {
            $errorMessage = $upload['error'];
        } else {
            $filePath = $upload['path'];

            if ($submissionType === 'file' && !$filePath) {
                $errorMessage = 'This assignment requires a file submission.';
            } elseif ($submissionType === 'text' && $textAnswer === '') {
                $errorMessage = 'This assignment requires a text answer.';
            } elseif ($submissionType === 'both' && $textAnswer === '' && !$filePath) {
                $errorMessage = 'Enter a text answer or upload a file.';
            } else {
                try {
                    $status = $duePassed ? 'Late' : 'Submitted';
                    $insert = $conn->prepare("
                        INSERT INTO assignment_submissions
                            (assignment_id, student_id, file_url, text_answer, submission_status)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $insert->bind_param("iisss", $assignmentId, $studentId, $filePath, $textAnswer, $status);
                    $insert->execute();
                    $insert->close();

                    $_SESSION['assignment_success'] = 'Assignment submitted successfully.';
                    header("Location: assignment_details.php?id=" . $assignmentId);
                    exit();
                } catch (Throwable $e) {
                    if ($filePath && file_exists($filePath)) {
                        @unlink($filePath);
                    }
                    $errorMessage = 'The assignment could not be submitted. Please try again.';
                }
            }
        }
    }
}

$maxFileSizeMb = (int) ($assignment['max_file_size_mb'] ?: 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnFlow | Assignment Details</title>
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
        <header class="topbar">
            <div class="topbar-left"><button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Assignments</p><h1>Assignment Details</h1></div></div>
            <div class="topbar-right"><div class="project-pill">LearnFlow</div><a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?= e($studentName) ?></strong></div></div></div>
        </header>

        <main class="dashboard-main">
            <?php if ($successMessage !== ''): ?>
                <div style="margin-bottom:20px;padding:14px 16px;border-radius:12px;background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;"><i class="fas fa-circle-check"></i> <?= e($successMessage) ?></div>
            <?php endif; ?>
            <?php if ($errorMessage !== ''): ?>
                <div style="margin-bottom:20px;padding:14px 16px;border-radius:12px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;"><i class="fas fa-circle-exclamation"></i> <?= e($errorMessage) ?></div>
            <?php endif; ?>

            <section class="assignment-detail-section">
                <div class="assignment-detail-header">
                    <div><p class="card-label"><?= e($assignment['course_name']) ?> / <?= e($assignment['batch_name']) ?></p><h2><?= e($assignment['title']) ?></h2><p>Teacher: <?= e($assignment['teacher_name']) ?></p></div>
                    <a href="assignments.php" class="secondary-btn"><i class="fas fa-arrow-left"></i>Back</a>
                </div>

                <div class="assignment-info-grid">
                    <div class="assignment-info-item"><div class="assignment-info-icon"><i class="fas fa-calendar-plus"></i></div><div><span>Opens</span><strong><?= e($assignment['open_date'] ?: 'Immediately') ?></strong></div></div>
                    <div class="assignment-info-item"><div class="assignment-info-icon"><i class="fas fa-calendar-xmark"></i></div><div><span>Due</span><strong><?= e($assignment['due_date']) ?></strong></div></div>
                    <div class="assignment-info-item"><div class="assignment-info-icon"><i class="fas fa-star"></i></div><div><span>Total Marks</span><strong><?= e($assignment['total_marks']) ?></strong></div></div>
                    <div class="assignment-info-item"><div class="assignment-info-icon"><i class="fas fa-upload"></i></div><div><span>Submission</span><strong><?= e(ucfirst($assignment['submission_type'])) ?></strong></div></div>
                </div>

                <div class="assignment-description-box"><h3>Description</h3><p><?= nl2br(e($assignment['description'] ?: 'No description provided.')) ?></p></div>
                <div class="assignment-description-box"><h3>Instructions</h3><p><?= nl2br(e($assignment['instructions'] ?: 'No additional instructions provided.')) ?></p></div>
            </section>

            <section class="assignment-detail-section" style="margin-top:24px;">
                <?php if ($currentSubmission): ?>
                    <div class="assignment-detail-header"><div><p class="card-label">Submission</p><h2>Your Submission</h2></div></div>
                    <div class="assignment-submitted-card">
                        <div class="assignment-submission-info">
                            <div><span>Status</span><strong><?= e($currentSubmission['submission_status']) ?></strong></div>
                            <div><span>Submitted</span><strong><?= e($currentSubmission['submission_date']) ?></strong></div>
                            <div><span>Marks</span><strong><?= $currentSubmission['marks'] !== null ? e($currentSubmission['marks']) . ' / ' . e($assignment['total_marks']) : 'Not graded yet' ?></strong></div>
                            <div><span>Feedback</span><strong><?= e($currentSubmission['feedback'] ?: 'No feedback yet') ?></strong></div>
                        </div>

                        <?php if (!empty($currentSubmission['text_answer'])): ?>
                            <div class="assignment-content-block"><p><?= nl2br(e($currentSubmission['text_answer'])) ?></p></div>
                        <?php endif; ?>

                        <?php if (!empty($currentSubmission['file_url'])): ?>
                            <?php $safeFile = safeStoredSubmissionPath($currentSubmission['file_url']); ?>
                            <?php if ($safeFile): ?>
                                <div class="assignment-file-area"><a class="secondary-btn" href="<?= e($safeFile) ?>" target="_blank" rel="noopener"><i class="fas fa-file-arrow-down"></i>Open Submitted File</a></div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php
                        $editable = $currentSubmission['submission_status'] !== 'Graded'
                            && $activeEnrollment
                            && $published
                            && (!$duePassed || $allowLate);
                        ?>

                        <?php if ($editable): ?>
                            <div class="assignment-crud-actions">
                                <a class="primary-btn" href="edit_submission.php?id=<?= (int) $currentSubmission['submission_id'] ?>"><i class="fas fa-pen"></i>Edit Submission</a>
                                <form method="POST" action="delete_submission.php" onsubmit="return confirm('Delete this assignment submission?');">
                                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="submission_id" value="<?= (int) $currentSubmission['submission_id'] ?>">
                                    <button type="submit" class="secondary-btn"><i class="fas fa-trash"></i>Delete Submission</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="assignment-detail-header"><div><p class="card-label">Submit Work</p><h2>Assignment Submission</h2><p>Maximum file size: <?= $maxFileSizeMb ?> MB.</p></div></div>

                    <?php if ($canSubmit): ?>
                        <form method="POST" enctype="multipart/form-data" class="assignment-submit-form">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                            <?php if (in_array($assignment['submission_type'], ['text', 'both'], true)): ?>
                                <div><label for="submission_text"><strong>Text Answer</strong></label><textarea id="submission_text" name="submission_text" class="assignment-textarea" rows="8" placeholder="Enter your answer here"></textarea></div>
                            <?php endif; ?>

                            <?php if (in_array($assignment['submission_type'], ['file', 'both'], true)): ?>
                                <div class="assignment-upload-box"><i class="fas fa-cloud-arrow-up"></i><div><strong>Upload Assignment File</strong><p>PDF, DOC, DOCX, ZIP or TXT. Max <?= $maxFileSizeMb ?> MB.</p></div><input type="file" name="assignment_file" accept=".pdf,.doc,.docx,.zip,.txt"></div>
                            <?php endif; ?>

                            <div class="form-actions"><button type="submit" name="submit_assignment" class="primary-btn"><i class="fas fa-paper-plane"></i>Submit Assignment</button></div>
                        </form>
                    <?php else: ?>
                        <div class="assignment-empty-state">
                            <div class="assignment-empty-icon"><i class="fas fa-lock"></i></div>
                            <h3>Submission Not Available</h3>
                            <p>
                                <?php
                                if (!$activeEnrollment) {
                                    echo 'Only actively enrolled students can submit work.';
                                } elseif (!$published) {
                                    echo 'This assignment is closed.';
                                } elseif (!$openReached) {
                                    echo 'This assignment has not opened yet.';
                                } elseif ($duePassed && !$allowLate) {
                                    echo 'The deadline has passed and late submissions are not allowed.';
                                } else {
                                    echo 'This assignment is not accepting submissions.';
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>
<script src="../js/student.js"></script>
</body>
</html>
