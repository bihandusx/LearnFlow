<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';

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
| LOAD STUDENT REGISTRATION NUMBER
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        registration_no

    FROM students

    WHERE student_id = ?

    LIMIT 1
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$studentRow =
    $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();

$registrationNo =
    $studentRow['registration_no']
    ?? '';

/*
|--------------------------------------------------------------------------
| SELECTED RECEIPT
|--------------------------------------------------------------------------
*/

$paymentId =
    isset($_GET['payment_id'])
        ? (int) $_GET['payment_id']
        : 0;

$selectedReceipt = null;


if ($paymentId > 0) {

    $stmt = $conn->prepare("
        SELECT
            p.payment_id,
            p.student_id,
            p.amount,
            p.payment_method,
            p.payment_date,
            p.payment_status,
            p.receipt_no,
            p.reference_no,
            p.payment_type,

            cp.enrollment_id,

            course_c.course_name AS course_payment_course,
            course_b.batch_name AS course_payment_batch,

            tp.purchase_id,

            lr.title AS tute_title,
            tute_c.course_name AS tute_course

        FROM payments p

        LEFT JOIN course_payments cp
            ON cp.payment_id = p.payment_id

        LEFT JOIN enrollments course_e
            ON course_e.enrollment_id = cp.enrollment_id

        LEFT JOIN batches course_b
            ON course_b.batch_id = course_e.batch_id

        LEFT JOIN courses course_c
            ON course_c.course_id = course_b.course_id

        LEFT JOIN tute_payments tpay
            ON tpay.payment_id = p.payment_id

        LEFT JOIN tute_purchases tp
            ON tp.purchase_id = tpay.purchase_id

        LEFT JOIN learning_resources lr
            ON lr.resource_id = tp.tute_resource_id

        LEFT JOIN modules tute_m
            ON tute_m.module_id = lr.module_id

        LEFT JOIN courses tute_c
            ON tute_c.course_id = tute_m.course_id

        WHERE
            p.payment_id = ?
            AND p.student_id = ?
            AND p.payment_status = 'paid'

        LIMIT 1
    ");

    $stmt->bind_param(
        "ii",
        $paymentId,
        $studentId
    );

    $stmt->execute();

    $selectedReceipt =
        $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();


    if (!$selectedReceipt) {

        header("Location: receipts.php");
        exit();
    }
}

/*
|--------------------------------------------------------------------------
| BUILD RECEIPT LABELS
|--------------------------------------------------------------------------
*/

$receiptNumber = '';
$receiptItem = '';
$receiptContext = '';


if ($selectedReceipt) {

    $receiptNumber =
        $selectedReceipt['receipt_no']
        ?: sprintf(
            'LF-R-%06d',
            (int) $selectedReceipt['payment_id']
        );


    if (
        $selectedReceipt['payment_type']
        === 'course'
    ) {

        $receiptItem =
            $selectedReceipt[
                'course_payment_course'
            ]
            ?: 'Course Fee';

        $receiptContext =
            $selectedReceipt[
                'course_payment_batch'
            ]
            ?: 'Course Payment';

    } else {

        $receiptItem =
            $selectedReceipt['tute_title']
            ?: 'Digital Tute';

        $receiptContext =
            $selectedReceipt['tute_course']
            ?: 'Tute Purchase';
    }
}

/*
|--------------------------------------------------------------------------
| DOWNLOAD RECEIPT AS STANDALONE HTML
|--------------------------------------------------------------------------
|
| The project does not depend on an external PDF library.
| This produces a real downloadable receipt file that opens in any browser
| and can also be printed/saved as PDF.
|--------------------------------------------------------------------------
*/

if (
    $selectedReceipt &&
    isset($_GET['download']) &&
    $_GET['download'] === '1'
) {

    $safeFileName =
        preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $receiptNumber
        );

    header(
        'Content-Type: text/html; charset=UTF-8'
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $safeFileName .
        '.html"'
    );

    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo e($receiptNumber); ?></title>
<style>
body {
    font-family: Arial, sans-serif;
    margin: 40px;
    color: #111827;
}
.receipt {
    max-width: 760px;
    margin: 0 auto;
    border: 1px solid #d1d5db;
    border-radius: 16px;
    padding: 28px;
}
.top {
    display: flex;
    justify-content: space-between;
    gap: 20px;
}
h1, h2, p {
    margin-top: 0;
}
.paid {
    font-weight: bold;
    color: #166534;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 24px;
}
td {
    border-bottom: 1px solid #e5e7eb;
    padding: 12px 8px;
}
td:first-child {
    width: 38%;
    font-weight: bold;
}
.total {
    font-size: 20px;
    font-weight: bold;
}
.footer {
    margin-top: 28px;
    font-size: 13px;
    color: #6b7280;
}
</style>
</head>
<body>
<div class="receipt">
    <div class="top">
        <div>
            <h1>LearnFlow</h1>
            <p>Payment Receipt</p>
        </div>
        <div>
            <strong><?php echo e($receiptNumber); ?></strong>
            <p class="paid">PAID</p>
        </div>
    </div>

    <table>
        <tr>
            <td>Student</td>
            <td><?php echo e($studentName); ?></td>
        </tr>
        <tr>
            <td>Registration No.</td>
            <td><?php echo e($registrationNo ?: '-'); ?></td>
        </tr>
        <tr>
            <td>Payment ID</td>
            <td>#<?php echo (int) $selectedReceipt['payment_id']; ?></td>
        </tr>
        <tr>
            <td>Payment Type</td>
            <td><?php echo e(prettyStatus($selectedReceipt['payment_type'])); ?></td>
        </tr>
        <tr>
            <td>Item</td>
            <td><?php echo e($receiptItem); ?></td>
        </tr>
        <tr>
            <td>Course / Batch</td>
            <td><?php echo e($receiptContext); ?></td>
        </tr>
        <tr>
            <td>Payment Method</td>
            <td><?php echo e(prettyStatus($selectedReceipt['payment_method'])); ?></td>
        </tr>
        <tr>
            <td>Reference</td>
            <td><?php echo e($selectedReceipt['reference_no'] ?: '-'); ?></td>
        </tr>
        <tr>
            <td>Payment Date</td>
            <td><?php echo e($selectedReceipt['payment_date']); ?></td>
        </tr>
        <tr>
            <td>Amount</td>
            <td class="total">LKR <?php echo money($selectedReceipt['amount']); ?></td>
        </tr>
    </table>

    <div class="footer">
        This receipt was generated by the LearnFlow Learning Management
        and Class Administration System.
    </div>
</div>
</body>
</html>
    <?php

    exit();
}

