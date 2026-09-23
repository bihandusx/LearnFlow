<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';
$success = $_SESSION['tute_success'] ?? '';
unset($_SESSION['tute_success']);

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function money($amount): string
{
    return number_format((float) $amount, 2);
}

function prettyStatus(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/*
|--------------------------------------------------------------------------
| PURCHASE / GET FREE TUTE
|--------------------------------------------------------------------------
|
| Paid tutes:
|   - create a pending tute_purchases row
|   - payment will be handled in Step 15 (payments.php)
|
| Free tutes:
|   - immediately mark the purchase as completed
|   - the student can download it using resource_access.php
|
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase_tute'])) {

    $csrfToken = $_POST['csrf_token'] ?? '';
    $resourceId = (int) ($_POST['resource_id'] ?? 0);

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {

        $error = 'Invalid request. Please refresh the page and try again.';

    } elseif ($resourceId <= 0) {

        $error = 'Invalid tute selected.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | LOAD THE TUTE AND VERIFY STUDENT ACCESS
            |--------------------------------------------------------------------------
            |
            | A student may purchase only an approved, available tute belonging
            | to a course in which they have an Active or Completed enrollment.
            |
            */

            $stmt = $conn->prepare("
                SELECT
                    lr.resource_id,
                    lr.title,
                    lr.approval_status,

                    t.price,
                    t.availability_status,

                    m.module_id,
                    c.course_id,
                    c.course_name

                FROM learning_resources lr

                INNER JOIN tutes t
                    ON t.resource_id = lr.resource_id

                INNER JOIN modules m
                    ON m.module_id = lr.module_id

                INNER JOIN courses c
                    ON c.course_id = m.course_id

                WHERE
                    lr.resource_id = ?
                    AND lr.resource_type = 'tute'
                    AND lr.approval_status = 'approved'
                    AND t.availability_status = 'available'

                    AND EXISTS
                    (
                        SELECT 1

                        FROM enrollments e

                        INNER JOIN batches b
                            ON b.batch_id = e.batch_id

                        WHERE
                            e.student_id = ?
                            AND b.course_id = c.course_id
                            AND e.enrollment_status IN
                            (
                                'Active',
                                'Completed'
                            )
                    )

                LIMIT 1
            ");

            $stmt->bind_param(
                "ii",
                $resourceId,
                $studentId
            );

            $stmt->execute();

            $tute = $stmt
                ->get_result()
                ->fetch_assoc();

            $stmt->close();

            if (!$tute) {
                throw new RuntimeException(
                    'This tute is not available for your enrolled courses.'
                );
            }

            $price = (float) $tute['price'];

            /*
            |--------------------------------------------------------------------------
            | CHECK EXISTING PURCHASE
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT
                    purchase_id,
                    purchase_status,
                    amount

                FROM tute_purchases

                WHERE
                    student_id = ?
                    AND tute_resource_id = ?

                LIMIT 1
            ");

            $stmt->bind_param(
                "ii",
                $studentId,
                $resourceId
            );

            $stmt->execute();

            $existingPurchase = $stmt
                ->get_result()
                ->fetch_assoc();

            $stmt->close();

            /*
            |--------------------------------------------------------------------------
            | ALREADY OWNED
            |--------------------------------------------------------------------------
            */

            if (
                $existingPurchase &&
                $existingPurchase['purchase_status'] === 'completed'
            ) {
                throw new RuntimeException(
                    'You already own this tute.'
                );
            }

            $conn->begin_transaction();

            /*
            |--------------------------------------------------------------------------
            | FREE TUTE
            |--------------------------------------------------------------------------
            */

            if ($price <= 0) {

                if ($existingPurchase) {

                    $purchaseId = (int) $existingPurchase['purchase_id'];

                    $stmt = $conn->prepare("
                        UPDATE tute_purchases

                        SET
                            purchase_status = 'completed',
                            purchase_date = CURRENT_TIMESTAMP,
                            amount = 0.00

                        WHERE
                            purchase_id = ?
                            AND student_id = ?
                    ");

                    $stmt->bind_param(
                        "ii",
                        $purchaseId,
                        $studentId
                    );

                    $stmt->execute();
                    $stmt->close();

                } else {

                    $stmt = $conn->prepare("
                        INSERT INTO tute_purchases
                        (
                            student_id,
                            tute_resource_id,
                            amount,
                            purchase_status
                        )

                        VALUES
                        (
                            ?,
                            ?,
                            0.00,
                            'completed'
                        )
                    ");

                    $stmt->bind_param(
                        "ii",
                        $studentId,
                        $resourceId
                    );

                    $stmt->execute();

                    $purchaseId = $conn->insert_id;

                    $stmt->close();
                }

                $conn->commit();

                $_SESSION['tute_success'] =
                    'The free tute "' .
                    $tute['title'] .
                    '" has been added to your account.';

                header("Location: tute_store.php");
                exit();
            }

            /*
            |--------------------------------------------------------------------------
            | PAID TUTE
            |--------------------------------------------------------------------------
            */

            if ($existingPurchase) {

                $purchaseId = (int) $existingPurchase['purchase_id'];

                /*
                | A pending purchase already exists.
                | Do not create duplicate rows.
                */

                if ($existingPurchase['purchase_status'] === 'pending') {

                    $conn->commit();

                    $_SESSION['tute_success'] =
                        'You already have a pending purchase for "' .
                        $tute['title'] .
                        '". Continue from the Payments page.';

                    header(
                        "Location: payments.php?purchase_id=" .
                        $purchaseId
                    );
                    exit();
                }

                /*
                | Cancelled/refunded purchases can be started again.
                */

                $stmt = $conn->prepare("
                    UPDATE tute_purchases

                    SET
                        purchase_status = 'pending',
                        purchase_date = CURRENT_TIMESTAMP,
                        amount = ?

                    WHERE
                        purchase_id = ?
                        AND student_id = ?
                ");

                $stmt->bind_param(
                    "dii",
                    $price,
                    $purchaseId,
                    $studentId
                );

                $stmt->execute();
                $stmt->close();

            } else {

                $stmt = $conn->prepare("
                    INSERT INTO tute_purchases
                    (
                        student_id,
                        tute_resource_id,
                        amount,
                        purchase_status
                    )

                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        'pending'
                    )
                ");

                $stmt->bind_param(
                    "iid",
                    $studentId,
                    $resourceId,
                    $price
                );

                $stmt->execute();

                $purchaseId = $conn->insert_id;

                $stmt->close();
            }

            $conn->commit();

            $_SESSION['tute_success'] =
                'Purchase request created for "' .
                $tute['title'] .
                '". Complete the payment from the Payments page.';

            header(
                "Location: payments.php?purchase_id=" .
                $purchaseId
            );
            exit();

        } catch (RuntimeException $e) {

            if ($conn->errno === 0) {
                // No database error; this is a controlled validation message.
            }

            $error = $e->getMessage();

        } catch (Throwable $e) {

            try {
                $conn->rollback();
            } catch (Throwable $ignored) {
            }

            $error =
                'The purchase could not be completed. Please try again.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| FILTER VALUES
|--------------------------------------------------------------------------
*/

$selectedCourseId = isset($_GET['course_id'])
    ? (int) $_GET['course_id']
    : 0;

$selectedStatus = trim($_GET['status'] ?? 'all');
$searchText = trim($_GET['search'] ?? '');

$allowedStatuses = [
    'all',
    'available',
    'owned',
    'pending'
];

if (!in_array($selectedStatus, $allowedStatuses, true)) {
    $selectedStatus = 'all';
}

/*
|--------------------------------------------------------------------------
| LOAD COURSES FOR FILTER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT DISTINCT
        c.course_id,
        c.course_name

    FROM enrollments e

    INNER JOIN batches b
        ON b.batch_id = e.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    WHERE
        e.student_id = ?
        AND e.enrollment_status IN
        (
            'Active',
            'Completed'
        )

    ORDER BY c.course_name
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$courseOptions = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

/*
|--------------------------------------------------------------------------
| LOAD TUTES
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT DISTINCT

        lr.resource_id,
        lr.title,
        lr.description,
        lr.file_url,
        lr.upload_date,

        t.price,
        t.availability_status,

        m.module_id,
        m.module_name,

        c.course_id,
        c.course_name,

        tp.purchase_id,
        tp.purchase_date,
        tp.purchase_status,
        tp.amount AS purchase_amount,

        (
            SELECT p.payment_status

            FROM tute_payments tpay

            INNER JOIN payments p
                ON p.payment_id = tpay.payment_id

            WHERE
                tpay.purchase_id = tp.purchase_id
                AND p.student_id = ?

            ORDER BY p.payment_id DESC

            LIMIT 1
        ) AS latest_payment_status

    FROM learning_resources lr

    INNER JOIN tutes t
        ON t.resource_id = lr.resource_id

    INNER JOIN modules m
        ON m.module_id = lr.module_id

    INNER JOIN courses c
        ON c.course_id = m.course_id

    LEFT JOIN tute_purchases tp
        ON tp.student_id = ?
        AND tp.tute_resource_id = lr.resource_id

    WHERE
        lr.resource_type = 'tute'
        AND lr.approval_status = 'approved'
        AND t.availability_status = 'available'

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            INNER JOIN batches b
                ON b.batch_id = e.batch_id

            WHERE
                e.student_id = ?
                AND b.course_id = c.course_id
                AND e.enrollment_status IN
                (
                    'Active',
                    'Completed'
                )
        )
";

$params = [
    $studentId,
    $studentId,
    $studentId
];

$types = "iii";

if ($selectedCourseId > 0) {
    $sql .= " AND c.course_id = ? ";
    $params[] = $selectedCourseId;
    $types .= "i";
}

if ($selectedStatus === 'owned') {

    $sql .= "
        AND tp.purchase_status = 'completed'
    ";

} elseif ($selectedStatus === 'pending') {

    $sql .= "
        AND tp.purchase_status = 'pending'
    ";

} elseif ($selectedStatus === 'available') {

    $sql .= "
        AND
        (
            tp.purchase_id IS NULL
            OR tp.purchase_status IN
            (
                'cancelled',
                'refunded'
            )
        )
    ";
}

if ($searchText !== '') {

    $sql .= "
        AND
        (
            lr.title LIKE ?
            OR lr.description LIKE ?
            OR m.module_name LIKE ?
            OR c.course_name LIKE ?
        )
    ";

    $likeSearch = '%' . $searchText . '%';

    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;

    $types .= "ssss";
}

$sql .= "
    ORDER BY
        CASE
            WHEN tp.purchase_status = 'completed' THEN 1
            WHEN tp.purchase_status = 'pending' THEN 2
            ELSE 3
        END,
        c.course_name,
        lr.title
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    $types,
    ...$params
);

$stmt->execute();

$tutes = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT

        COUNT(DISTINCT lr.resource_id) AS total_tutes,

        COUNT(
            DISTINCT CASE
                WHEN tp.purchase_status = 'completed'
                THEN lr.resource_id
            END
        ) AS purchased_tutes,

        COUNT(
            DISTINCT CASE
                WHEN tp.purchase_status = 'pending'
                THEN lr.resource_id
            END
        ) AS pending_tutes

    FROM learning_resources lr

    INNER JOIN tutes t
        ON t.resource_id = lr.resource_id

    INNER JOIN modules m
        ON m.module_id = lr.module_id

    INNER JOIN courses c
        ON c.course_id = m.course_id

    LEFT JOIN tute_purchases tp
        ON tp.student_id = ?
        AND tp.tute_resource_id = lr.resource_id

    WHERE
        lr.resource_type = 'tute'
        AND lr.approval_status = 'approved'
        AND t.availability_status = 'available'

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            INNER JOIN batches b
                ON b.batch_id = e.batch_id

            WHERE
                e.student_id = ?
                AND b.course_id = c.course_id
                AND e.enrollment_status IN
                (
                    'Active',
                    'Completed'
                )
        )
");

$stmt->bind_param(
    "ii",
    $studentId,
    $studentId
);

$stmt->execute();

$stats = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

$totalTutes = (int) ($stats['total_tutes'] ?? 0);
$purchasedTutes = (int) ($stats['purchased_tutes'] ?? 0);
$pendingTutes = (int) ($stats['pending_tutes'] ?? 0);
$availableTutes = max(
    0,
    $totalTutes - $purchasedTutes - $pendingTutes
);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo e($projectName); ?>
        | Digital Tute Store
    </title>

    <link
        rel="stylesheet"
        href="../css/student.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >

</head>


<body>


<div class="dashboard-shell">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">

        <div class="brand-panel">

            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>

            <div>

                <p class="brand-label">
                    LearnFlow
                </p>

                <p class="brand-subtitle">
                    Student Portal
                </p>

            </div>

        </div>


        <nav class="sidebar-menu">

            <a href="dashboard.php" class="menu-link">

                <i class="fas fa-tachometer-alt"></i>

                Dashboard

            </a>


            <a href="profile.php" class="menu-link">

                <i class="fas fa-user"></i>

                Profile

            </a>


            <a href="courses.php" class="menu-link">

                <i class="fas fa-book-open"></i>

                Courses

            </a>


            <a href="materials.php" class="menu-link">

                <i class="fas fa-folder-open"></i>

                Materials

            </a>


            <a href="assignments.php" class="menu-link">

                <i class="fas fa-file-alt"></i>

                Assignments

            </a>


            <a href="quizzes.php" class="menu-link">

                <i class="fas fa-check-square"></i>

                Quizzes

            </a>


            <a href="exams.php" class="menu-link">

                <i class="fas fa-clipboard-list"></i>

                Exams

            </a>


            <a href="grades.php" class="menu-link">

                <i class="fas fa-chart-column"></i>

                Grades

            </a>


            <a href="progress.php" class="menu-link">

                <i class="fas fa-chart-line"></i>

                Progress

            </a>


            <a href="attendance.php" class="menu-link">

                <i class="fas fa-calendar-check"></i>

                Attendance

            </a>


            <a href="schedule.php" class="menu-link">

                <i class="fas fa-calendar-days"></i>

                Schedule

            </a>


            <a href="announcements.php" class="menu-link">

                <i class="fas fa-bullhorn"></i>

                Announcements

            </a>


            <a href="discussions.php" class="menu-link">

                <i class="fas fa-comments"></i>

                Discussions

            </a>


            <a href="tute_store.php" class="menu-link active">

                <i class="fas fa-store"></i>

                Tute Store

            </a>


            <a href="payments.php" class="menu-link">

                <i class="fas fa-credit-card"></i>

                Payments

            </a>


            <a href="notifications.php" class="menu-link">

                <i class="fas fa-bell"></i>

                Notifications

            </a>


            <a
                href="../auth/logout.php"
                class="menu-link logout-link"
            >

                <i class="fas fa-sign-out-alt"></i>

                Logout

            </a>

        </nav>

    </aside>


    <!-- =====================================================
         CONTENT
         ===================================================== -->

    <div class="content-area">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button
                    class="mobile-menu-btn"
                    type="button"
                >

                    <i class="fas fa-bars"></i>

                </button>


                <div class="dashboard-title">

                    <p class="small-label">
                        Store
                    </p>

                    <h1>
                        Digital Tute Store
                    </h1>

                </div>

            </div>


            <div class="topbar-right">

                <div class="project-pill">
                    LearnFlow
                </div>


                <a
                    href="notifications.php"
                    class="icon-btn"
                >

                    <i class="fas fa-bell"></i>

                </a>


                <div class="profile-chip">

                    <div class="avatar-placeholder">

                        <i class="fas fa-user-circle"></i>

                    </div>

                    <div>

                        <span>
                            Hello,
                        </span>

                        <strong>
                            <?php echo e($studentName); ?>
                        </strong>

                    </div>

                </div>

            </div>

        </header>


        <main class="dashboard-main">


            <!-- =================================================
                 OVERVIEW
                 ================================================= -->

            <section class="overview-cards">


                <article class="summary-card">

                    <div>

                        <p class="card-label">
                            Digital Learning Store
                        </p>

                        <h2>
                            Purchase and access digital tutes
                            for your enrolled courses.
                        </h2>

                    </div>

                    <div class="summary-icon">

                        <i class="fas fa-store"></i>

                    </div>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Available
                        </p>

                        <h3>
                            <?php echo $availableTutes; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        Ready to purchase
                    </span>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Owned
                        </p>

                        <h3>
                            <?php echo $purchasedTutes; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        Purchased tutes
                    </span>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Pending
                        </p>

                        <h3>
                            <?php echo $pendingTutes; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        Awaiting payment
                    </span>

                </article>

            </section>


            <!-- =================================================
                 SUCCESS MESSAGE
                 ================================================= -->

            <?php if ($success !== ''): ?>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#ecfdf5;
                        color:#166534;
                        border:1px solid #bbf7d0;
                    "
                >

                    <i class="fas fa-circle-check"></i>

                    <?php echo e($success); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 ERROR MESSAGE
                 ================================================= -->

            <?php if ($error !== ''): ?>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#fef2f2;
                        color:#991b1b;
                        border:1px solid #fecaca;
                    "
                >

                    <i class="fas fa-circle-exclamation"></i>

                    <?php echo e($error); ?>

                </div>

            <?php endif; ?>


            <!-- =================================================
                 FILTERS
                 ================================================= -->

            <section class="course-detail-section">

                <div class="course-detail-header">

                    <div>

                        <p class="card-label">
                            Find Tutes
                        </p>

                        <h2>
                            Store Filters
                        </h2>

                    </div>

                </div>


                <form
                    method="GET"
                    action="tute_store.php"
                    class="profile-form"
                >

                    <div class="form-grid">


                        <div class="form-group">

                            <label for="course_id">
                                Course
                            </label>

                            <div class="input-wrapper">

                                <i class="fas fa-book"></i>

                                <select
                                    id="course_id"
                                    name="course_id"
                                    style="
                                        width:100%;
                                        border:1px solid var(--border);
                                        border-radius:14px;
                                        background:#ffffff;
                                        color:var(--text-strong);
                                        padding:13px 14px 13px 44px;
                                        outline:none;
                                    "
                                >

                                    <option value="0">
                                        All Courses
                                    </option>

                                    <?php
                                    foreach ($courseOptions as $courseOption):
                                    ?>

                                        <option
                                            value="<?php
                                            echo (int) $courseOption['course_id'];
                                            ?>"
                                            <?php
                                            echo (
                                                $selectedCourseId ===
                                                (int) $courseOption['course_id']
                                            )
                                                ? 'selected'
                                                : '';
                                            ?>
                                        >

                                            <?php
                                            echo e(
                                                $courseOption['course_name']
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>

                        </div>


                        <div class="form-group">

                            <label for="status">
                                Purchase Status
                            </label>

                            <div class="input-wrapper">

                                <i class="fas fa-filter"></i>

                                <select
                                    id="status"
                                    name="status"
                                    style="
                                        width:100%;
                                        border:1px solid var(--border);
                                        border-radius:14px;
                                        background:#ffffff;
                                        color:var(--text-strong);
                                        padding:13px 14px 13px 44px;
                                        outline:none;
                                    "
                                >

                                    <option
                                        value="all"
                                        <?php
                                        echo $selectedStatus === 'all'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        All
                                    </option>

                                    <option
                                        value="available"
                                        <?php
                                        echo $selectedStatus === 'available'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        Available
                                    </option>

                                    <option
                                        value="owned"
                                        <?php
                                        echo $selectedStatus === 'owned'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        Owned
                                    </option>

                                    <option
                                        value="pending"
                                        <?php
                                        echo $selectedStatus === 'pending'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        Pending Payment
                                    </option>

                                </select>

                            </div>

                        </div>


                        <div class="form-group full-width">

                            <label for="search">
                                Search
                            </label>

                            <div class="input-wrapper">

                                <i class="fas fa-search"></i>

                                <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    value="<?php echo e($searchText); ?>"
                                    placeholder="Search by tute, module or course"
                                >

                            </div>

                        </div>

                    </div>


                    <div class="form-actions">

                        <a
                            href="tute_store.php"
                            class="secondary-btn"
                        >

                            <i class="fas fa-rotate-left"></i>

                            Clear

                        </a>


                        <button
                            type="submit"
                            class="primary-btn"
                        >

                            <i class="fas fa-filter"></i>

                            Apply Filters

                        </button>

                    </div>

                </form>

            </section>


            <!-- =================================================
                 TUTE CARDS
                 ================================================= -->

            <section
                class="course-detail-section"
                style="margin-top:24px;"
            >

                <div class="course-detail-header">

                    <div>

                        <p class="card-label">
                            Digital Tutes
                        </p>

                        <h2>
                            Available Learning Tutes
                        </h2>

                        <p>
                            Only approved tutes from courses you are
                            enrolled in are shown here.
                        </p>

                    </div>

                </div>


                <?php if (empty($tutes)): ?>

                    <div class="course-empty-state">

                        <div class="course-empty-icon">

                            <i class="fas fa-store"></i>

                        </div>

                        <h3>
                            No Tutes Found
                        </h3>

                        <p>
                            No digital tutes match the selected filters.
                        </p>

                    </div>

                <?php else: ?>


                    <div class="card-grid">


                        <?php foreach ($tutes as $tute): ?>


                            <?php

                            $purchaseStatus =
                                $tute['purchase_status'] ?? '';

                            $isOwned =
                                $purchaseStatus === 'completed';

                            $isPending =
                                $purchaseStatus === 'pending';

                            $isFree =
                                (float) $tute['price'] <= 0;

                            ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-purple">

                                    <i class="fas fa-file-pdf"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        $tute['course_name']
                                    );
                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $tute['title']
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Module:
                                    </strong>

                                    <?php
                                    echo e(
                                        $tute['module_name']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <?php
                                    echo e(
                                        $tute['description']
                                        ?: 'No description has been added.'
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Price:
                                    </strong>

                                    <?php if ($isFree): ?>

                                        Free

                                    <?php else: ?>

                                        LKR
                                        <?php
                                        echo money(
                                            $tute['price']
                                        );
                                        ?>

                                    <?php endif; ?>

                                </p>


                                <?php if ($isOwned): ?>

                                    <p>

                                        <strong>
                                            Status:
                                        </strong>

                                        Owned

                                    </p>


                                    <div
                                        class="form-actions"
                                        style="
                                            justify-content:flex-start;
                                            flex-wrap:wrap;
                                        "
                                    >

                                        <a
                                            href="resource_access.php?resource_id=<?php
                                            echo (int) $tute['resource_id'];
                                            ?>&action=download"
                                            class="primary-btn"
                                        >

                                            <i class="fas fa-download"></i>

                                            Download Tute

                                        </a>

                                    </div>


                                <?php elseif ($isPending): ?>

                                    <p>

                                        <strong>
                                            Purchase:
                                        </strong>

                                        Pending

                                    </p>


                                    <?php
                                    if (
                                        !empty(
                                            $tute['latest_payment_status']
                                        )
                                    ):
                                    ?>

                                        <p>

                                            <strong>
                                                Payment:
                                            </strong>

                                            <?php
                                            echo e(
                                                prettyStatus(
                                                    $tute[
                                                        'latest_payment_status'
                                                    ]
                                                )
                                            );
                                            ?>

                                        </p>

                                    <?php endif; ?>


                                    <div
                                        class="form-actions"
                                        style="
                                            justify-content:flex-start;
                                            flex-wrap:wrap;
                                        "
                                    >

                                        <a
                                            href="payments.php?purchase_id=<?php
                                            echo (int) $tute['purchase_id'];
                                            ?>"
                                            class="primary-btn"
                                        >

                                            <i class="fas fa-credit-card"></i>

                                            Continue Payment

                                        </a>

                                    </div>


                                <?php else: ?>


                                    <form
                                        method="POST"
                                        action="tute_store.php"
                                        class="form-actions"
                                        style="
                                            justify-content:flex-start;
                                        "
                                    >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?php
                                            echo e(
                                                $_SESSION[
                                                    'csrf_token'
                                                ]
                                            );
                                            ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="resource_id"
                                            value="<?php
                                            echo (int)
                                                $tute['resource_id'];
                                            ?>"
                                        >

                                        <button
                                            type="submit"
                                            name="purchase_tute"
                                            class="primary-btn"
                                        >

                                            <?php if ($isFree): ?>

                                                <i class="fas fa-plus-circle"></i>

                                                Get Free Tute

                                            <?php else: ?>

                                                <i class="fas fa-cart-shopping"></i>

                                                Purchase Tute

                                            <?php endif; ?>

                                        </button>

                                    </form>


                                <?php endif; ?>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


            <!-- =================================================
                 QUICK LINKS
                 ================================================= -->

            <section class="card-grid">


                <a
                    href="payments.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-green">

                        <i class="fas fa-credit-card"></i>

                    </div>

                    <h3>
                        Payments
                    </h3>

                    <p>
                        Continue pending purchases and view
                        your payment history.
                    </p>

                </a>


                <a
                    href="materials.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-blue">

                        <i class="fas fa-folder-open"></i>

                    </div>

                    <h3>
                        Learning Materials
                    </h3>

                    <p>
                        Return to your course notes,
                        recordings and learning resources.
                    </p>

                </a>


            </section>


        </main>

    </div>

</div>


<script src="../js/student.js"></script>


</body>

</html>
