<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';
$success = $_SESSION['exam_success'] ?? '';
unset($_SESSION['exam_success']);

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';
$examId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($examId <= 0) {
    header("Location: exams.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

function formatExamTime(?string $value): string
{
    if (!$value) {
        return 'Not specified';
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('h:i A', $timestamp) : $value;
}

/* --------------------------------------------------------------------------
   Load exam and verify that the logged-in Student belongs to its batch.
   -------------------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        a.assessment_id AS exam_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status AS assessment_status,
        a.teacher_id,

        ex.exam_date,
        ex.start_time,
        ex.end_time,
        ex.venue,
        ex.duration_minutes,
        ex.attempt_limit,
        ex.passing_marks,

        b.batch_name,
        c.course_id,
        c.course_name,
        e.enrollment_status,
        ua.fullname AS teacher_name,

        COUNT(DISTINCT eq.question_id) AS question_count

    FROM assessments a

    INNER JOIN exams ex
        ON ex.exam_id = a.assessment_id

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

    LEFT JOIN exam_questions eq
        ON eq.exam_id = a.assessment_id

    WHERE
        a.assessment_id = ?
        AND a.assessment_type = 'exam'
        AND a.status IN ('published', 'closed')

    GROUP BY
        a.assessment_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status,
        a.teacher_id,
        ex.exam_date,
        ex.start_time,
        ex.end_time,
        ex.venue,
        ex.duration_minutes,
        ex.attempt_limit,
        ex.passing_marks,
        b.batch_name,
        c.course_id,
        c.course_name,
        e.enrollment_status,
        ua.fullname

    LIMIT 1
");
$stmt->bind_param("ii", $studentId, $examId);
$stmt->execute();
$exam = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exam) {
    header("Location: exams.php");
    exit();
}

/* --------------------------------------------------------------------------
   Effective examination window.
   -------------------------------------------------------------------------- */
$scheduledStart = strtotime($exam['exam_date'] . ' ' . $exam['start_time']);
$scheduledEnd = strtotime($exam['exam_date'] . ' ' . $exam['end_time']);

$effectiveStart = $scheduledStart;
$effectiveEnd = $scheduledEnd;

if ($exam['open_date']) {
    $assessmentOpen = strtotime($exam['open_date']);
    if ($assessmentOpen && $assessmentOpen > $effectiveStart) {
        $effectiveStart = $assessmentOpen;
    }
}

if ($exam['close_date']) {
    $assessmentClose = strtotime($exam['close_date']);
    if ($assessmentClose && $assessmentClose < $effectiveEnd) {
        $effectiveEnd = $assessmentClose;
    }
}

$now = time();
$beforeExam = $now < $effectiveStart;
$afterExam = $now > $effectiveEnd;
$isPublished = $exam['assessment_status'] === 'published';
$isEnrollmentActive = $exam['enrollment_status'] === 'Active';
$isSubmitRequest = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam']);

/*
|--------------------------------------------------------------------------
| SERVER-SIDE SUBMISSION GRACE PERIOD
|--------------------------------------------------------------------------
| Allows a few seconds for the browser's automatic timer submission to reach
| the server, while still preventing submissions long after the exam expires.
*/
$submissionGraceSeconds = 5;

/* --------------------------------------------------------------------------
   Find in-progress attempt.
   -------------------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        attempt_id,
        attempt_number,
        started_at
    FROM assessment_attempts
    WHERE
        assessment_id = ?
        AND student_id = ?
        AND attempt_status = 'in_progress'
    ORDER BY attempt_number DESC
    LIMIT 1
");
$stmt->bind_param("ii", $examId, $studentId);
$stmt->execute();
$activeAttempt = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* --------------------------------------------------------------------------
   Calculate an attempt deadline. The Student can never continue beyond the
   official exam end time. duration_minutes can make the attempt shorter.
   -------------------------------------------------------------------------- */
function calculateAttemptExpiry(array $exam, array $attempt, int $effectiveEnd): int
{
    $startedAt = strtotime($attempt['started_at']);
    $durationMinutes = (int) ($exam['duration_minutes'] ?? 0);

    if ($durationMinutes > 0) {
        return min($effectiveEnd, $startedAt + ($durationMinutes * 60));
    }

    return $effectiveEnd;
}

if ($activeAttempt) {
    $expiresAt = calculateAttemptExpiry($exam, $activeAttempt, $effectiveEnd);

    if (
        !$isSubmitRequest &&
        $now > ($expiresAt + $submissionGraceSeconds)
    ) {
        $expiredAttemptId = (int) $activeAttempt['attempt_id'];

        $stmt = $conn->prepare("
            UPDATE assessment_attempts
            SET
                attempt_status = 'abandoned',
                submitted_at = NOW(),
                grade = 'Time Expired'
            WHERE
                attempt_id = ?
                AND student_id = ?
                AND assessment_id = ?
                AND attempt_status = 'in_progress'
        ");
        $stmt->bind_param("iii", $expiredAttemptId, $studentId, $examId);
        $stmt->execute();
        $stmt->close();

        $activeAttempt = null;
        $error = 'Your previous exam attempt reached its time limit and was closed.';
    }
}

/* --------------------------------------------------------------------------
   Attempt history.
   -------------------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        attempt_id,
        attempt_number,
        started_at,
        submitted_at,
        score,
        grade,
        attempt_status
    FROM assessment_attempts
    WHERE
        assessment_id = ?
        AND student_id = ?
    ORDER BY attempt_number DESC
");
$stmt->bind_param("ii", $examId, $studentId);
$stmt->execute();
$attemptHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$attemptsUsed = count($attemptHistory);
$remainingAttempts = max(0, (int) $exam['attempt_limit'] - $attemptsUsed);

$isOpenForNewAttempt =
    $isPublished &&
    $isEnrollmentActive &&
    !$beforeExam &&
    !$afterExam &&
    $remainingAttempts > 0;

/* --------------------------------------------------------------------------
   Start exam.
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_exam'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif ($activeAttempt) {
        header("Location: exam_attempt.php?id=" . $examId);
        exit();
    } elseif (!$isPublished) {
        $error = 'This exam is not currently published.';
    } elseif (!$isEnrollmentActive) {
        $error = 'Only actively enrolled students can start this exam.';
    } elseif ($beforeExam) {
        $error = 'This exam has not started yet.';
    } elseif ($afterExam) {
        $error = 'The examination window has already closed.';
    } elseif ($remainingAttempts <= 0) {
        $error = 'You have used all allowed attempts for this exam.';
    } elseif ((int) $exam['question_count'] <= 0) {
        $error = 'This exam does not contain any questions yet.';
    } else {
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("
                SELECT COALESCE(MAX(attempt_number), 0) + 1 AS next_attempt
                FROM assessment_attempts
                WHERE assessment_id = ? AND student_id = ?
                FOR UPDATE
            ");
            $stmt->bind_param("ii", $examId, $studentId);
            $stmt->execute();
            $nextAttempt = (int) $stmt->get_result()->fetch_assoc()['next_attempt'];
            $stmt->close();

            if ($nextAttempt > (int) $exam['attempt_limit']) {
                throw new RuntimeException('You have used all allowed attempts for this exam.');
            }

            $stmt = $conn->prepare("
                INSERT INTO assessment_attempts
                    (assessment_id, student_id, attempt_number, attempt_status)
                VALUES
                    (?, ?, ?, 'in_progress')
            ");
            $stmt->bind_param("iii", $examId, $studentId, $nextAttempt);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            header("Location: exam_attempt.php?id=" . $examId);
            exit();

        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'The exam attempt could not be started.';
        }
    }
}

/* --------------------------------------------------------------------------
   Reload active attempt after start.
   -------------------------------------------------------------------------- */
if (!$activeAttempt) {
    $stmt = $conn->prepare("
        SELECT
            attempt_id,
            attempt_number,
            started_at
        FROM assessment_attempts
        WHERE
            assessment_id = ?
            AND student_id = ?
            AND attempt_status = 'in_progress'
        ORDER BY attempt_number DESC
        LIMIT 1
    ");
    $stmt->bind_param("ii", $examId, $studentId);
    $stmt->execute();
    $activeAttempt = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$questions = [];
$existingAnswers = [];
$secondsLeft = 0;

if ($activeAttempt) {
    $attemptId = (int) $activeAttempt['attempt_id'];

    $stmt = $conn->prepare("
        SELECT
            q.question_id,
            q.question_text,
            q.question_type,
            eq.question_order,
            eq.marks
        FROM exam_questions eq
        INNER JOIN questions q
            ON q.question_id = eq.question_id
        WHERE eq.exam_id = ?
        ORDER BY eq.question_order, q.question_id
    ");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($questions as &$question) {
        $question['options'] = [];

        if (in_array($question['question_type'], ['mcq', 'true_false'], true)) {
            $stmt = $conn->prepare("
                SELECT
                    option_id,
                    option_text
                FROM question_options
                WHERE question_id = ?
                ORDER BY option_id
            ");
            $questionId = (int) $question['question_id'];
            $stmt->bind_param("i", $questionId);
            $stmt->execute();
            $question['options'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
    unset($question);

    $stmt = $conn->prepare("
        SELECT
            question_id,
            selected_option_id,
            answer_text
        FROM attempt_answers
        WHERE attempt_id = ?
    ");
    $stmt->bind_param("i", $attemptId);
    $stmt->execute();
    $answerRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($answerRows as $answer) {
        $existingAnswers[(int) $answer['question_id']] = $answer;
    }

    $expiresAt = calculateAttemptExpiry($exam, $activeAttempt, $effectiveEnd);
    $secondsLeft = max(0, $expiresAt - time());
}

/* --------------------------------------------------------------------------
   Submit exam.
   MCQ / true-false => auto-graded.
   short-answer / essay => saved for Teacher grading.
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif (!$activeAttempt) {
        $error = 'There is no active exam attempt to submit.';
    } elseif (!$isPublished) {
        $error = 'This exam is no longer open for submission.';
    } elseif (!$isEnrollmentActive) {
        $error = 'Your enrollment is no longer active.';
    } else {
        $attemptExpiry = calculateAttemptExpiry(
            $exam,
            $activeAttempt,
            $effectiveEnd
        );

        if (time() > ($attemptExpiry + $submissionGraceSeconds)) {
            $expiredAttemptId = (int) $activeAttempt['attempt_id'];

            $stmt = $conn->prepare("
                UPDATE assessment_attempts
                SET
                    attempt_status = 'abandoned',
                    submitted_at = NOW(),
                    grade = 'Time Expired'
                WHERE
                    attempt_id = ?
                    AND student_id = ?
                    AND assessment_id = ?
                    AND attempt_status = 'in_progress'
            ");
            $stmt->bind_param(
                "iii",
                $expiredAttemptId,
                $studentId,
                $examId
            );
            $stmt->execute();
            $stmt->close();

            $activeAttempt = null;
            $error = 'The examination time limit has expired. This attempt can no longer be submitted.';
        } else {
            try {
                $attemptId = (int) $activeAttempt['attempt_id'];
                $conn->begin_transaction();

            $stmt = $conn->prepare("
                SELECT
                    q.question_id,
                    q.question_type,
                    eq.marks
                FROM exam_questions eq
                INNER JOIN questions q
                    ON q.question_id = eq.question_id
                WHERE eq.exam_id = ?
                ORDER BY eq.question_order
            ");
            $stmt->bind_param("i", $examId);
            $stmt->execute();
            $submissionQuestions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            $automaticScore = 0.0;
            $manualGradingRequired = false;

            foreach ($submissionQuestions as $question) {
                $questionId = (int) $question['question_id'];
                $marks = (float) $question['marks'];
                $fieldName = 'q_' . $questionId;

                if (in_array($question['question_type'], ['mcq', 'true_false'], true)) {
                    $selectedOptionId = isset($_POST[$fieldName]) && $_POST[$fieldName] !== ''
                        ? (int) $_POST[$fieldName]
                        : 0;

                    $validOptionId = null;
                    $awardedMarks = 0.0;

                    if ($selectedOptionId > 0) {
                        $stmt = $conn->prepare("
                            SELECT
                                option_id,
                                is_correct
                            FROM question_options
                            WHERE
                                option_id = ?
                                AND question_id = ?
                            LIMIT 1
                        ");
                        $stmt->bind_param("ii", $selectedOptionId, $questionId);
                        $stmt->execute();
                        $option = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        if ($option) {
                            $validOptionId = (int) $option['option_id'];
                            if ((int) $option['is_correct'] === 1) {
                                $awardedMarks = $marks;
                                $automaticScore += $marks;
                            }
                        }
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO attempt_answers
                            (attempt_id, question_id, selected_option_id, answer_text, awarded_marks)
                        VALUES
                            (?, ?, ?, NULL, ?)
                        ON DUPLICATE KEY UPDATE
                            selected_option_id = VALUES(selected_option_id),
                            answer_text = NULL,
                            awarded_marks = VALUES(awarded_marks),
                            feedback = NULL
                    ");
                    $stmt->bind_param("iiid", $attemptId, $questionId, $validOptionId, $awardedMarks);
                    $stmt->execute();
                    $stmt->close();

                } else {
                    $manualGradingRequired = true;
                    $answerText = trim((string) ($_POST[$fieldName] ?? ''));
                    $answerValue = $answerText !== '' ? $answerText : null;

                    $stmt = $conn->prepare("
                        INSERT INTO attempt_answers
                            (attempt_id, question_id, selected_option_id, answer_text, awarded_marks)
                        VALUES
                            (?, ?, NULL, ?, NULL)
                        ON DUPLICATE KEY UPDATE
                            selected_option_id = NULL,
                            answer_text = VALUES(answer_text),
                            awarded_marks = NULL,
                            feedback = NULL
                    ");
                    $stmt->bind_param("iis", $attemptId, $questionId, $answerValue);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            if ($manualGradingRequired) {
                $grade = 'Pending';
                $attemptStatus = 'submitted';
            } else {
                $grade = $automaticScore >= (float) $exam['passing_marks'] ? 'Pass' : 'Fail';
                $attemptStatus = 'graded';
            }

            $stmt = $conn->prepare("
                UPDATE assessment_attempts
                SET
                    submitted_at = NOW(),
                    score = ?,
                    grade = ?,
                    attempt_status = ?
                WHERE
                    attempt_id = ?
                    AND student_id = ?
                    AND assessment_id = ?
                    AND attempt_status = 'in_progress'
            ");
            $stmt->bind_param(
                "dssiii",
                $automaticScore,
                $grade,
                $attemptStatus,
                $attemptId,
                $studentId,
                $examId
            );
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                $stmt->close();
                throw new RuntimeException('This exam attempt is no longer active.');
            }
            $stmt->close();

            $conn->commit();

            $_SESSION['exam_success'] = $manualGradingRequired
                ? 'Exam submitted successfully. Written answers are awaiting teacher grading.'
                : 'Exam submitted and graded successfully.';

            header("Location: exam_attempt.php?id=" . $examId);
            exit();

            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'The exam could not be submitted. Please try again.';
            }
        }
    }
}

/* --------------------------------------------------------------------------
   Refresh history after a completed submission.
   -------------------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        attempt_id,
        attempt_number,
        started_at,
        submitted_at,
        score,
        grade,
        attempt_status
    FROM assessment_attempts
    WHERE
        assessment_id = ?
        AND student_id = ?
    ORDER BY attempt_number DESC
");
$stmt->bind_param("ii", $examId, $studentId);
$stmt->execute();
$attemptHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$attemptsUsed = count($attemptHistory);
$remainingAttempts = max(0, (int) $exam['attempt_limit'] - $attemptsUsed);

$latestAttempt = $attemptHistory[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | <?php echo e($exam['title']); ?></title>
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
            <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="profile.php" class="menu-link"><i class="fas fa-user"></i> Profile</a>
            <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i> Courses</a>
            <a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i> Materials</a>
            <a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i> Assignments</a>
            <a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i> Quizzes</a>
            <a href="exams.php" class="menu-link active"><i class="fas fa-clipboard-list"></i> Exams</a>
            <a href="grades.php" class="menu-link"><i class="fas fa-chart-column"></i> Grades</a>
            <a href="progress.php" class="menu-link"><i class="fas fa-chart-line"></i> Progress</a>
            <a href="attendance.php" class="menu-link"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="schedule.php" class="menu-link"><i class="fas fa-calendar-days"></i> Schedule</a>
            <a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="discussions.php" class="menu-link"><i class="fas fa-comments"></i> Discussions</a>
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
                    <p class="small-label">Examination</p>
                    <h1><?php echo e($exam['title']); ?></h1>
                </div>
            </div>
            <div class="topbar-right">
                <div class="project-pill">LearnFlow</div>
                <div class="profile-chip">
                    <div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div>
                    <div><span>Hello,</span> <strong><?php echo e($studentName); ?></strong></div>
                </div>
            </div>
        </header>

        <main class="dashboard-main">

            <?php if ($success !== ''): ?>
                <div style="margin-bottom:20px;padding:14px 16px;border-radius:12px;background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;">
                    <i class="fas fa-circle-check"></i> <?php echo e($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div style="margin-bottom:20px;padding:14px 16px;border-radius:12px;background:#fef2f2;color:#991b1b;border:1px solid #fecaca;">
                    <i class="fas fa-circle-exclamation"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <section class="overview-cards">
                <article class="summary-card">
                    <div>
                        <p class="card-label"><?php echo e($exam['course_name']); ?></p>
                        <h2><?php echo e($exam['title']); ?></h2>
                    </div>
                    <div class="summary-icon"><i class="fas fa-clipboard-list"></i></div>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">Questions</p><h3><?php echo (int) $exam['question_count']; ?></h3></div>
                    <span class="stat-badge">Exam questions</span>
                </article>

                <article class="stat-card">
                    <div><p class="card-label">Total Marks</p><h3><?php echo e($exam['total_marks']); ?></h3></div>
                    <span class="stat-badge">Pass: <?php echo e($exam['passing_marks']); ?></span>
                </article>
            </section>

            <section class="exam-section">
                <div class="exam-section-header">
                    <div>
                        <p class="card-label">Exam Information</p>
                        <h2><?php echo e($exam['title']); ?></h2>
                        <p><?php echo e($exam['description'] ?: 'No additional description has been provided.'); ?></p>
                    </div>
                    <a href="exams.php" class="secondary-btn"><i class="fas fa-arrow-left"></i> Back to Exams</a>
                </div>

                <div class="course-info-grid">
                    <div class="course-info-item">
                        <div class="course-info-icon"><i class="fas fa-layer-group"></i></div>
                        <div><span>Batch</span><strong><?php echo e($exam['batch_name']); ?></strong></div>
                    </div>
                    <div class="course-info-item">
                        <div class="course-info-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div><span>Teacher</span><strong><?php echo e($exam['teacher_name'] ?: 'Not assigned'); ?></strong></div>
                    </div>
                    <div class="course-info-item">
                        <div class="course-info-icon"><i class="fas fa-calendar"></i></div>
                        <div><span>Date</span><strong><?php echo e(date('d M Y', strtotime($exam['exam_date']))); ?></strong></div>
                    </div>
                    <div class="course-info-item">
                        <div class="course-info-icon"><i class="fas fa-clock"></i></div>
                        <div><span>Scheduled Time</span><strong><?php echo e(formatExamTime($exam['start_time'])); ?> - <?php echo e(formatExamTime($exam['end_time'])); ?></strong></div>
                    </div>
                    <div class="course-info-item">
                        <div class="course-info-icon"><i class="fas fa-location-dot"></i></div>
                        <div><span>Venue</span><strong><?php echo e($exam['venue'] ?: 'Online / Not specified'); ?></strong></div>
                    </div>
                    <div class="course-info-item">
                        <div class="course-info-icon"><i class="fas fa-repeat"></i></div>
                        <div><span>Attempts</span><strong><?php echo $attemptsUsed; ?> / <?php echo (int) $exam['attempt_limit']; ?></strong></div>
                    </div>
                </div>
            </section>

            <?php if ($activeAttempt): ?>
                <section class="exam-section">
                    <div class="exam-section-header">
                        <div>
                            <p class="card-label">Active Attempt</p>
                            <h2>Attempt <?php echo (int) $activeAttempt['attempt_number']; ?></h2>
                            <p>Submit before the timer reaches zero. The examination also closes at the official end time.</p>
                        </div>
                        <div class="project-pill" id="examTimer" style="font-size:18px;">
                            Time Left: --:--
                        </div>
                    </div>

                    <form method="POST" action="exam_attempt.php?id=<?php echo $examId; ?>" id="examForm">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">

                        <?php foreach ($questions as $index => $question): ?>
                            <?php
                            $questionId = (int) $question['question_id'];
                            $fieldName = 'q_' . $questionId;
                            $saved = $existingAnswers[$questionId] ?? null;
                            ?>

                            <article class="dashboard-card" style="margin-bottom:20px;">
                                <p class="card-label">
                                    Question <?php echo $index + 1; ?> · <?php echo e(ucwords(str_replace('_', ' ', $question['question_type']))); ?> · <?php echo e($question['marks']); ?> mark(s)
                                </p>
                                <h3><?php echo nl2br(e($question['question_text'])); ?></h3>

                                <?php if (in_array($question['question_type'], ['mcq', 'true_false'], true)): ?>
                                    <div style="display:grid;gap:10px;margin-top:16px;">
                                        <?php foreach ($question['options'] as $option): ?>
                                            <label style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid #e5e7eb;border-radius:12px;cursor:pointer;">
                                                <input
                                                    type="radio"
                                                    name="<?php echo e($fieldName); ?>"
                                                    value="<?php echo (int) $option['option_id']; ?>"
                                                    <?php echo $saved && (int) $saved['selected_option_id'] === (int) $option['option_id'] ? 'checked' : ''; ?>
                                                >
                                                <span><?php echo e($option['option_text']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                    <div class="form-group" style="margin-top:16px;">
                                        <label for="<?php echo e($fieldName); ?>">Your Answer</label>
                                        <textarea
                                            id="<?php echo e($fieldName); ?>"
                                            name="<?php echo e($fieldName); ?>"
                                            rows="4"
                                            placeholder="Enter your answer"
                                        ><?php echo e($saved['answer_text'] ?? ''); ?></textarea>
                                    </div>

                                <?php else: ?>
                                    <div class="form-group" style="margin-top:16px;">
                                        <label for="<?php echo e($fieldName); ?>">Your Essay Answer</label>
                                        <textarea
                                            id="<?php echo e($fieldName); ?>"
                                            name="<?php echo e($fieldName); ?>"
                                            rows="8"
                                            placeholder="Write your answer"
                                        ><?php echo e($saved['answer_text'] ?? ''); ?></textarea>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>

                        <div class="form-actions">
                            <button
                                type="submit"
                                name="submit_exam"
                                class="primary-btn"
                                onclick="return confirm('Submit this exam now? You will not be able to edit your answers after submission.');"
                            >
                                <i class="fas fa-paper-plane"></i> Submit Exam
                            </button>
                        </div>
                    </form>
                </section>

            <?php else: ?>
                <section class="exam-section">
                    <div class="exam-section-header">
                        <div>
                            <p class="card-label">Exam Access</p>
                            <h2>
                                <?php
                                if ($beforeExam) {
                                    echo 'Exam Not Started';
                                } elseif ($afterExam) {
                                    echo 'Exam Window Closed';
                                } elseif ($remainingAttempts <= 0) {
                                    echo 'No Attempts Remaining';
                                } else {
                                    echo 'Ready to Start';
                                }
                                ?>
                            </h2>
                            <p>
                                Effective exam window:
                                <?php echo e(date('d M Y, h:i A', $effectiveStart)); ?>
                                to
                                <?php echo e(date('d M Y, h:i A', $effectiveEnd)); ?>.
                            </p>
                        </div>
                    </div>

                    <?php if ($isOpenForNewAttempt && (int) $exam['question_count'] > 0): ?>
                        <form method="POST" action="exam_attempt.php?id=<?php echo $examId; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                            <button
                                type="submit"
                                name="start_exam"
                                class="primary-btn"
                                onclick="return confirm('Start the examination now? The timer begins immediately.');"
                            >
                                <i class="fas fa-play"></i> Start Exam
                            </button>
                        </form>
                    <?php elseif ($beforeExam): ?>
                        <p>This exam becomes available at the scheduled start time.</p>
                    <?php elseif ($afterExam): ?>
                        <p>The scheduled examination period has ended.</p>
                    <?php elseif (!$isEnrollmentActive && empty($attemptHistory)): ?>
                        <p>Your enrollment is not active, so a new examination attempt cannot be started.</p>
                    <?php elseif ($remainingAttempts <= 0): ?>
                        <p>You have already used the allowed number of attempts.</p>
                    <?php elseif ((int) $exam['question_count'] <= 0): ?>
                        <p>The exam does not contain questions yet.</p>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="exam-section">
                <div class="exam-section-header">
                    <div>
                        <p class="card-label">Results</p>
                        <h2>Attempt History</h2>
                        <p>Completed online exam attempts are retained as academic records.</p>
                    </div>
                </div>

                <?php if (empty($attemptHistory)): ?>
                    <div class="exam-empty-state">
                        <div class="exam-empty-icon"><i class="fas fa-clock-rotate-left"></i></div>
                        <h3>No Exam Attempts Yet</h3>
                        <p>Your attempt history will appear here after you start the exam.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($attemptHistory as $attempt): ?>
                            <article class="dashboard-card">
                                <div class="card-icon bg-purple"><i class="fas fa-file-circle-check"></i></div>
                                <p class="card-label">Attempt <?php echo (int) $attempt['attempt_number']; ?></p>
                                <h3><?php echo e(ucwords(str_replace('_', ' ', $attempt['attempt_status']))); ?></h3>
                                <p><strong>Started:</strong> <?php echo e(formatDateTimeValue($attempt['started_at'])); ?></p>
                                <p><strong>Submitted:</strong> <?php echo e(formatDateTimeValue($attempt['submitted_at'])); ?></p>
                                <p>
                                    <strong>Score:</strong>
                                    <?php echo $attempt['score'] !== null ? e($attempt['score']) . ' / ' . e($exam['total_marks']) : 'Pending'; ?>
                                </p>
                                <p><strong>Result:</strong> <?php echo e($attempt['grade'] ?: 'Pending'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<?php if ($activeAttempt): ?>
<script>
(function () {
    let secondsLeft = <?php echo (int) $secondsLeft; ?>;
    const timer = document.getElementById('examTimer');
    const form = document.getElementById('examForm');
    let submitted = false;

    function renderTimer() {
        const minutes = Math.floor(secondsLeft / 60);
        const seconds = secondsLeft % 60;
        timer.textContent = 'Time Left: ' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
    }

    renderTimer();

    const interval = setInterval(function () {
        if (secondsLeft <= 0) {
            clearInterval(interval);

            if (!submitted && form) {
                submitted = true;
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'submit_exam';
                hidden.value = '1';
                form.appendChild(hidden);
                form.submit();
            }
            return;
        }

        secondsLeft--;
        renderTimer();
    }, 1000);

    form.addEventListener('submit', function () {
        submitted = true;
    });
})();
</script>
<?php endif; ?>

<script src="../js/student.js"></script>
</body>
</html>
