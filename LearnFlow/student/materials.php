<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function prettyType(string $type, ?string $materialType = null): string
{
    if ($type === 'study_material') {
        return $materialType ?: 'Study Material';
    }

    return match ($type) {
        'recording' => 'Recording',
        'tute' => 'Tute',
        default => ucwords(str_replace('_', ' ', $type)),
    };
}

function durationText(?int $seconds): string
{
    if (!$seconds || $seconds <= 0) {
        return 'Not specified';
    }

    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);

    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    }

    return max(1, $minutes) . ' min';
}

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
$batchId = isset($_GET['batch_id']) ? (int) $_GET['batch_id'] : 0;
$moduleId = isset($_GET['module_id']) ? (int) $_GET['module_id'] : 0;
$typeFilter = trim($_GET['type'] ?? '');
$search = trim($_GET['search'] ?? '');

$allowedTypes = ['', 'study_material', 'recording', 'tute'];
if (!in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = '';
}

/* ---------------------------------------------------------
   Student's enrolled courses for the filter dropdown
   --------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT DISTINCT
        c.course_id,
        c.course_name,
        s.subject_code
    FROM enrollments e
    INNER JOIN batches b
        ON b.batch_id = e.batch_id
    INNER JOIN courses c
        ON c.course_id = b.course_id
    LEFT JOIN subjects s
        ON s.subject_id = c.subject_id
    WHERE
        e.student_id = ?
        AND e.enrollment_status IN ('Active','Completed')
    ORDER BY c.course_name
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$courseOptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ---------------------------------------------------------
   Validate an optional course filter
   --------------------------------------------------------- */
