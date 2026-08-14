<?php

session_start();

include "../config/db.php";

if (
    !isset($_SESSION['user_id'], $_SESSION['role'])
    || $_SESSION['role'] !== 'Parent'
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION['user_id'];

$ensureParent = $conn->prepare("INSERT IGNORE INTO parent (ParentID) VALUES (?)");
$ensureParent->bind_param("i", $parentId);
$ensureParent->execute();
$ensureParent->close();

$parentStmt = $conn->prepare(
    "SELECT u.UserID, u.Name
     FROM users u
     INNER JOIN parent p ON p.ParentID = u.UserID
     WHERE u.UserID = ? AND u.Role = 'Parent'
     LIMIT 1"
);
$parentStmt->bind_param("i", $parentId);
$parentStmt->execute();
$parentResult = $parentStmt->get_result();
$parent = $parentResult ? $parentResult->fetch_assoc() : null;
$parentStmt->close();

if (!$parent) {
    header("Location: ../auth/login.php");
    exit();
}

$studentStmt = $conn->prepare(
    "SELECT s.StudentID, su.Name AS StudentName
     FROM parent_student ps
     INNER JOIN student s ON s.StudentID = ps.StudentID
     INNER JOIN users su ON su.UserID = s.StudentID
     WHERE ps.ParentID = ?
     LIMIT 1"
);
$studentStmt->bind_param("i", $parentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$linkedStudent = $studentResult ? $studentResult->fetch_assoc() : null;
$studentStmt->close();

function parent_initials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    if (!empty($parts[0])) {
        $initials .= strtoupper(substr($parts[0], 0, 1));
    }

    if (count($parts) > 1 && !empty($parts[count($parts) - 1])) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }

    return $initials !== '' ? $initials : 'P';
}

function payments_course_slug($courseName)
{
    $slug = strtolower(trim((string) $courseName));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'course';
}

function payments_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

function payments_format_lkr($amount)
{
    return 'LKR ' . number_format((float) $amount, 0);
}

function payments_format_date($date, $format = 'd M Y')
{
    $ts = $date ? strtotime((string) $date) : false;
    return $ts ? date($format, $ts) : '';
}

function payments_display_status($status, $dueDate)
{
    if ($status === 'Paid') {
        return 'Paid';
    }

    $dueTs = $dueDate ? strtotime((string) $dueDate) : false;
    $today = strtotime(date('Y-m-d'));

    if ($dueTs && $dueTs < $today) {
        return 'Overdue';
    }

    return 'Due';
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$paymentRows = [];
$courseOptions = [];
$outstanding = 0.0;
$totalPaid = 0.0;
$nextDueTs = null;
$studentFirstName = 'Student';

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = payments_first_name($linkedStudent['StudentName']);

    $paymentStmt = $conn->prepare(
        "SELECT p.PaymentID, p.Amount, p.PaymentDate, p.PaymentMethod, p.Status,
                p.ItemTitle, p.DueDate, p.ReceiptNumber, p.CourseID, c.CourseName
         FROM payment p
         INNER JOIN course c ON c.CourseID = p.CourseID
         WHERE p.StudentID = ?
         ORDER BY COALESCE(p.PaymentDate, p.DueDate) DESC, p.PaymentID DESC"
    );
    $paymentStmt->bind_param("i", $studentId);
    $paymentStmt->execute();
    $paymentResult = $paymentStmt->get_result();

    while ($row = $paymentResult->fetch_assoc()) {
        $displayStatus = payments_display_status($row['Status'], $row['DueDate']);
        $amount = (float) $row['Amount'];
        $courseId = (int) $row['CourseID'];
        $courseOptions[$courseId] = $row['CourseName'];

        if ($displayStatus === 'Paid') {
            $totalPaid += $amount;
            $dateLabel = 'Paid: ' . payments_format_date($row['PaymentDate']);
        } else {
            $outstanding += $amount;
            $dateLabel = 'Due: ' . payments_format_date($row['DueDate']);
            $dueTs = $row['DueDate'] ? strtotime((string) $row['DueDate']) : false;
            if ($dueTs && ($nextDueTs === null || $dueTs < $nextDueTs)) {
                $nextDueTs = $dueTs;
            }
        }

        $paymentRows[] = [
            'item_title' => $row['ItemTitle'] ?: 'Tuition Fee',
            'course_name' => $row['CourseName'],
            'course_slug' => payments_course_slug($row['CourseName']),
            'amount_label' => payments_format_lkr($amount),
            'date_label' => $dateLabel,
            'status' => $displayStatus,
            'status_slug' => strtolower($displayStatus),
            'is_paid' => $displayStatus === 'Paid',
        ];
    }

    $paymentStmt->close();
}

