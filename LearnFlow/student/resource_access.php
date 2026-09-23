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

$studentId = (int) $_SESSION['user_id'];
$resourceId = isset($_GET['resource_id']) ? (int) $_GET['resource_id'] : 0;
$action = strtolower(trim($_GET['action'] ?? 'view'));

if (
    $resourceId <= 0 ||
    !in_array($action, ['view', 'download', 'watch'], true)
) {
    header("Location: materials.php");
    exit();
}

/* ---------------------------------------------------------
   Load the resource and verify that:
   1. it is approved,
   2. the Student has an Active/Completed enrollment in the
      resource's course,
   3. paid tutes have actually been purchased.
   --------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        lr.resource_id,
        lr.title,
        lr.file_url,
        lr.resource_type,
        m.course_id,
        m.module_id,
        COALESCE(t.price, 0) AS tute_price,
        t.availability_status,
        CASE
            WHEN lr.resource_type <> 'tute' THEN 1
            WHEN COALESCE(t.price, 0) = 0 THEN 1
            WHEN EXISTS (
                SELECT 1
                FROM tute_purchases tp
                WHERE
                    tp.student_id = ?
                    AND tp.tute_resource_id = lr.resource_id
                    AND tp.purchase_status = 'completed'
            ) THEN 1
            ELSE 0
        END AS can_access
    FROM learning_resources lr
    INNER JOIN modules m
        ON m.module_id = lr.module_id
    LEFT JOIN tutes t
        ON t.resource_id = lr.resource_id
    WHERE
        lr.resource_id = ?
        AND lr.approval_status = 'approved'
        AND EXISTS (
            SELECT 1
            FROM enrollments e
            INNER JOIN batches b
                ON b.batch_id = e.batch_id
            WHERE
                e.student_id = ?
                AND b.course_id = m.course_id
                AND e.enrollment_status IN ('Active','Completed')
        )
    LIMIT 1
");

$stmt->bind_param(
    "iii",
    $studentId,
    $resourceId,
    $studentId
);

$stmt->execute();
$resource = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (
    !$resource ||
    (int) $resource['can_access'] !== 1
) {
    header("Location: materials.php");
    exit();
}

if (
    $resource['resource_type'] === 'tute' &&
    $resource['availability_status'] !== null &&
    $resource['availability_status'] !== 'available'
) {
    header("Location: materials.php");
    exit();
}

/* ---------------------------------------------------------
   Normalize action against resource type.
   --------------------------------------------------------- */
$accessType = $action;

if ($resource['resource_type'] === 'recording') {
    $accessType = 'watch';
} elseif ($action === 'watch') {
    $accessType = 'view';
}

/* ---------------------------------------------------------
   Validate the stored resource destination BEFORE recording
   access/progress. Broken or unsafe links must not count as
   completed learning activity.
   --------------------------------------------------------- */
$fileUrl = trim((string) $resource['file_url']);

if ($fileUrl === '') {
    header("Location: materials.php");
    exit();
}

$isRemoteUrl = preg_match('~^https?://~i', $fileUrl) === 1;

/* Reject unsafe/custom URL schemes. */
if (
    !$isRemoteUrl &&
    preg_match('~^[a-z][a-z0-9+.-]*:~i', $fileUrl)
) {
    header("Location: materials.php");
    exit();
}

$projectRoot = realpath(__DIR__ . '/..');
$localCandidate = false;
$cleanRelative = '';

if (!$isRemoteUrl) {
    if ($projectRoot === false) {
        header("Location: materials.php");
        exit();
    }

    $cleanRelative = ltrim(
        str_replace('\\', '/', $fileUrl),
        '/'
    );

    $localCandidate = realpath(
        $projectRoot .
        DIRECTORY_SEPARATOR .
        str_replace('/', DIRECTORY_SEPARATOR, $cleanRelative)
    );

    if (
        $localCandidate === false ||
        !is_file($localCandidate) ||
        !str_starts_with(
            $localCandidate,
            $projectRoot . DIRECTORY_SEPARATOR
        )
    ) {
        header("Location: materials.php");
        exit();
    }
}

/* ---------------------------------------------------------
   Write an access audit record only after the destination has
   passed validation.
   --------------------------------------------------------- */