/*
|--------------------------------------------------------------------------
| LOAD ALL PAID RECEIPTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        p.payment_id,
        p.amount,
        p.payment_method,
        p.payment_date,
        p.receipt_no,
        p.reference_no,
        p.payment_type,

        course_c.course_name AS course_payment_course,
        course_b.batch_name AS course_payment_batch,

        lr.title AS tute_title,
        tute_c.course_name AS tute_course

    FROM payments p

    LEFT JOIN course_payments cp
        ON cp.payment_id = p.payment_id

    LEFT JOIN enrollments course_e
        ON course_e.enrollment_id = cp.enrollment_id

    LEFT JOIN batches course_b
        ON course_b.batch_id = course_e.batch_id

    LEFT JOIN courses course_c
        ON course_c.course_id = course_b.course_id

    LEFT JOIN tute_payments tpay
        ON tpay.payment_id = p.payment_id

    LEFT JOIN tute_purchases tp
        ON tp.purchase_id = tpay.purchase_id

    LEFT JOIN learning_resources lr
        ON lr.resource_id = tp.tute_resource_id

    LEFT JOIN modules tute_m
        ON tute_m.module_id = lr.module_id

    LEFT JOIN courses tute_c
        ON tute_c.course_id = tute_m.course_id

    WHERE
        p.student_id = ?
        AND p.payment_status = 'paid'

    ORDER BY
        p.payment_date DESC,
        p.payment_id DESC
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$receipts =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$totalReceipts = count($receipts);
$totalPaid = 0.00;

foreach ($receipts as $receipt) {
    $totalPaid += (float) $receipt['amount'];
}

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
        | Payment Receipts
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

            <a href="tute_store.php" class="menu-link">

                <i class="fas fa-store"></i>
                Tute Store

            </a>

            <a href="payments.php" class="menu-link active">

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
                        Payments
                    </p>

                    <h1>
                        Payment Receipts
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
                            Payment Records
                        </p>

                        <h2>
                            View and download your confirmed
                            payment receipts.
                        </h2>

                    </div>

                    <div class="summary-icon">

                        <i class="fas fa-receipt"></i>

                    </div>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Receipts
                        </p>

                        <h3>
                            <?php echo $totalReceipts; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        Paid transactions
                    </span>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Total Paid
                        </p>

                        <h3 class="receipt-total-value">

                            LKR
                            <?php echo money($totalPaid); ?>

                        </h3>

                    </div>

                    <span class="stat-badge">
                        Confirmed payments
                    </span>

                </article>

            </section>


            <!-- =================================================
                 SELECTED RECEIPT
                 ================================================= -->

            <?php if ($selectedReceipt !== null): ?>

                <section class="receipt-section">

                    <div class="receipt-section-header">

                        <div>

                            <p class="card-label">
                                Payment Receipt
                            </p>

                            <h2>
                                <?php echo e($receiptNumber); ?>
                            </h2>

                        </div>


                        <div
                            class="form-actions"
                            style="flex-wrap:wrap;"
                        >

                            <a
                                href="receipts.php"
                                class="secondary-btn"
                            >

                                <i class="fas fa-arrow-left"></i>

                                Back

                            </a>


                            <a
                                href="receipts.php?payment_id=<?php
                                echo (int)
                                    $selectedReceipt[
                                        'payment_id'
                                    ];
                                ?>&download=1"
                                class="secondary-btn"
                            >

                                <i class="fas fa-download"></i>

                                Download

                            </a>


                            <button
                                type="button"
                                class="primary-btn"
                                onclick="window.print();"
                            >

                                <i class="fas fa-print"></i>

                                Print / Save PDF

                            </button>

                        </div>

                    </div>


                    <div
                        class="receipt-document"
                        id="printableReceipt"
                    >

                        <div class="receipt-document-top">

                            <div>

                                <div class="receipt-brand-icon">

                                    <i class="fas fa-graduation-cap"></i>

                                </div>


                                <div>

                                    <h3>
                                        LearnFlow
                                    </h3>

                                    <p>
                                        Digital Payment Receipt
                                    </p>

                                </div>

                            </div>


                            <span class="receipt-paid-badge">
                                Paid
                            </span>

                        </div>


                        <div class="receipt-divider"></div>


                        <div class="receipt-details-grid">


                            <div class="receipt-detail-item">

                                <span>
                                    Receipt Number
                                </span>

                                <strong>
                                    <?php echo e($receiptNumber); ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Payment ID
                                </span>

                                <strong>

                                    #<?php
                                    echo (int)
                                        $selectedReceipt[
                                            'payment_id'
                                        ];
                                    ?>

                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Student
                                </span>

                                <strong>
                                    <?php echo e($studentName); ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Registration Number
                                </span>

                                <strong>
                                    <?php
                                    echo e(
                                        $registrationNo
                                        ?: '-'
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Payment Type
                                </span>

                                <strong>
                                    <?php
                                    echo e(
                                        prettyStatus(
                                            $selectedReceipt[
                                                'payment_type'
                                            ]
                                        )
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Item
                                </span>

                                <strong>
                                    <?php echo e($receiptItem); ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Course / Batch
                                </span>

                                <strong>
                                    <?php echo e($receiptContext); ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Payment Method
                                </span>

                                <strong>
                                    <?php
                                    echo e(
                                        prettyStatus(
                                            $selectedReceipt[
                                                'payment_method'
                                            ]
                                        )
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Reference
                                </span>

                                <strong>
                                    <?php
                                    echo e(
                                        $selectedReceipt[
                                            'reference_no'
                                        ]
                                        ?: '-'
                                    );
                                    ?>
                                </strong>

                            </div>


                            <div class="receipt-detail-item">

                                <span>
                                    Payment Date
                                </span>

                                <strong>
                                    <?php
                                    echo e(
                                        $selectedReceipt[
                                            'payment_date'
                                        ]
                                    );
                                    ?>
                                </strong>

                            </div>


                        </div>


                        <div
                            style="
                                margin-top:24px;
                                padding:18px;
                                border-radius:14px;
                                background:#f8fafc;
                            "
                        >

                            <p class="card-label">
                                Amount Paid
                            </p>

                            <h2
                                style="
                                    margin:4px 0 0;
                                "
                            >

                                LKR
                                <?php
                                echo money(
                                    $selectedReceipt[
                                        'amount'
                                    ]
                                );
                                ?>

                            </h2>

                        </div>


                    </div>


                </section>

            <?php endif; ?>


            <!-- =================================================
                 RECEIPT LIST
                 ================================================= -->

            <section
                class="receipt-section"
                style="margin-top:24px;"
            >

                <div class="receipt-section-header">

                    <div>

                        <p class="card-label">
                            Receipt History
                        </p>

                        <h2>
                            Confirmed Payments
                        </h2>

                        <p>
                            Receipts are available only after a
                            payment has been confirmed as paid.
                        </p>

                    </div>


                    <a
                        href="payments.php"
                        class="secondary-btn"
                    >

                        <i class="fas fa-arrow-left"></i>

                        Payment History

                    </a>

                </div>


                <?php if (empty($receipts)): ?>

                    <div class="course-empty-state">

                        <div class="course-empty-icon">

                            <i class="fas fa-receipt"></i>

                        </div>

                        <h3>
                            No Receipts Yet
                        </h3>

                        <p>
                            A receipt will appear after a payment
                            is confirmed as paid.
                        </p>

                    </div>

                <?php else: ?>


                    <div class="card-grid">


                        <?php foreach ($receipts as $receipt): ?>


                            <?php

                            $displayReceiptNo =
                                $receipt['receipt_no']
                                ?: sprintf(
                                    'LF-R-%06d',
                                    (int) $receipt['payment_id']
                                );


                            if (
                                $receipt['payment_type']
                                === 'course'
                            ) {

                                $item =
                                    $receipt[
                                        'course_payment_course'
                                    ]
                                    ?: 'Course Fee';

                                $context =
                                    $receipt[
                                        'course_payment_batch'
                                    ]
                                    ?: 'Course Payment';

                            } else {

                                $item =
                                    $receipt['tute_title']
                                    ?: 'Digital Tute';

                                $context =
                                    $receipt['tute_course']
                                    ?: 'Tute Purchase';
                            }

                            ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-green">

                                    <i class="fas fa-receipt"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        $displayReceiptNo
                                    );
                                    ?>

                                </p>


                                <h3>

                                    <?php echo e($item); ?>

                                </h3>


                                <p>

                                    <?php echo e($context); ?>

                                </p>


                                <p>

                                    <strong>
                                        Amount:
                                    </strong>

                                    LKR
                                    <?php
                                    echo money(
                                        $receipt['amount']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Date:
                                    </strong>

                                    <?php
                                    echo e(
                                        $receipt['payment_date']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Method:
                                    </strong>

                                    <?php
                                    echo e(
                                        prettyStatus(
                                            $receipt[
                                                'payment_method'
                                            ]
                                        )
                                    );
                                    ?>

                                </p>


                                <div
                                    class="form-actions"
                                    style="
                                        justify-content:flex-start;
                                        flex-wrap:wrap;
                                    "
                                >

                                    <a
                                        href="receipts.php?payment_id=<?php
                                        echo (int)
                                            $receipt[
                                                'payment_id'
                                            ];
                                        ?>"
                                        class="primary-btn"
                                    >

                                        <i class="fas fa-eye"></i>

                                        View Receipt

                                    </a>


                                    <a
                                        href="receipts.php?payment_id=<?php
                                        echo (int)
                                            $receipt[
                                                'payment_id'
                                            ];
                                        ?>&download=1"
                                        class="secondary-btn"
                                    >

                                        <i class="fas fa-download"></i>

                                        Download

                                    </a>

                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


        </main>

    </div>

</div>


<style>
@media print {

    .sidebar,
    .topbar,
    .receipt-section > .receipt-section-header,
    .receipt-section:nth-last-child(1) {
        display: none !important;
    }

    .content-area,
    .dashboard-main {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }

    #printableReceipt {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>


<script src="../js/student.js"></script>


</body>

</html>