$hasRecords = count($paymentRows) > 0;
$pageTitle = htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8') . "'s Payment Status";
$nextDueLabel = $nextDueTs ? date('d M', $nextDueTs) : '—';

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Payment Status | LEARNFLOW</title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <!-- Parent CSS -->

    <link
        rel="stylesheet"
        href="../css/parent.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        <!-- Brand -->

        <div class="sidebar-brand">

            <i class="fa-solid fa-graduation-cap"></i>

            <span>LEARNFLOW</span>

        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">


            <!-- Main -->

            <div class="nav-section-title">
                Main
            </div>


            <a
                href="dashboard.php"
                class="nav-link"
            >

                <i class="fa-solid fa-house"></i>

                <span>Dashboard</span>

            </a>


            <a
                href="profile.php"
                class="nav-link"
            >

                <i class="fa-solid fa-user"></i>

                <span>My Profile</span>

            </a>


            <!-- Student Monitoring -->

            <div class="nav-section-title">
                Student Monitoring
            </div>


            <a
                href="attendance.php"
                class="nav-link"
            >

                <i class="fa-solid fa-calendar-check"></i>

                <span>Attendance</span>

            </a>


            <a
                href="progress.php"
                class="nav-link"
            >

                <i class="fa-solid fa-chart-line"></i>

                <span>Academic Progress</span>

            </a>


            <a
                href="assignments.php"
                class="nav-link"
            >

                <i class="fa-solid fa-file-pen"></i>

                <span>Assignments</span>

            </a>


            <a
                href="results.php"
                class="nav-link"
            >

                <i class="fa-solid fa-award"></i>

                <span>Examination Results</span>

            </a>


            <a
                href="courses.php"
                class="nav-link"
            >

                <i class="fa-solid fa-book-open"></i>

                <span>Course Participation</span>

            </a>


            <!-- Communication -->

            <div class="nav-section-title">
                Communication
            </div>


            <a
                href="announcements.php"
                class="nav-link"
            >

                <i class="fa-solid fa-bullhorn"></i>

                <span>Announcements</span>

            </a>


            <a
                href="contact.php"
                class="nav-link"
            >

                <i class="fa-solid fa-comments"></i>

                <span>Contact Teachers/Admins</span>

            </a>


            <a
                href="meetings.php"
                class="nav-link"
            >

                <i class="fa-solid fa-handshake"></i>

                <span>Request Meeting</span>

            </a>


            <!-- Payments -->

            <div class="nav-section-title">
                Payments
            </div>


            <a
                href="payments.php"
                class="nav-link active"
            >

                <i class="fa-solid fa-credit-card"></i>

                <span>Payment Status</span>

            </a>


            <a
                href="receipts.php"
                class="nav-link"
            >

                <i class="fa-solid fa-receipt"></i>

                <span>Payment Receipts</span>

            </a>


            <!-- Settings -->

            <div class="nav-section-title">
                Settings
            </div>


            <a
                href="emergency-contact.php"
                class="nav-link"
            >

                <i class="fa-solid fa-phone-volume"></i>

                <span>Emergency Contact</span>

            </a>


        </nav>


        <!-- Sidebar Footer -->

        <div class="sidebar-footer">

            <a
                href="../auth/logout.php"
                class="nav-link"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

            </a>

        </div>


    </aside>



    <!-- =================================================
         MAIN AREA
    ================================================== -->

    <main class="main-area">


        <!-- TOP NAVBAR -->

        <header class="top-navbar">


            <div class="page-title">


                <div class="profile-top-title">


                    <button
                        class="menu-toggle"
                        id="menuToggle"
                    >

                        <i class="fa-solid fa-bars"></i>

                    </button>


                    <div>

                        <h2>Payment Status</h2>

                        <p>
                            Track tuition fees and payment dues for your child.
                        </p>

                    </div>


                </div>


            </div>



            <!-- User Area -->

            <div class="user-area">


                <button
                    class="notification-btn"
                    id="notificationButton"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-badge"></span>

                </button>


                <div class="user-profile">


                    <div class="user-avatar">

                        <?php echo htmlspecialchars($parentInitials, ENT_QUOTES, 'UTF-8'); ?>

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            <?php echo htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="user-role">
                            Parent
                        </span>

                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="payments-page-header">


                <div>

                    <h1>
                        <?php echo $pageTitle; ?>
                    </h1>

                    <p>
                        Overview of tuition fees, dues and payment history.
                    </p>

                </div>


                <div class="payments-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo htmlspecialchars(payments_format_lkr($outstanding), ENT_QUOTES, 'UTF-8'); ?>
                        </strong>

                        <span>
                            Outstanding
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo htmlspecialchars(payments_format_lkr($totalPaid), ENT_QUOTES, 'UTF-8'); ?>
                        </strong>

                        <span>
                            Total Paid
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo htmlspecialchars($nextDueLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </strong>

                        <span>
                            Next Due
                        </span>

                    </div>


                </div>


            </div>



            <!-- FILTERS -->

            <div class="payments-filter-card">


                <select
                    id="courseFilter"
                    class="payments-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <?php foreach ($courseOptions as $courseName): ?>
                        <option value="<?php echo htmlspecialchars(payments_course_slug($courseName), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>

                </select>



                <select
                    id="statusFilter"
                    class="payments-filter"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="paid">
                        Paid
                    </option>

                    <option value="due">
                        Due
                    </option>

                    <option value="overdue">
                        Overdue
                    </option>

                </select>


            </div>



            <!-- PAYMENTS TABLE -->

            <div
                class="payments-table-card"
                <?php echo $hasRecords ? '' : 'style="display: none;"'; ?>
            >


                <table
                    class="payments-table"
                    id="paymentsTable"
                >


                    <thead>

                        <tr>

                            <th>Item</th>

                            <th>Course</th>

                            <th>Amount</th>

                            <th>Due / Paid Date</th>

                            <th>Status</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($paymentRows as $row): ?>
                            <tr
                                class="payments-row"
                                data-course="<?php echo htmlspecialchars($row['course_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-status="<?php echo htmlspecialchars($row['status_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                            >

                                <td>

                                    <span class="payments-item">

                                        <i class="fa-solid fa-file-invoice-dollar"></i>

                                        <?php echo htmlspecialchars($row['item_title'], ENT_QUOTES, 'UTF-8'); ?>

                                    </span>

                                </td>

                                <td><?php echo htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td class="payments-amount"><?php echo htmlspecialchars($row['amount_label'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td><?php echo htmlspecialchars($row['date_label'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td>

                                    <span class="status-badge status-<?php echo htmlspecialchars($row['status_slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>

                                </td>

                                <td>

                                    <?php if ($row['is_paid']): ?>
                                        <a
                                            href="receipts.php"
                                            class="view-receipt-link"
                                        >

                                            View Receipt

                                        </a>
                                    <?php else: ?>
                                        <button
                                            type="button"
                                            class="pay-now-btn"
                                        >

                                            Pay Now

                                        </button>
                                    <?php endif; ?>

                                </td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>


                </table>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noPayments"
                class="no-results"
                <?php echo $hasRecords ? '' : 'style="display: flex;"'; ?>
            >

                <i class="fa-solid fa-credit-card"></i>

                <h3>
                    No Payment Records Found
                </h3>

                <p>
                    <?php echo $hasRecords
                        ? 'Try changing your filter options.'
                        : 'There are no payment records to show yet.'; ?>
                </p>

            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Payments JavaScript -->

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        const courseFilter =
            document.getElementById("courseFilter");


        const statusFilter =
            document.getElementById("statusFilter");


        const paymentRows =
            document.querySelectorAll(".payments-row");


        const paymentsTableCard =
            document.querySelector(".payments-table-card");


        const noPayments =
            document.getElementById("noPayments");


        const emptyMessage =
            noPayments.querySelector("p");


        const payNowButtons =
            document.querySelectorAll(".pay-now-btn");


        const hasServerRecords =
            <?php echo $hasRecords ? 'true' : 'false'; ?>;



        function filterPayments() {


            const selectedCourse =
                courseFilter.value;


            const selectedStatus =
                statusFilter.value;


            let visibleCount = 0;



            paymentRows.forEach(
                function (row) {


                    const course =
                        row.getAttribute("data-course");


                    const status =
                        row.getAttribute("data-status");


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesStatus =
                        selectedStatus === "all" ||
                        status === selectedStatus;



                    if (matchesCourse && matchesStatus) {

                        row.style.display = "";

                        visibleCount++;

                    }

                    else {

                        row.style.display = "none";

                    }


                }
            );



            if (!hasServerRecords || visibleCount === 0) {

                paymentsTableCard.style.display = "none";

                noPayments.style.display = "flex";

                emptyMessage.textContent = hasServerRecords
                    ? "Try changing your filter options."
                    : "There are no payment records to show yet.";

            }

            else {

                paymentsTableCard.style.display = "block";

                noPayments.style.display = "none";

            }


        }



        courseFilter.addEventListener("change", filterPayments);


        statusFilter.addEventListener("change", filterPayments);



        /* -------------------------
           PAY NOW BUTTONS
        ------------------------- */

        payNowButtons.forEach(
            function (button) {


                button.addEventListener(
                    "click",
                    function () {


                        alert(
                            "The online payment gateway will be connected to the backend later."
                        );


                    }
                );


            }
        );


    }
);


</script>


</body>

</html>
