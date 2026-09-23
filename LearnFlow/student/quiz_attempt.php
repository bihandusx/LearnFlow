<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';
$success = $_SESSION['quiz_success'] ?? '';
unset($_SESSION['quiz_success']);

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';
$quizId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($quizId <= 0) {
    header("Location: quizzes.php");
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

/* --------------------------------------------------------------------------
   Load quiz and verify that it belongs to one of this student's batches.
   -------------------------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        a.assessment_id AS quiz_id,
        a.batch_id,
        a.title,
        a.description,
        a.open_date,
        a.close_date,
        a.total_marks,
        a.status AS assessment_status,
        a.teacher_id,

        q.duration_minutes,
        q.attempt_limit,
        q.passing_marks,
        q.randomize_questions,

        b.batch_name,
        c.course_id,
        c.course_name,
        e.enrollment_status,
        ua.fullname AS teacher_name,

        COUNT(DISTINCT qq.question_id) AS question_count

    FROM assessments a
    INNER JOIN quizzes q
        ON q.quiz_id = a.assessment_id
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
    LEFT JOIN quiz_questions qq
        ON qq.quiz_id = a.assessment_id
    WHERE
        a.assessment_id = ?
        AND a.assessment_type = 'quiz'
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
        q.duration_minutes,
        q.attempt_limit,
        q.passing_marks,
        q.randomize_questions,
        b.batch_name,
        c.course_id,
        c.course_name,
        e.enrollment_status,
        ua.fullname
    LIMIT 1
");
$stmt->bind_param("ii", $studentId, $quizId);
$stmt->execute();
$quiz = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$quiz) {
    header("Location: quizzes.php");
    exit();
}

$now = time();
$openTimestamp = $quiz['open_date'] ? strtotime($quiz['open_date']) : null;
$closeTimestamp = $quiz['close_date'] ? strtotime($quiz['close_date']) : null;
$isBeforeOpen = $openTimestamp !== null && $now < $openTimestamp;
$isAfterClose = $closeTimestamp !== null && $now > $closeTimestamp;
$isOpenForAttempts =
    $quiz['assessment_status'] === 'published' &&
    !$isBeforeOpen &&
    !$isAfterClose &&
    $quiz['enrollment_status'] === 'Active';

$isSubmitRequest =
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['submit_quiz']);

$submissionGraceSeconds = 5;

function calculateQuizAttemptExpiry(
    array $quiz,
    array $attempt,
    ?int $closeTimestamp
): int {
    $startedAt = strtotime($attempt['started_at']);
    $durationExpiry =
        $startedAt + ((int) $quiz['duration_minutes'] * 60);

    if ($closeTimestamp !== null) {
        return min($durationExpiry, $closeTimestamp);
    }

    return $durationExpiry;
}

/* --------------------------------------------------------------------------
   Find current in-progress attempt. If its timer has expired, consume it as
   abandoned. This prevents unlimited time by closing/reopening the browser.
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
$stmt->bind_param("ii", $quizId, $studentId);
$stmt->execute();
$activeAttempt = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($activeAttempt) {
    $expiresAt = calculateQuizAttemptExpiry(
        $quiz,
        $activeAttempt,
        $closeTimestamp
    );

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
        $stmt->bind_param(
            "iii",
            $expiredAttemptId,
            $studentId,
            $quizId
        );
        $stmt->execute();
        $stmt->close();

        $activeAttempt = null;
        $error = 'Your previous quiz attempt expired and has been closed.';
    }
}

/* --------------------------------------------------------------------------
   Attempt history / count.
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
$stmt->bind_param("ii", $quizId, $studentId);
$stmt->execute();
$attemptHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$attemptsUsed = count($attemptHistory);
$remainingAttempts = max(0, (int) $quiz['attempt_limit'] - $attemptsUsed);

/* --------------------------------------------------------------------------
   Start or resume attempt.
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_quiz'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif ($activeAttempt) {
        header("Location: quiz_attempt.php?id=" . $quizId);
        exit();
    } elseif (!$isOpenForAttempts) {
        $error = 'This quiz is not currently available for a new attempt.';
    } elseif ($remainingAttempts <= 0) {
        $error = 'You have used all allowed attempts for this quiz.';
    } elseif ((int) $quiz['question_count'] <= 0) {
        $error = 'This quiz has no questions yet.';
    } else {
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("
                SELECT COALESCE(MAX(attempt_number), 0) + 1 AS next_attempt
                FROM assessment_attempts
                WHERE assessment_id = ? AND student_id = ?
                FOR UPDATE
            ");
            $stmt->bind_param("ii", $quizId, $studentId);
            $stmt->execute();
            $nextAttempt = (int) $stmt->get_result()->fetch_assoc()['next_attempt'];
            $stmt->close();

            if ($nextAttempt > (int) $quiz['attempt_limit']) {
                throw new RuntimeException('You have used all allowed attempts for this quiz.');
            }

            $stmt = $conn->prepare("
                INSERT INTO assessment_attempts
                    (assessment_id, student_id, attempt_number, attempt_status)
                VALUES
                    (?, ?, ?, 'in_progress')
            ");
            $stmt->bind_param("iii", $quizId, $studentId, $nextAttempt);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            header("Location: quiz_attempt.php?id=" . $quizId);
            exit();

        } catch (Throwable $e) {
            $conn->rollback();
            $error = $e instanceof RuntimeException
                ? $e->getMessage()
                : 'The quiz attempt could not be started.';
        }
    }
}

/* --------------------------------------------------------------------------
   Reload active attempt after a successful start if this is a normal GET.
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
    $stmt->bind_param("ii", $quizId, $studentId);
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
            qq.question_order,
            qq.marks
        FROM quiz_questions qq
        INNER JOIN questions q
            ON q.question_id = qq.question_id
        WHERE qq.quiz_id = ?
        ORDER BY qq.question_order, q.question_id
    ");
    $stmt->bind_param("i", $quizId);
    $stmt->execute();
    $questions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ((bool) $quiz['randomize_questions']) {
        usort($questions, function ($a, $b) use ($attemptId) {
            $keyA = sprintf('%u', crc32($attemptId . ':' . $a['question_id']));
            $keyB = sprintf('%u', crc32($attemptId . ':' . $b['question_id']));
            return $keyA <=> $keyB;
        });
    }

    foreach ($questions as &$question) {
        $question['options'] = [];

        if (in_array($question['question_type'], ['mcq', 'true_false'], true)) {
            $stmt = $conn->prepare("
                SELECT option_id, option_text
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

    $expiresAt = calculateQuizAttemptExpiry(
        $quiz,
        $activeAttempt,
        $closeTimestamp
    );
    $secondsLeft = max(0, $expiresAt - time());
}

/* --------------------------------------------------------------------------
   Submit active attempt.
   - MCQ and true/false are auto-graded.
   - Short answer and essay answers stay pending for teacher grading.
   -------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid request. Please refresh the page and try again.';
    } elseif (!$activeAttempt) {
        $error = 'There is no active quiz attempt to submit.';
    } elseif ($quiz['assessment_status'] !== 'published') {
        $error = 'This quiz is no longer open for submission.';
    } elseif ($quiz['enrollment_status'] !== 'Active') {
        $error = 'Your enrollment is no longer active.';
    } else {
        $attemptExpiry = calculateQuizAttemptExpiry(
            $quiz,
            $activeAttempt,
            $closeTimestamp
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
                $quizId
            );
            $stmt->execute();
            $stmt->close();

            $activeAttempt = null;
            $error =
                'The quiz time limit has expired. This attempt can no longer be submitted.';
        } else {
            try {
                $attemptId = (int) $activeAttempt['attempt_id'];
                $conn->begin_transaction();

            $stmt = $conn->prepare("
                SELECT
                    q.question_id,
                    q.question_type,
                    qq.marks
                FROM quiz_questions qq
                INNER JOIN questions q ON q.question_id = qq.question_id
                WHERE qq.quiz_id = ?
                ORDER BY qq.question_order
            ");
            $stmt->bind_param("i", $quizId);
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

                    $awardedMarks = 0.0;
                    $validSelectedOptionId = null;

                    if ($selectedOptionId > 0) {
                        $stmt = $conn->prepare("
                            SELECT is_correct
                            FROM question_options
                            WHERE option_id = ? AND question_id = ?
                            LIMIT 1
                        ");
                        $stmt->bind_param("ii", $selectedOptionId, $questionId);
                        $stmt->execute();
                        $option = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        if (!$option) {
                            throw new RuntimeException('An invalid answer option was submitted.');
                        }

                        $validSelectedOptionId = $selectedOptionId;
                        if ((int) $option['is_correct'] === 1) {
                            $awardedMarks = $marks;
                        }
                    }

                    $automaticScore += $awardedMarks;

                    if ($validSelectedOptionId === null) {
                        $stmt = $conn->prepare("
                            INSERT INTO attempt_answers
                                (attempt_id, question_id, selected_option_id, answer_text, awarded_marks)
                            VALUES
                                (?, ?, NULL, NULL, ?)
                            ON DUPLICATE KEY UPDATE
                                selected_option_id = NULL,
                                answer_text = NULL,
                                awarded_marks = VALUES(awarded_marks),
                                feedback = NULL
                        ");
                        $stmt->bind_param("iid", $attemptId, $questionId, $awardedMarks);
                    } else {
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
                        $stmt->bind_param("iiid", $attemptId, $questionId, $validSelectedOptionId, $awardedMarks);
                    }

                    $stmt->execute();
                    $stmt->close();

                } else {
                    $answerText = trim((string) ($_POST[$fieldName] ?? ''));

                    if ($answerText === '') {
                        $zeroMarks = 0.0;
                        $stmt = $conn->prepare("
                            INSERT INTO attempt_answers
                                (attempt_id, question_id, selected_option_id, answer_text, awarded_marks)
                            VALUES
                                (?, ?, NULL, NULL, ?)
                            ON DUPLICATE KEY UPDATE
                                selected_option_id = NULL,
                                answer_text = NULL,
                                awarded_marks = VALUES(awarded_marks),
                                feedback = NULL
                        ");
                        $stmt->bind_param("iid", $attemptId, $questionId, $zeroMarks);
                    } else {
                        $manualGradingRequired = true;
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
                        $stmt->bind_param("iis", $attemptId, $questionId, $answerText);
                    }

                    $stmt->execute();
                    $stmt->close();
                }
            }

            if ($manualGradingRequired) {
                $attemptStatus = 'submitted';
                $grade = 'Pending';
            } else {
                $attemptStatus = 'graded';
                $grade = $automaticScore >= (float) $quiz['passing_marks'] ? 'Pass' : 'Fail';
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
                $quizId
            );
            $stmt->execute();

            if ($stmt->affected_rows !== 1) {
                throw new RuntimeException('This attempt was already submitted or is no longer active.');
            }
            $stmt->close();

            $conn->commit();

            $_SESSION['quiz_success'] = $manualGradingRequired
                ? 'Quiz submitted successfully. Written answers are waiting for teacher grading.'
                : 'Quiz submitted and automatically graded.';

            header("Location: quiz_attempt.php?id=" . $quizId);
            exit();

            } catch (Throwable $e) {
                $conn->rollback();
                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : 'The quiz could not be submitted. Please try again.';
            }
        }
    }
}

/* --------------------------------------------------------------------------
   Final refresh of history after POST redirects / normal GET.
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
$stmt->bind_param("ii", $quizId, $studentId);
$stmt->execute();
$attemptHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$attemptsUsed = count($attemptHistory);
$remainingAttempts = max(0, (int) $quiz['attempt_limit'] - $attemptsUsed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | Quiz Attempt</title>
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
            <div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Student Portal</p></div>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a>
            <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a>
            <a href="materials.php" class="menu-link"><i class="fas fa-folder-open"></i>Materials</a>
            <a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i>Assignments</a>
            <a href="quizzes.php" class="menu-link active"><i class="fas fa-check-square"></i>Quizzes</a>
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
            <div class="topbar-left">
                <button class="mobile-menu-btn" type="button"><i class="fas fa-bars"></i></button>
                <div class="dashboard-title"><p class="small-label">Quizzes</p><h1>Quiz Attempt</h1></div>
            </div>
            <div class="topbar-right">
                <div class="project-pill">LearnFlow</div>
                <a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a>
                <div class="profile-chip">
                    <div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div>
                    <div><span>Hello,</span><strong><?php echo e($studentName); ?></strong></div>
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
                    <div><p class="card-label"><?php echo e($quiz['course_name']); ?> · <?php echo e($quiz['batch_name']); ?></p><h2><?php echo e($quiz['title']); ?></h2></div>
                    <div class="summary-icon"><i class="fas fa-list-check"></i></div>
                </article>
                <article class="stat-card"><div><p class="card-label">Questions</p><h3><?php echo (int) $quiz['question_count']; ?></h3></div><span class="stat-badge">Quiz questions</span></article>
                <article class="stat-card"><div><p class="card-label">Duration</p><h3><?php echo (int) $quiz['duration_minutes']; ?> min</h3></div><span class="stat-badge">Per attempt</span></article>
                <article class="stat-card"><div><p class="card-label">Attempts Left</p><h3><?php echo $remainingAttempts; ?></h3></div><span class="stat-badge"><?php echo $attemptsUsed; ?> used</span></article>
            </section>

            <section class="course-detail-section">
                <div class="course-detail-header">
                    <div>
                        <p class="card-label">Quiz Information</p>
                        <h2><?php echo e($quiz['title']); ?></h2>
                        <p><?php echo e($quiz['description'] ?: 'No description provided.'); ?></p>
                    </div>
                    <a href="quizzes.php" class="secondary-btn"><i class="fas fa-arrow-left"></i>Back to Quizzes</a>
                </div>

                <div class="course-info-grid">
                    <div class="course-info-item"><div class="course-info-icon"><i class="fas fa-chalkboard-teacher"></i></div><div><span>Teacher</span><strong><?php echo e($quiz['teacher_name'] ?: 'Not assigned'); ?></strong></div></div>
                    <div class="course-info-item"><div class="course-info-icon"><i class="fas fa-star"></i></div><div><span>Total Marks</span><strong><?php echo e($quiz['total_marks']); ?></strong></div></div>
                    <div class="course-info-item"><div class="course-info-icon"><i class="fas fa-circle-check"></i></div><div><span>Passing Marks</span><strong><?php echo e($quiz['passing_marks']); ?></strong></div></div>
                    <div class="course-info-item"><div class="course-info-icon"><i class="fas fa-rotate"></i></div><div><span>Attempt Limit</span><strong><?php echo (int) $quiz['attempt_limit']; ?></strong></div></div>
                    <div class="course-info-item"><div class="course-info-icon"><i class="fas fa-calendar-plus"></i></div><div><span>Opens</span><strong><?php echo e(formatDateTimeValue($quiz['open_date'])); ?></strong></div></div>
                    <div class="course-info-item"><div class="course-info-icon"><i class="fas fa-calendar-xmark"></i></div><div><span>Closes</span><strong><?php echo e(formatDateTimeValue($quiz['close_date'])); ?></strong></div></div>
                </div>
            </section>

            <?php if ($activeAttempt): ?>
                <section class="course-detail-section" style="margin-top:24px;">
                    <div class="course-detail-header">
                        <div>
                            <p class="card-label">Attempt <?php echo (int) $activeAttempt['attempt_number']; ?></p>
                            <h2>Answer the Questions</h2>
                            <p>Submit before the timer reaches zero.</p>
                        </div>
                        <div class="project-pill" id="quizTimer" data-seconds-left="<?php echo (int) $secondsLeft; ?>">
                            Time Left: --:--
                        </div>
                    </div>

                    <form method="POST" action="quiz_attempt.php?id=<?php echo $quizId; ?>" id="quizForm">
                        <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="submit_quiz" value="1">

                        <?php foreach ($questions as $index => $question): ?>
                            <?php
                            $qid = (int) $question['question_id'];
                            $saved = $existingAnswers[$qid] ?? null;
                            ?>
                            <article class="dashboard-card" style="margin-bottom:18px;">
                                <p class="card-label">Question <?php echo $index + 1; ?> · <?php echo e($question['marks']); ?> mark(s)</p>
                                <h3><?php echo nl2br(e($question['question_text'])); ?></h3>

                                <?php if (in_array($question['question_type'], ['mcq', 'true_false'], true)): ?>
                                    <div style="display:grid;gap:10px;margin-top:16px;">
                                        <?php foreach ($question['options'] as $option): ?>
                                            <label style="display:flex;gap:10px;align-items:flex-start;padding:12px;border:1px solid #e5e7eb;border-radius:12px;cursor:pointer;">
                                                <input type="radio"
                                                       name="q_<?php echo $qid; ?>"
                                                       value="<?php echo (int) $option['option_id']; ?>"
                                                       <?php echo $saved && (int) $saved['selected_option_id'] === (int) $option['option_id'] ? 'checked' : ''; ?>>
                                                <span><?php echo e($option['option_text']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif ($question['question_type'] === 'short_answer'): ?>
                                    <div class="form-group" style="margin-top:16px;">
                                        <label for="q_<?php echo $qid; ?>">Your Answer</label>
                                        <div class="input-wrapper">
                                            <i class="fas fa-pen"></i>
                                            <input type="text"
                                                   id="q_<?php echo $qid; ?>"
                                                   name="q_<?php echo $qid; ?>"
                                                   value="<?php echo e($saved['answer_text'] ?? ''); ?>"
                                                   placeholder="Enter your answer">
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="form-group" style="margin-top:16px;">
                                        <label for="q_<?php echo $qid; ?>">Your Answer</label>
                                        <textarea id="q_<?php echo $qid; ?>"
                                                  name="q_<?php echo $qid; ?>"
                                                  rows="6"
                                                  style="width:100%;padding:14px;border:1px solid #dfe4ea;border-radius:14px;"
                                                  placeholder="Write your answer here"><?php echo e($saved['answer_text'] ?? ''); ?></textarea>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>

                        <div class="form-actions">
                            <button type="submit" class="primary-btn" onclick="return confirm('Submit this quiz attempt? You cannot edit it after submission.');">
                                <i class="fas fa-paper-plane"></i>Submit Quiz
                            </button>
                        </div>
                    </form>
                </section>

            <?php else: ?>
                <section class="course-detail-section" style="margin-top:24px;">
                    <div class="course-detail-header">
                        <div>
                            <p class="card-label">Attempt</p>
                            <h2>Start Quiz</h2>
                            <?php if ($isBeforeOpen): ?>
                                <p>This quiz has not opened yet.</p>
                            <?php elseif ($isAfterClose || $quiz['assessment_status'] === 'closed'): ?>
                                <p>This quiz is closed.</p>
                            <?php elseif ($quiz['enrollment_status'] !== 'Active'): ?>
                                <p>Your enrollment is not active, so a new attempt cannot be started.</p>
                            <?php elseif ($remainingAttempts <= 0): ?>
                                <p>You have used all allowed attempts.</p>
                            <?php elseif ((int) $quiz['question_count'] <= 0): ?>
                                <p>The teacher has not added questions to this quiz yet.</p>
                            <?php else: ?>
                                <p>Once started, the <?php echo (int) $quiz['duration_minutes']; ?> minute timer begins immediately.</p>
                            <?php endif; ?>
                        </div>

                        <?php if ($isOpenForAttempts && $remainingAttempts > 0 && (int) $quiz['question_count'] > 0): ?>
                            <form method="POST" action="quiz_attempt.php?id=<?php echo $quizId; ?>">
                                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                                <button type="submit" name="start_quiz" class="primary-btn" onclick="return confirm('Start this timed quiz attempt now?');">
                                    <i class="fas fa-play"></i>Start Quiz
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="course-detail-section" style="margin-top:24px;">
                <div class="course-detail-header">
                    <div><p class="card-label">History</p><h2>My Attempts</h2><p>Your attempt history for this quiz.</p></div>
                </div>

                <?php if (empty($attemptHistory)): ?>
                    <div class="course-empty-state">
                        <div class="course-empty-icon"><i class="fas fa-clock-rotate-left"></i></div>
                        <h3>No Attempts Yet</h3>
                        <p>You have not attempted this quiz.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($attemptHistory as $attempt): ?>
                            <article class="dashboard-card">
                                <div class="card-icon bg-purple"><i class="fas fa-chart-simple"></i></div>
                                <p class="card-label">Attempt <?php echo (int) $attempt['attempt_number']; ?></p>
                                <h3><?php echo e(ucwords(str_replace('_', ' ', $attempt['attempt_status']))); ?></h3>
                                <p><strong>Started:</strong> <?php echo e(formatDateTimeValue($attempt['started_at'])); ?></p>
                                <p><strong>Submitted:</strong> <?php echo e(formatDateTimeValue($attempt['submitted_at'])); ?></p>
                                <p><strong>Score:</strong> <?php echo $attempt['score'] !== null ? e($attempt['score']) . ' / ' . e($quiz['total_marks']) : 'Pending'; ?></p>
                                <p><strong>Result:</strong> <?php echo e($attempt['grade'] ?: 'Pending'); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</div>

<script src="../js/student.js"></script>
<?php if ($activeAttempt): ?>
<script>
(function () {
    const timer = document.getElementById('quizTimer');
    const form = document.getElementById('quizForm');
    if (!timer || !form) return;

    let seconds = parseInt(timer.dataset.secondsLeft || '0', 10);

    function render() {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        timer.textContent = 'Time Left: ' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    render();

    if (seconds <= 0) {
        form.submit();
        return;
    }

    const interval = setInterval(function () {
        seconds--;
        render();

        if (seconds <= 0) {
            clearInterval(interval);
            form.submit();
        }
    }, 1000);
})();
</script>
<?php endif; ?>
</body>
</html>
