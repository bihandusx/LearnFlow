<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: assignments.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$submissionId = (int) ($_POST['submission_id'] ?? 0);
$csrf = $_POST['csrf_token'] ?? '';

if (
    $submissionId <= 0 ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrf)
) {
    header("Location: assignments.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| LOAD THE STUDENT'S SUBMISSION
|--------------------------------------------------------------------------
| Only the logged-in Student's own submission is loaded.
| The Student must still belong to the assignment's batch.
*/

$stmt = $conn->prepare("
    SELECT
        sub.submission_id,
        sub.assignment_id,
        sub.file_url,
        sub.submission_status,

        a.open_date,
        a.close_date,
        a.status AS assessment_status,

        ass.due_date,
        ass.allow_late_submission,

        e.enrollment_status

    FROM assignment_submissions sub

    INNER JOIN assignments ass
        ON ass.assignment_id = sub.assignment_id

    INNER JOIN assessments a
        ON a.assessment_id = ass.assignment_id

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

/*
|--------------------------------------------------------------------------
| CHECK WHETHER DELETION IS STILL ALLOWED
|--------------------------------------------------------------------------
| A Student may delete only while the assignment is still accepting
| submissions. Graded submissions cannot be deleted.
*/

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

$canDelete =
    $submission['submission_status'] !== 'Graded' &&
    $submission['assessment_status'] === 'published' &&
    $submission['enrollment_status'] === 'Active' &&
    $openReached &&
    $closeNotReached &&
    (!$duePassed || (bool) $submission['allow_late_submission']);

if (!$canDelete) {
    $_SESSION['assignment_success'] =
        'This submission can no longer be deleted.';

    header(
        "Location: assignment_details.php?id=" .
        $assignmentId
    );
    exit();
}

/*
|--------------------------------------------------------------------------
| RESOLVE STORED FILE
|--------------------------------------------------------------------------
*/

$filePath = '';

if (!empty($submission['file_url'])) {
    $filePath =
        '../uploads/assignment_submissions/' .
        basename($submission['file_url']);
}

/*
|--------------------------------------------------------------------------
| DELETE SUBMISSION
|--------------------------------------------------------------------------
*/

try {
    $conn->begin_transaction();

    $delete = $conn->prepare("
        DELETE FROM assignment_submissions

        WHERE submission_id = ?
          AND student_id = ?
          AND submission_status <> 'Graded'
    ");

    $delete->bind_param(
        "ii",
        $submissionId,
        $studentId
    );

    $delete->execute();

    if ($delete->affected_rows !== 1) {
        $delete->close();
        throw new RuntimeException(
            'Submission was not deleted.'
        );
    }

    $delete->close();

    $conn->commit();

    /*
    |--------------------------------------------------------------------------
    | REMOVE PHYSICAL FILE AFTER DATABASE DELETE SUCCEEDS
    |--------------------------------------------------------------------------
    */

    if (
        $filePath !== '' &&
        file_exists($filePath) &&
        is_file($filePath)
    ) {
        @unlink($filePath);
    }

    $_SESSION['assignment_success'] =
        'Assignment submission deleted successfully.';

} catch (Throwable $e) {

    try {
        $conn->rollback();
    } catch (Throwable $ignored) {
        // Ignore rollback errors if the transaction is no longer active.
    }

    $_SESSION['assignment_success'] =
        'Unable to delete the assignment submission.';
}

header(
    "Location: assignment_details.php?id=" .
    $assignmentId
);

exit();
?>