if ($courseId > 0) {
    $stmt = $conn->prepare("
        SELECT 1
        FROM enrollments e
        INNER JOIN batches b
            ON b.batch_id = e.batch_id
        WHERE
            e.student_id = ?
            AND b.course_id = ?
            AND e.enrollment_status IN ('Active','Completed')
        LIMIT 1
    ");
    $stmt->bind_param("ii", $studentId, $courseId);
    $stmt->execute();
    $canViewCourse = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (!$canViewCourse) {
        $courseId = 0;
        $moduleId = 0;
    }
}

/* ---------------------------------------------------------
   Validate an optional module filter
   --------------------------------------------------------- */
if ($moduleId > 0) {
    $stmt = $conn->prepare("
        SELECT m.module_id, m.course_id
        FROM modules m
        WHERE m.module_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $moduleRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$moduleRow) {
        $moduleId = 0;
    } elseif ($courseId > 0 && (int) $moduleRow['course_id'] !== $courseId) {
        $moduleId = 0;
    }
}

/* ---------------------------------------------------------
   Overview counts across all approved resources in courses
   the student is enrolled in.
   --------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT lr.resource_id) AS total_resources,
        COUNT(DISTINCT CASE WHEN lr.resource_type = 'study_material' THEN lr.resource_id END) AS total_materials,
        COUNT(DISTINCT CASE WHEN lr.resource_type = 'recording' THEN lr.resource_id END) AS total_recordings
    FROM learning_resources lr
    INNER JOIN modules m
        ON m.module_id = lr.module_id
    WHERE
        lr.approval_status = 'approved'
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
");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$counts = $stmt->get_result()->fetch_assoc();
$stmt->close();

$totalResources = (int) ($counts['total_resources'] ?? 0);
$totalNotes = (int) ($counts['total_materials'] ?? 0);
$totalRecordings = (int) ($counts['total_recordings'] ?? 0);

/* ---------------------------------------------------------
   Load filtered approved resources
   --------------------------------------------------------- */
$stmt = $conn->prepare("
    SELECT
        lr.resource_id,
        lr.title,
        lr.description,
        lr.file_url,
        lr.upload_date,
        lr.resource_type,

        m.module_id,
        m.module_name,
        m.module_order,

        c.course_id,
        c.course_name,
        s.subject_code,

        uploader.fullname AS uploaded_by,

        sm.material_type,

        r.duration_seconds,
        r.recorded_date,

        t.price,
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
        END AS can_access,

        (
            SELECT COUNT(*)
            FROM resource_access_logs ral
            WHERE
                ral.student_id = ?
                AND ral.resource_id = lr.resource_id
        ) AS access_count

    FROM learning_resources lr

    INNER JOIN modules m
        ON m.module_id = lr.module_id

    INNER JOIN courses c
        ON c.course_id = m.course_id

    LEFT JOIN subjects s
        ON s.subject_id = c.subject_id

    INNER JOIN user_accounts uploader
        ON uploader.user_id = lr.uploaded_by_user_id

    LEFT JOIN study_materials sm
        ON sm.resource_id = lr.resource_id

    LEFT JOIN recordings r
        ON r.resource_id = lr.resource_id

    LEFT JOIN tutes t
        ON t.resource_id = lr.resource_id

    WHERE
        lr.approval_status = 'approved'

        AND EXISTS (
            SELECT 1
            FROM enrollments e
            INNER JOIN batches b
                ON b.batch_id = e.batch_id
            WHERE
                e.student_id = ?
                AND b.course_id = c.course_id
                AND e.enrollment_status IN ('Active','Completed')
        )

        AND (? = 0 OR c.course_id = ?)
        AND (? = 0 OR m.module_id = ?)
        AND (? = '' OR lr.resource_type = ?)

        AND (
            ? = ''
            OR lr.title LIKE CONCAT('%', ?, '%')
            OR COALESCE(lr.description, '') LIKE CONCAT('%', ?, '%')
            OR m.module_name LIKE CONCAT('%', ?, '%')
            OR c.course_name LIKE CONCAT('%', ?, '%')
        )

    ORDER BY
        c.course_name,
        m.module_order,
        lr.upload_date DESC,
        lr.resource_id DESC
");

$stmt->bind_param(
    "iiiiiiisssssss",
    $studentId,
    $studentId,
    $studentId,
    $courseId,
    $courseId,
    $moduleId,
    $moduleId,
    $typeFilter,
    $typeFilter,
    $search,
    $search,
    $search,
    $search,
    $search
);

$stmt->execute();
$resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($projectName); ?> | Learning Materials</title>
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
            <a href="materials.php" class="menu-link active"><i class="fas fa-folder-open"></i> Materials</a>
            <a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i> Assignments</a>
            <a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i> Quizzes</a>
            <a href="exams.php" class="menu-link"><i class="fas fa-clipboard-list"></i> Exams</a>
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
                    <p class="small-label">Materials</p>
                    <h1>Learning Materials</h1>
                </div>
            </div>

            <div class="topbar-right">
                <div class="project-pill">LearnFlow</div>
                <a href="notifications.php" class="icon-btn"><i class="fas fa-bell"></i></a>
                <div class="profile-chip">
                    <div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div>
                    <div>
                        <span>Hello,</span>
                        <strong><?php echo e($studentName); ?></strong>
                    </div>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <section class="overview-cards">
                <article class="summary-card">
                    <div>
                        <p class="card-label">Learning Resources</p>
                        <h2>Access approved resources from your enrolled courses.</h2>
                    </div>
                    <div class="summary-icon"><i class="fas fa-folder-open"></i></div>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Available Resources</p>
                        <h3><?php echo $totalResources; ?></h3>
                    </div>
                    <span class="stat-badge">Approved resources</span>
                </article>

                <article class="stat-card">
                    <div>
                        <p class="card-label">Recordings</p>
                        <h3><?php echo $totalRecordings; ?></h3>
                    </div>
                    <span class="stat-badge"><?php echo $totalNotes; ?> study materials</span>
                </article>
            </section>

            <section class="material-section">
                <div class="material-section-header">
                    <div>
                        <p class="card-label">Course Resources</p>
                        <h2>Browse Materials</h2>
                        <p>Only approved resources from courses you are enrolled in are displayed.</p>
                    </div>
                </div>

                <form method="GET" action="materials.php" class="material-filter-row">
                    <?php if ($batchId > 0): ?>
                        <input type="hidden" name="batch_id" value="<?php echo $batchId; ?>">
                    <?php endif; ?>

                    <div class="material-filter-group">
                        <label for="courseFilter">Course</label>
                        <select id="courseFilter" name="course_id">
                            <option value="0">All Courses</option>
                            <?php foreach ($courseOptions as $option): ?>
                                <option value="<?php echo (int) $option['course_id']; ?>"
                                    <?php echo $courseId === (int) $option['course_id'] ? 'selected' : ''; ?>>
                                    <?php echo e(($option['subject_code'] ? $option['subject_code'] . ' - ' : '') . $option['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="material-filter-group">
                        <label for="typeFilter">Resource Type</label>
                        <select id="typeFilter" name="type">
                            <option value="">All Types</option>
                            <option value="study_material" <?php echo $typeFilter === 'study_material' ? 'selected' : ''; ?>>Study Materials</option>
                            <option value="recording" <?php echo $typeFilter === 'recording' ? 'selected' : ''; ?>>Recordings</option>
                            <option value="tute" <?php echo $typeFilter === 'tute' ? 'selected' : ''; ?>>Tutes</option>
                        </select>
                    </div>

                    <div class="material-filter-group material-search-group">
                        <label for="materialSearch">Search</label>
                        <div class="material-search-box">
                            <i class="fas fa-search"></i>
                            <input type="text"
                                   id="materialSearch"
                                   name="search"
                                   value="<?php echo e($search); ?>"
                                   placeholder="Search materials">
                        </div>
                    </div>

                    <?php if ($moduleId > 0): ?>
                        <input type="hidden" name="module_id" value="<?php echo $moduleId; ?>">
                    <?php endif; ?>

                    <div class="form-actions" style="grid-column:1/-1; justify-content:flex-start; margin-top:0;">
                        <button class="primary-btn" type="submit">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a class="secondary-btn" href="materials.php<?php echo $courseId > 0 ? '?course_id=' . $courseId : ''; ?>">
                            <i class="fas fa-rotate-left"></i> Clear
                        </a>
                    </div>
                </form>
            </section>

            <section class="material-section">
                <div class="material-section-header">
                    <div>
                        <p class="card-label">Resources</p>
                        <h2>Available Materials</h2>
                        <p><?php echo count($resources); ?> resource(s) match the current filters.</p>
                    </div>
                </div>

                <?php if (empty($resources)): ?>
                    <div class="material-empty-state">
                        <div class="material-empty-icon"><i class="fas fa-folder-open"></i></div>
                        <h3>No Learning Materials Available</h3>
                        <p>Approved resources for your enrolled courses will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="card-grid">
                        <?php foreach ($resources as $resource): ?>
                            <?php
                            $resourceType = $resource['resource_type'];
                            $canAccess = (int) $resource['can_access'] === 1;

                            $icon = match ($resourceType) {
                                'recording' => 'fa-video',
                                'tute' => 'fa-file-pdf',
                                default => 'fa-file-lines',
                            };

                            $iconClass = match ($resourceType) {
                                'recording' => 'bg-purple',
                                'tute' => 'bg-green',
                                default => 'bg-blue',
                            };
                            ?>

                            <article class="dashboard-card">
                                <div class="card-icon <?php echo e($iconClass); ?>">
                                    <i class="fas <?php echo e($icon); ?>"></i>
                                </div>

                                <p class="card-label">
                                    <?php echo e(prettyType($resourceType, $resource['material_type'])); ?>
                                </p>

                                <h3><?php echo e($resource['title']); ?></h3>

                                <p><?php echo e($resource['description'] ?: 'No description provided.'); ?></p>

                                <p><strong>Course:</strong> <?php echo e($resource['course_name']); ?></p>
                                <p><strong>Module:</strong> <?php echo e($resource['module_name']); ?></p>
                                <p><strong>Uploaded by:</strong> <?php echo e($resource['uploaded_by']); ?></p>

                                <?php if ($resourceType === 'recording'): ?>
                                    <p><strong>Duration:</strong> <?php echo e(durationText($resource['duration_seconds'] !== null ? (int) $resource['duration_seconds'] : null)); ?></p>
                                    <p><strong>Recorded:</strong> <?php echo e($resource['recorded_date'] ?: 'Not specified'); ?></p>
                                <?php endif; ?>

                                <?php if ($resourceType === 'tute'): ?>
                                    <p><strong>Price:</strong> LKR <?php echo number_format((float) ($resource['price'] ?? 0), 2); ?></p>
                                <?php endif; ?>

                                <p><strong>Your accesses:</strong> <?php echo (int) $resource['access_count']; ?></p>

                                <div class="form-actions" style="justify-content:flex-start; flex-wrap:wrap;">
                                    <?php if ($canAccess): ?>
                                        <?php if ($resourceType === 'recording'): ?>
                                            <a class="primary-btn"
                                               href="resource_access.php?resource_id=<?php echo (int) $resource['resource_id']; ?>&action=watch"
                                               target="_blank"
                                               rel="noopener">
                                                <i class="fas fa-play"></i> Watch
                                            </a>
                                        <?php else: ?>
                                            <a class="primary-btn"
                                               href="resource_access.php?resource_id=<?php echo (int) $resource['resource_id']; ?>&action=view"
                                               target="_blank"
                                               rel="noopener">
                                                <i class="fas fa-eye"></i> View
                                            </a>

                                            <a class="secondary-btn"
                                               href="resource_access.php?resource_id=<?php echo (int) $resource['resource_id']; ?>&action=download">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a class="primary-btn" href="tute_store.php">
                                            <i class="fas fa-lock"></i> Purchase in Tute Store
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="card-grid">
                <a href="materials.php?type=study_material" class="dashboard-card">
                    <div class="card-icon bg-blue"><i class="fas fa-file-lines"></i></div>
                    <h3>Study Materials</h3>
                    <p>View lecture notes, references and other approved study resources.</p>
                </a>

                <a href="materials.php?type=tute" class="dashboard-card">
                    <div class="card-icon bg-green"><i class="fas fa-file-pdf"></i></div>
                    <h3>Tutes</h3>
                    <p>Access free or purchased digital tutes for your enrolled courses.</p>
                </a>

                <a href="materials.php?type=recording" class="dashboard-card">
                    <div class="card-icon bg-purple"><i class="fas fa-video"></i></div>
                    <h3>Recordings</h3>
                    <p>Watch approved recorded lessons provided by your teachers.</p>
                </a>
            </section>
        </main>
    </div>
</div>

<script src="../js/student.js"></script>
</body>
</html>
