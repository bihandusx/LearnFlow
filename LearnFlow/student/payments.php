<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';
$success = $_SESSION['payment_success'] ?? '';
unset($_SESSION['payment_success']);

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
| KEEP TUTE PURCHASE STATUS IN SYNC WITH CONFIRMED PAYMENT
|--------------------------------------------------------------------------
|
| There is no online payment gateway in LearnFlow.
| A Student creates a payment record with status "pending".
| Staff/Admin later confirms it by changing payments.payment_status to "paid".
|
| When the Student returns to this page, any paid tute payment is synchronized
| to tute_purchases.purchase_status = 'completed'.
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE tute_purchases tp

    INNER JOIN tute_payments tpay
        ON tpay.purchase_id = tp.purchase_id

    INNER JOIN payments p
        ON p.payment_id = tpay.payment_id

    SET
        tp.purchase_status = 'completed'

    WHERE
        tp.student_id = ?
        AND p.student_id = ?
        AND p.payment_type = 'tute'
        AND p.payment_status = 'paid'
        AND tp.purchase_status <> 'completed'
");

$stmt->bind_param(
    "ii",
    $studentId,
    $studentId
);

$stmt->execute();
$stmt->close();

/*
|--------------------------------------------------------------------------
| POST: CREATE / RESUBMIT PAYMENT REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {

        $error =
            'Invalid request. Please refresh the page and try again.';

    } else {

        $paymentMethod =
            trim($_POST['payment_method'] ?? '');

        $referenceNo =
            trim($_POST['reference_no'] ?? '');

        $allowedMethods = [
            'cash',
            'bank_transfer',
            'card',
            'other'
        ];

        if (
            !in_array(
                $paymentMethod,
                $allowedMethods,
                true
            )
        ) {

            $error =
                'Please choose a valid payment method.';

        } elseif (strlen($referenceNo) > 150) {

            $error =
                'Reference number is too long.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | COURSE PAYMENT
            |--------------------------------------------------------------------------
            */

            if (isset($_POST['pay_course'])) {

                $enrollmentId =
                    (int) ($_POST['enrollment_id'] ?? 0);

                $requestedAmount =
                    (float) ($_POST['amount'] ?? 0);


                if ($enrollmentId <= 0) {

                    $error =
                        'Invalid enrollment selected.';

                } elseif ($requestedAmount <= 0) {

                    $error =
                        'Payment amount must be greater than zero.';

                } else {

                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | LOAD THE STUDENT'S ENROLLMENT
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $conn->prepare("
                            SELECT
                                e.enrollment_id,
                                e.enrollment_status,

                                b.batch_id,
                                b.batch_name,

                                c.course_id,
                                c.course_name,
                                c.course_fee

                            FROM enrollments e

                            INNER JOIN batches b
                                ON b.batch_id = e.batch_id

                            INNER JOIN courses c
                                ON c.course_id = b.course_id

                            WHERE
                                e.enrollment_id = ?
                                AND e.student_id = ?
                                AND e.enrollment_status IN
                                (
                                    'Pending',
                                    'Active',
                                    'Completed'
                                )

                            LIMIT 1
                        ");

                        $stmt->bind_param(
                            "ii",
                            $enrollmentId,
                            $studentId
                        );

                        $stmt->execute();

                        $enrollment =
                            $stmt
                            ->get_result()
                            ->fetch_assoc();

                        $stmt->close();


                        if (!$enrollment) {

                            throw new RuntimeException(
                                'Enrollment not found.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | CALCULATE CURRENT PAID TOTAL
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $conn->prepare("
                            SELECT
                                COALESCE(
                                    SUM(p.amount),
                                    0
                                ) AS paid_total

                            FROM course_payments cp

                            INNER JOIN payments p
                                ON p.payment_id = cp.payment_id

                            WHERE
                                cp.enrollment_id = ?
                                AND p.student_id = ?
                                AND p.payment_type = 'course'
                                AND p.payment_status = 'paid'
                        ");

                        $stmt->bind_param(
                            "ii",
                            $enrollmentId,
                            $studentId
                        );

                        $stmt->execute();

                        $paidTotal =
                            (float) (
                                $stmt
                                ->get_result()
                                ->fetch_assoc()['paid_total']
                                ?? 0
                            );

                        $stmt->close();


                        $courseFee =
                            (float) $enrollment['course_fee'];

                        $outstanding =
                            max(
                                0,
                                $courseFee - $paidTotal
                            );


                        if ($outstanding <= 0) {

                            throw new RuntimeException(
                                'This course fee has already been fully paid.'
                            );
                        }


                        if (
                            $requestedAmount >
                            ($outstanding + 0.00001)
                        ) {

                            throw new RuntimeException(
                                'The payment amount cannot exceed the outstanding balance of LKR ' .
                                money($outstanding) .
                                '.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | BLOCK A SECOND PENDING PAYMENT FOR SAME ENROLLMENT
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $conn->prepare("
                            SELECT
                                p.payment_id

                            FROM course_payments cp

                            INNER JOIN payments p
                                ON p.payment_id = cp.payment_id

                            WHERE
                                cp.enrollment_id = ?
                                AND p.student_id = ?
                                AND p.payment_type = 'course'
                                AND p.payment_status = 'pending'

                            LIMIT 1
                        ");

                        $stmt->bind_param(
                            "ii",
                            $enrollmentId,
                            $studentId
                        );

                        $stmt->execute();

                        $pendingPayment =
                            $stmt
                            ->get_result()
                            ->fetch_assoc();

                        $stmt->close();


                        if ($pendingPayment) {

                            throw new RuntimeException(
                                'A course payment is already pending confirmation. Please wait for it to be reviewed.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | INSERT PAYMENT + COURSE PAYMENT SUBTYPE
                        |--------------------------------------------------------------------------
                        */

                        $conn->begin_transaction();

                        $stmt = $conn->prepare("
                            INSERT INTO payments
                            (
                                student_id,
                                amount,
                                payment_method,
                                payment_status,
                                reference_no,
                                payment_type
                            )

                            VALUES
                            (
                                ?,
                                ?,
                                ?,
                                'pending',
                                ?,
                                'course'
                            )
                        ");

                        $referenceValue =
                            $referenceNo !== ''
                                ? $referenceNo
                                : null;

                        $stmt->bind_param(
                            "idss",
                            $studentId,
                            $requestedAmount,
                            $paymentMethod,
                            $referenceValue
                        );

                        $stmt->execute();

                        $paymentId =
                            (int) $conn->insert_id;

                        $stmt->close();


                        $stmt = $conn->prepare("
                            INSERT INTO course_payments
                            (
                                payment_id,
                                enrollment_id
                            )

                            VALUES
                            (
                                ?,
                                ?
                            )
                        ");

                        $stmt->bind_param(
                            "ii",
                            $paymentId,
                            $enrollmentId
                        );

                        $stmt->execute();
                        $stmt->close();

                        $conn->commit();


                        $_SESSION['payment_success'] =
                            'Course payment request submitted for ' .
                            $enrollment['course_name'] .
                            '. It is waiting for staff confirmation.';


                        header(
                            "Location: payments.php"
                        );

                        exit();

                    } catch (RuntimeException $e) {

                        try {
                            $conn->rollback();
                        } catch (Throwable $ignored) {
                        }

                        $error = $e->getMessage();

                    } catch (Throwable $e) {

                        try {
                            $conn->rollback();
                        } catch (Throwable $ignored) {
                        }

                        $error =
                            'The course payment request could not be created. Please try again.';
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | TUTE PAYMENT
            |--------------------------------------------------------------------------
            */

            if (
                isset($_POST['pay_tute']) &&
                $error === ''
            ) {

                $purchaseId =
                    (int) ($_POST['purchase_id'] ?? 0);


                if ($purchaseId <= 0) {

                    $error =
                        'Invalid tute purchase selected.';

                } else {

                    try {

                        /*
                        |--------------------------------------------------------------------------
                        | LOAD PURCHASE
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $conn->prepare("
                            SELECT
                                tp.purchase_id,
                                tp.tute_resource_id,
                                tp.amount,
                                tp.purchase_status,

                                lr.title,

                                c.course_name

                            FROM tute_purchases tp

                            INNER JOIN learning_resources lr
                                ON lr.resource_id = tp.tute_resource_id

                            INNER JOIN modules m
                                ON m.module_id = lr.module_id

                            INNER JOIN courses c
                                ON c.course_id = m.course_id

                            WHERE
                                tp.purchase_id = ?
                                AND tp.student_id = ?

                            LIMIT 1
                        ");

                        $stmt->bind_param(
                            "ii",
                            $purchaseId,
                            $studentId
                        );

                        $stmt->execute();

                        $purchase =
                            $stmt
                            ->get_result()
                            ->fetch_assoc();

                        $stmt->close();


                        if (!$purchase) {

                            throw new RuntimeException(
                                'Tute purchase not found.'
                            );
                        }


                        if (
                            $purchase['purchase_status']
                            === 'completed'
                        ) {

                            throw new RuntimeException(
                                'This tute has already been paid for.'
                            );
                        }


                        $amount =
                            (float) $purchase['amount'];


                        if ($amount <= 0) {

                            throw new RuntimeException(
                                'This is a free tute and does not require payment.'
                            );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | FIND EXISTING PAYMENT FOR THIS PURCHASE
                        |--------------------------------------------------------------------------
                        |
                        | tute_payments.purchase_id is UNIQUE, so a tute purchase can
                        | have only one linked payment record. If that payment previously
                        | failed/cancelled/refunded, we safely reuse it.
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $conn->prepare("
                            SELECT
                                p.payment_id,
                                p.payment_status

                            FROM tute_payments tpay

                            INNER JOIN payments p
                                ON p.payment_id = tpay.payment_id

                            WHERE
                                tpay.purchase_id = ?
                                AND p.student_id = ?

                            LIMIT 1
                        ");

                        $stmt->bind_param(
                            "ii",
                            $purchaseId,
                            $studentId
                        );

                        $stmt->execute();

                        $existingPayment =
                            $stmt
                            ->get_result()
                            ->fetch_assoc();

                        $stmt->close();


                        if (
                            $existingPayment &&
                            $existingPayment['payment_status']
                            === 'paid'
                        ) {

                            /*
                            | Bring the purchase record into sync.
                            */

                            $stmt = $conn->prepare("
                                UPDATE tute_purchases

                                SET purchase_status = 'completed'

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


                            throw new RuntimeException(
                                'This payment has already been confirmed. The tute is now available in your account.'
                            );
                        }


                        if (
                            $existingPayment &&
                            $existingPayment['payment_status']
                            === 'pending'
                        ) {

                            throw new RuntimeException(
                                'A payment for this tute is already pending confirmation.'
                            );
                        }


                        $referenceValue =
                            $referenceNo !== ''
                                ? $referenceNo
                                : null;


                        $conn->begin_transaction();


                        if ($existingPayment) {

                            /*
                            |--------------------------------------------------------------------------
                            | RESUBMIT FAILED/CANCELLED/REFUNDED PAYMENT
                            |--------------------------------------------------------------------------
                            */

                            $paymentId =
                                (int) $existingPayment['payment_id'];


                            $stmt = $conn->prepare("
                                UPDATE payments

                                SET
                                    amount = ?,
                                    payment_method = ?,
                                    payment_date = CURRENT_TIMESTAMP,
                                    payment_status = 'pending',
                                    receipt_no = NULL,
                                    reference_no = ?,
                                    payment_type = 'tute'

                                WHERE
                                    payment_id = ?
                                    AND student_id = ?
                            ");

                            $stmt->bind_param(
                                "dssii",
                                $amount,
                                $paymentMethod,
                                $referenceValue,
                                $paymentId,
                                $studentId
                            );

                            $stmt->execute();
                            $stmt->close();


                            $stmt = $conn->prepare("
                                UPDATE tute_purchases

                                SET
                                    purchase_status = 'pending',
                                    amount = ?,
                                    purchase_date = CURRENT_TIMESTAMP

                                WHERE
                                    purchase_id = ?
                                    AND student_id = ?
                            ");

                            $stmt->bind_param(
                                "dii",
                                $amount,
                                $purchaseId,
                                $studentId
                            );

                            $stmt->execute();
                            $stmt->close();

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | NEW PAYMENT
                            |--------------------------------------------------------------------------
                            */

                            $stmt = $conn->prepare("
                                INSERT INTO payments
                                (
                                    student_id,
                                    amount,
                                    payment_method,
                                    payment_status,
                                    reference_no,
                                    payment_type
                                )

                                VALUES
                                (
                                    ?,
                                    ?,
                                    ?,
                                    'pending',
                                    ?,
                                    'tute'
                                )
                            ");

                            $stmt->bind_param(
                                "idss",
                                $studentId,
                                $amount,
                                $paymentMethod,
                                $referenceValue
                            );

                            $stmt->execute();

                            $paymentId =
                                (int) $conn->insert_id;

                            $stmt->close();


                            $stmt = $conn->prepare("
                                INSERT INTO tute_payments
                                (
                                    payment_id,
                                    purchase_id
                                )

                                VALUES
                                (
                                    ?,
                                    ?
                                )
                            ");

                            $stmt->bind_param(
                                "ii",
                                $paymentId,
                                $purchaseId
                            );

                            $stmt->execute();
                            $stmt->close();
                        }


                        $conn->commit();


                        $_SESSION['payment_success'] =
                            'Payment request submitted for "' .
                            $purchase['title'] .
                            '". It is waiting for staff confirmation.';


                        header(
                            "Location: payments.php"
                        );

                        exit();

                    } catch (RuntimeException $e) {

                        try {
                            $conn->rollback();
                        } catch (Throwable $ignored) {
                        }

                        $error = $e->getMessage();

                    } catch (Throwable $e) {

                        try {
                            $conn->rollback();
                        } catch (Throwable $ignored) {
                        }

                        $error =
                            'The tute payment request could not be created. Please try again.';
                    }
                }
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| SELECTED TUTE PURCHASE FROM STEP 14
|--------------------------------------------------------------------------
*/

$selectedPurchaseId =
    isset($_GET['purchase_id'])
        ? (int) $_GET['purchase_id']
        : 0;

$selectedPurchase = null;


if ($selectedPurchaseId > 0) {

    $stmt = $conn->prepare("
        SELECT
            tp.purchase_id,
            tp.amount,
            tp.purchase_status,

            lr.title,

            m.module_name,

            c.course_name,

            p.payment_id,
            p.payment_status,
            p.payment_method,
            p.reference_no

        FROM tute_purchases tp

        INNER JOIN learning_resources lr
            ON lr.resource_id = tp.tute_resource_id

        INNER JOIN modules m
            ON m.module_id = lr.module_id

        INNER JOIN courses c
            ON c.course_id = m.course_id

        LEFT JOIN tute_payments tpay
            ON tpay.purchase_id = tp.purchase_id

        LEFT JOIN payments p
            ON p.payment_id = tpay.payment_id
            AND p.student_id = tp.student_id

        WHERE
            tp.purchase_id = ?
            AND tp.student_id = ?

        LIMIT 1
    ");

    $stmt->bind_param(
        "ii",
        $selectedPurchaseId,
        $studentId
    );

    $stmt->execute();

    $selectedPurchase =
        $stmt
        ->get_result()
        ->fetch_assoc();

    $stmt->close();


    if (!$selectedPurchase) {

        $selectedPurchaseId = 0;

    } elseif (
        $selectedPurchase['purchase_status']
        === 'completed'
    ) {

        $selectedPurchaseId = 0;
        $selectedPurchase = null;
    }
}

/*
|--------------------------------------------------------------------------
| LOAD COURSE FEE STATUS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        e.enrollment_id,
        e.enrollment_status,
        e.enrollment_date,

        b.batch_id,
        b.batch_name,

        c.course_id,
        c.course_name,
        c.course_fee,

        COALESCE(
            SUM(
                CASE
                    WHEN p.payment_status = 'paid'
                    THEN p.amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_amount,

        COALESCE(
            SUM(
                CASE
                    WHEN p.payment_status = 'pending'
                    THEN p.amount
                    ELSE 0
                END
            ),
            0
        ) AS pending_amount

    FROM enrollments e

    INNER JOIN batches b
        ON b.batch_id = e.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    LEFT JOIN course_payments cp
        ON cp.enrollment_id = e.enrollment_id

    LEFT JOIN payments p
        ON p.payment_id = cp.payment_id
        AND p.student_id = e.student_id
        AND p.payment_type = 'course'

    WHERE
        e.student_id = ?
        AND e.enrollment_status IN
        (
            'Pending',
            'Active',
            'Completed'
        )

    GROUP BY
        e.enrollment_id,
        e.enrollment_status,
        e.enrollment_date,

        b.batch_id,
        b.batch_name,

        c.course_id,
        c.course_name,
        c.course_fee

    ORDER BY
        c.course_name,
        b.batch_name
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$courseFees =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

/*
|--------------------------------------------------------------------------
| LOAD PENDING TUTE PURCHASES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        tp.purchase_id,
        tp.amount,
        tp.purchase_date,
        tp.purchase_status,

        lr.title,

        m.module_name,

        c.course_name,

        p.payment_id,
        p.payment_status,
        p.payment_method,
        p.reference_no

    FROM tute_purchases tp

    INNER JOIN learning_resources lr
        ON lr.resource_id = tp.tute_resource_id

    INNER JOIN modules m
        ON m.module_id = lr.module_id

    INNER JOIN courses c
        ON c.course_id = m.course_id

    LEFT JOIN tute_payments tpay
        ON tpay.purchase_id = tp.purchase_id

    LEFT JOIN payments p
        ON p.payment_id = tpay.payment_id

    WHERE
        tp.student_id = ?
        AND tp.purchase_status = 'pending'
        AND tp.amount > 0

    ORDER BY
        tp.purchase_date DESC,
        tp.purchase_id DESC
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$pendingTutePurchases =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

/*
|--------------------------------------------------------------------------
| PAYMENT HISTORY
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        p.payment_id,
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
        p.student_id = ?

    ORDER BY
        p.payment_date DESC,
        p.payment_id DESC
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$payments =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalPayments = count($payments);
$completedPayments = 0;
$pendingPayments = 0;
$totalSpent = 0.00;

foreach ($payments as $payment) {

    if ($payment['payment_status'] === 'paid') {

        $completedPayments++;

        $totalSpent +=
            (float) $payment['amount'];
    }

    if ($payment['payment_status'] === 'pending') {
        $pendingPayments++;
    }
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
        | Payments
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
                        Payments
                    </p>

                    <h1>
                        Payment Management
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
                            Payment Overview
                        </p>

                        <h2>
                            Track course fees, tute purchases,
                            payment history and receipts.
                        </h2>

                    </div>

                    <div class="summary-icon">

                        <i class="fas fa-credit-card"></i>

                    </div>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Paid
                        </p>

                        <h3>
                            <?php echo $completedPayments; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        Confirmed payments
                    </span>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Pending
                        </p>

                        <h3>
                            <?php echo $pendingPayments; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        Awaiting confirmation
                    </span>

                </article>


                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Total Paid
                        </p>

                        <h3 class="payment-total-value">

                            LKR
                            <?php echo money($totalSpent); ?>

                        </h3>

                    </div>

                    <span class="stat-badge">
                        Confirmed total
                    </span>

                </article>


            </section>


            <!-- =================================================
                 SUCCESS
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
                 ERROR
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
                 SELECTED TUTE PAYMENT
                 ================================================= -->

            <?php if ($selectedPurchase !== null): ?>

                <section class="payment-section">

                    <div class="payment-section-header">

                        <div>

                            <p class="card-label">
                                Digital Tute Payment
                            </p>

                            <h2>
                                Complete Payment Details
                            </h2>

                            <p>
                                LearnFlow does not process an online
                                gateway payment here. Submit the payment
                                information for staff confirmation.
                            </p>

                        </div>

                    </div>


                    <div
                        class="dashboard-card"
                        style="margin-bottom:20px;"
                    >

                        <p class="card-label">

                            <?php
                            echo e(
                                $selectedPurchase['course_name']
                            );
                            ?>

                        </p>

                        <h3>

                            <?php
                            echo e(
                                $selectedPurchase['title']
                            );
                            ?>

                        </h3>

                        <p>

                            <strong>
                                Module:
                            </strong>

                            <?php
                            echo e(
                                $selectedPurchase['module_name']
                            );
                            ?>

                        </p>

                        <p>

                            <strong>
                                Amount:
                            </strong>

                            LKR
                            <?php
                            echo money(
                                $selectedPurchase['amount']
                            );
                            ?>

                        </p>

                    </div>


                    <?php
                    if (
                        ($selectedPurchase['payment_status'] ?? '')
                        === 'pending'
                    ):
                    ?>

                        <div
                            style="
                                padding:14px 16px;
                                border-radius:12px;
                                background:#fff7ed;
                                color:#9a3412;
                                border:1px solid #fed7aa;
                            "
                        >

                            <i class="fas fa-clock"></i>

                            A payment request already exists and is
                            waiting for staff confirmation.

                        </div>

                    <?php else: ?>


                        <form
                            method="POST"
                            action="payments.php?purchase_id=<?php
                            echo (int) $selectedPurchase['purchase_id'];
                            ?>"
                            class="payment-form"
                        >

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?php
                                echo e(
                                    $_SESSION['csrf_token']
                                );
                                ?>"
                            >

                            <input
                                type="hidden"
                                name="purchase_id"
                                value="<?php
                                echo (int)
                                    $selectedPurchase['purchase_id'];
                                ?>"
                            >


                            <div class="form-grid">


                                <div class="form-group">

                                    <label for="tutePaymentMethod">

                                        Payment Method

                                    </label>

                                    <div class="input-wrapper">

                                        <i class="fas fa-wallet"></i>

                                        <select
                                            id="tutePaymentMethod"
                                            name="payment_method"
                                            required
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

                                            <option value="">
                                                Select payment method
                                            </option>

                                            <option value="bank_transfer">
                                                Bank Transfer
                                            </option>

                                            <option value="cash">
                                                Cash
                                            </option>

                                            <option value="card">
                                                Card Record
                                            </option>

                                            <option value="other">
                                                Other
                                            </option>

                                        </select>

                                    </div>

                                </div>


                                <div class="form-group">

                                    <label for="tuteReferenceNo">

                                        Reference Number

                                    </label>

                                    <div class="input-wrapper">

                                        <i class="fas fa-hashtag"></i>

                                        <input
                                            type="text"
                                            id="tuteReferenceNo"
                                            name="reference_no"
                                            maxlength="150"
                                            placeholder="Bank slip / transaction reference"
                                        >

                                    </div>

                                </div>


                            </div>


                            <div class="form-actions">

                                <a
                                    href="tute_store.php"
                                    class="secondary-btn"
                                >

                                    <i class="fas fa-arrow-left"></i>

                                    Back to Store

                                </a>


                                <button
                                    type="submit"
                                    name="pay_tute"
                                    class="primary-btn"
                                >

                                    <i class="fas fa-paper-plane"></i>

                                    Submit Payment Request

                                </button>

                            </div>

                        </form>


                    <?php endif; ?>


                </section>

            <?php endif; ?>


            <!-- =================================================
                 COURSE FEES
                 ================================================= -->

            <section
                class="payment-section"
                style="margin-top:24px;"
            >

                <div class="payment-section-header">

                    <div>

                        <p class="card-label">
                            Course Fees
                        </p>

                        <h2>
                            Course Payment Status
                        </h2>

                        <p>
                            Confirmed payments reduce the outstanding
                            balance. Pending requests remain visible
                            until reviewed by staff.
                        </p>

                    </div>

                </div>


                <?php if (empty($courseFees)): ?>

                    <div class="course-empty-state">

                        <div class="course-empty-icon">

                            <i class="fas fa-book-open"></i>

                        </div>

                        <h3>
                            No Course Fees
                        </h3>

                        <p>
                            No enrolled courses are currently available.
                        </p>

                    </div>

                <?php else: ?>


                    <div class="card-grid">


                        <?php foreach ($courseFees as $courseFee): ?>


                            <?php

                            $fee =
                                (float) $courseFee['course_fee'];

                            $paid =
                                (float) $courseFee['paid_amount'];

                            $pending =
                                (float) $courseFee['pending_amount'];

                            $outstanding =
                                max(
                                    0,
                                    $fee - $paid
                                );

                            ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-blue">

                                    <i class="fas fa-book"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        $courseFee['batch_name']
                                    );
                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $courseFee['course_name']
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Course Fee:
                                    </strong>

                                    LKR
                                    <?php echo money($fee); ?>

                                </p>


                                <p>

                                    <strong>
                                        Confirmed Paid:
                                    </strong>

                                    LKR
                                    <?php echo money($paid); ?>

                                </p>


                                <p>

                                    <strong>
                                        Outstanding:
                                    </strong>

                                    LKR
                                    <?php echo money($outstanding); ?>

                                </p>


                                <?php if ($pending > 0): ?>

                                    <p>

                                        <strong>
                                            Pending Confirmation:
                                        </strong>

                                        LKR
                                        <?php echo money($pending); ?>

                                    </p>

                                <?php endif; ?>


                                <?php if ($outstanding <= 0): ?>

                                    <p>
                                        <strong>Status:</strong>
                                        Paid in Full
                                    </p>

                                <?php elseif ($pending > 0): ?>

                                    <p>
                                        <strong>Status:</strong>
                                        Payment Pending
                                    </p>

                                <?php else: ?>


                                    <form
                                        method="POST"
                                        action="payments.php"
                                        class="profile-form"
                                        style="margin-top:16px;"
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
                                            name="enrollment_id"
                                            value="<?php
                                            echo (int)
                                                $courseFee[
                                                    'enrollment_id'
                                                ];
                                            ?>"
                                        >


                                        <div class="form-grid">


                                            <div class="form-group">

                                                <label>
                                                    Amount
                                                </label>

                                                <div class="input-wrapper">

                                                    <i class="fas fa-money-bill-wave"></i>

                                                    <input
                                                        type="number"
                                                        name="amount"
                                                        min="0.01"
                                                        max="<?php
                                                        echo e(
                                                            number_format(
                                                                $outstanding,
                                                                2,
                                                                '.',
                                                                ''
                                                            )
                                                        );
                                                        ?>"
                                                        step="0.01"
                                                        value="<?php
                                                        echo e(
                                                            number_format(
                                                                $outstanding,
                                                                2,
                                                                '.',
                                                                ''
                                                            )
                                                        );
                                                        ?>"
                                                        required
                                                    >

                                                </div>

                                            </div>


                                            <div class="form-group">

                                                <label>
                                                    Payment Method
                                                </label>

                                                <div class="input-wrapper">

                                                    <i class="fas fa-wallet"></i>

                                                    <select
                                                        name="payment_method"
                                                        required
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

                                                        <option value="">
                                                            Select method
                                                        </option>

                                                        <option value="bank_transfer">
                                                            Bank Transfer
                                                        </option>

                                                        <option value="cash">
                                                            Cash
                                                        </option>

                                                        <option value="card">
                                                            Card Record
                                                        </option>

                                                        <option value="other">
                                                            Other
                                                        </option>

                                                    </select>

                                                </div>

                                            </div>


                                            <div class="form-group full-width">

                                                <label>
                                                    Reference Number
                                                </label>

                                                <div class="input-wrapper">

                                                    <i class="fas fa-hashtag"></i>

                                                    <input
                                                        type="text"
                                                        name="reference_no"
                                                        maxlength="150"
                                                        placeholder="Optional payment/slip reference"
                                                    >

                                                </div>

                                            </div>


                                        </div>


                                        <div
                                            class="form-actions"
                                            style="justify-content:flex-start;"
                                        >

                                            <button
                                                type="submit"
                                                name="pay_course"
                                                class="primary-btn"
                                            >

                                                <i class="fas fa-paper-plane"></i>

                                                Submit Payment Request

                                            </button>

                                        </div>

                                    </form>


                                <?php endif; ?>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


            <!-- =================================================
                 PENDING TUTE PURCHASES
                 ================================================= -->

            <section
                class="payment-section"
                style="margin-top:24px;"
            >

                <div class="payment-section-header">

                    <div>

                        <p class="card-label">
                            Digital Tutes
                        </p>

                        <h2>
                            Pending Tute Purchases
                        </h2>

                    </div>

                </div>


                <?php if (empty($pendingTutePurchases)): ?>

                    <div class="course-empty-state">

                        <div class="course-empty-icon">

                            <i class="fas fa-circle-check"></i>

                        </div>

                        <h3>
                            No Pending Tute Purchases
                        </h3>

                        <p>
                            Your paid or available tutes can be
                            viewed from the Tute Store.
                        </p>

                    </div>

                <?php else: ?>


                    <div class="card-grid">


                        <?php foreach ($pendingTutePurchases as $purchase): ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-purple">

                                    <i class="fas fa-file-pdf"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        $purchase['course_name']
                                    );
                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $purchase['title']
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Amount:
                                    </strong>

                                    LKR
                                    <?php
                                    echo money(
                                        $purchase['amount']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Purchase:
                                    </strong>

                                    Pending

                                </p>


                                <?php
                                if (
                                    !empty(
                                        $purchase['payment_status']
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
                                                $purchase[
                                                    'payment_status'
                                                ]
                                            )
                                        );
                                        ?>

                                    </p>

                                <?php endif; ?>


                                <div
                                    class="form-actions"
                                    style="justify-content:flex-start;"
                                >

                                    <a
                                        href="payments.php?purchase_id=<?php
                                        echo (int)
                                            $purchase[
                                                'purchase_id'
                                            ];
                                        ?>"
                                        class="primary-btn"
                                    >

                                        <i class="fas fa-credit-card"></i>

                                        <?php
                                        echo (
                                            $purchase[
                                                'payment_status'
                                            ]
                                            === 'pending'
                                        )
                                            ? 'View Payment'
                                            : 'Pay Now';
                                        ?>

                                    </a>

                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


            <!-- =================================================
                 PAYMENT HISTORY
                 ================================================= -->

            <section
                class="payment-section"
                style="margin-top:24px;"
            >

                <div class="payment-section-header">

                    <div>

                        <p class="card-label">
                            History
                        </p>

                        <h2>
                            Payment History
                        </h2>

                        <p>
                            All payment records belonging to your
                            Student account are shown here.
                        </p>

                    </div>


                    <a
                        href="receipts.php"
                        class="secondary-btn"
                    >

                        <i class="fas fa-receipt"></i>

                        View Receipts

                    </a>

                </div>


                <?php if (empty($payments)): ?>

                    <div class="course-empty-state">

                        <div class="course-empty-icon">

                            <i class="fas fa-credit-card"></i>

                        </div>

                        <h3>
                            No Payment Records
                        </h3>

                        <p>
                            You have not submitted any payments yet.
                        </p>

                    </div>

                <?php else: ?>


                    <div class="card-grid">


                        <?php foreach ($payments as $payment): ?>


                            <?php

                            if (
                                $payment['payment_type']
                                === 'course'
                            ) {

                                $paymentTitle =
                                    $payment[
                                        'course_payment_course'
                                    ]
                                    ?: 'Course Payment';

                                $paymentSubtitle =
                                    $payment[
                                        'course_payment_batch'
                                    ]
                                    ?: 'Course Fee';

                            } else {

                                $paymentTitle =
                                    $payment['tute_title']
                                    ?: 'Digital Tute';

                                $paymentSubtitle =
                                    $payment['tute_course']
                                    ?: 'Tute Purchase';
                            }

                            ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-green">

                                    <i class="fas fa-money-check-dollar"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        strtoupper(
                                            $payment['payment_type']
                                        )
                                    );
                                    ?>

                                    PAYMENT

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $paymentTitle
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <?php
                                    echo e(
                                        $paymentSubtitle
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Amount:
                                    </strong>

                                    LKR
                                    <?php
                                    echo money(
                                        $payment['amount']
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
                                            $payment[
                                                'payment_method'
                                            ]
                                        )
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Date:
                                    </strong>

                                    <?php
                                    echo e(
                                        $payment['payment_date']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Status:
                                    </strong>

                                    <?php
                                    echo e(
                                        prettyStatus(
                                            $payment[
                                                'payment_status'
                                            ]
                                        )
                                    );
                                    ?>

                                </p>


                                <?php
                                if (
                                    !empty(
                                        $payment['reference_no']
                                    )
                                ):
                                ?>

                                    <p>

                                        <strong>
                                            Reference:
                                        </strong>

                                        <?php
                                        echo e(
                                            $payment[
                                                'reference_no'
                                            ]
                                        );
                                        ?>

                                    </p>

                                <?php endif; ?>


                                <?php
                                if (
                                    $payment[
                                        'payment_status'
                                    ] === 'paid'
                                ):
                                ?>

                                    <div
                                        class="form-actions"
                                        style="
                                            justify-content:flex-start;
                                        "
                                    >

                                        <a
                                            href="receipts.php?payment_id=<?php
                                            echo (int)
                                                $payment[
                                                    'payment_id'
                                                ];
                                            ?>"
                                            class="primary-btn"
                                        >

                                            <i class="fas fa-receipt"></i>

                                            View Receipt

                                        </a>

                                    </div>

                                <?php endif; ?>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


        </main>

    </div>

</div>


<script src="../js/student.js"></script>


</body>

</html>