$stmt = $conn->prepare("
    INSERT INTO resource_access_logs
    (
        student_id,
        resource_id,
        access_type,
        watched_seconds,
        completed
    )
    VALUES (?, ?, ?, NULL, TRUE)
");

$stmt->bind_param(
    "iis",
    $studentId,
    $resourceId,
    $accessType
);

$stmt->execute();
$stmt->close();

/* ---------------------------------------------------------
   Update Student module progress.
   Accessing an approved study material or recording counts as
   completing that resource once. Tutes are excluded because
   they belong to the optional store.
   --------------------------------------------------------- */
$moduleId = (int) $resource['module_id'];

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_resources
    FROM learning_resources
    WHERE module_id = ?
      AND approval_status = 'approved'
      AND resource_type IN ('study_material','recording')
");

$stmt->bind_param("i", $moduleId);
$stmt->execute();

$totalResources = (int) (
    $stmt->get_result()->fetch_assoc()['total_resources'] ?? 0
);

$stmt->close();

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT ral.resource_id) AS accessed_resources
    FROM resource_access_logs ral
    INNER JOIN learning_resources lr
        ON lr.resource_id = ral.resource_id
    WHERE
        ral.student_id = ?
        AND lr.module_id = ?
        AND lr.approval_status = 'approved'
        AND lr.resource_type IN ('study_material','recording')
        AND ral.completed = TRUE
");

$stmt->bind_param(
    "ii",
    $studentId,
    $moduleId
);

$stmt->execute();

$accessedResources = (int) (
    $stmt->get_result()->fetch_assoc()['accessed_resources'] ?? 0
);

$stmt->close();

$progressPercentage = $totalResources > 0
    ? min(
        100,
        round(
            ($accessedResources / $totalResources) * 100,
            2
        )
    )
    : 0.00;

$progressStatus = $progressPercentage >= 100
    ? 'completed'
    : (
        $progressPercentage > 0
            ? 'in_progress'
            : 'not_started'
    );

$stmt = $conn->prepare("
    INSERT INTO student_module_progress
    (
        student_id,
        module_id,
        progress_percentage,
        progress_status,
        last_accessed_at,
        completed_at
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        CURRENT_TIMESTAMP,
        IF(? = 'completed', CURRENT_TIMESTAMP, NULL)
    )
    ON DUPLICATE KEY UPDATE
        progress_percentage = VALUES(progress_percentage),
        progress_status = VALUES(progress_status),
        last_accessed_at = CURRENT_TIMESTAMP,
        completed_at = CASE
            WHEN VALUES(progress_status) = 'completed'
            THEN COALESCE(completed_at, CURRENT_TIMESTAMP)
            ELSE completed_at
        END
");

$stmt->bind_param(
    "iidss",
    $studentId,
    $moduleId,
    $progressPercentage,
    $progressStatus,
    $progressStatus
);

$stmt->execute();
$stmt->close();

/* ---------------------------------------------------------
   Remote URL such as an approved video/cloud resource.
   --------------------------------------------------------- */
if ($isRemoteUrl) {
    header("Location: " . $fileUrl);
    exit();
}

/* ---------------------------------------------------------
   Local file download.
   --------------------------------------------------------- */
if ($action === 'download') {
    $downloadName = basename($localCandidate);

    $mime = function_exists('mime_content_type')
        ? mime_content_type($localCandidate)
        : 'application/octet-stream';

    header('Content-Description: File Transfer');
    header(
        'Content-Type: ' .
        ($mime ?: 'application/octet-stream')
    );
    header(
        'Content-Disposition: attachment; filename="' .
        str_replace('"', '', $downloadName) .
        '"'
    );
    header(
        'Content-Length: ' .
        filesize($localCandidate)
    );
    header('Cache-Control: private, must-revalidate');

    readfile($localCandidate);
    exit();
}

/* ---------------------------------------------------------
   Local file view.
   Use only the already-validated project-relative path.
   --------------------------------------------------------- */
$relativePath = str_replace(
    '\\',
    '/',
    substr(
        $localCandidate,
        strlen($projectRoot) + 1
    )
);

$redirectUrl = '../' . $relativePath;

header("Location: " . $redirectUrl);
exit();
