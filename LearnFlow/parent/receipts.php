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

function receipts_course_slug($courseName)
{
    $slug = strtolower(trim((string) $courseName));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'course';
}

function receipts_course_icon($courseName)
{
    $name = strtolower((string) $courseName);

    if (strpos($name, 'math') !== false) {
        return 'fa-calculator';
    }
    if (strpos($name, 'physics') !== false) {
        return 'fa-atom';
    }
    if (strpos($name, 'chemistry') !== false) {
        return 'fa-flask';
    }
    if (strpos($name, 'english') !== false) {
        return 'fa-language';
    }
    if (strpos($name, 'web') !== false || strpos($name, 'develop') !== false) {
        return 'fa-code';
    }

    return 'fa-book';
}

function receipts_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

function receipts_format_lkr($amount)
{
    return 'LKR ' . number_format((float) $amount, 0);
}

function receipts_format_date($date, $format = 'd M Y')
{
    $ts = $date ? strtotime((string) $date) : false;
    return $ts ? date($format, $ts) : '';
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$receiptRows = [];
$courseOptions = [];
$monthOptions = [];
$totalPaid = 0.0;
$currentYear = (int) date('Y');
$studentFirstName = 'Student';

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = receipts_first_name($linkedStudent['StudentName']);

    $receiptStmt = $conn->prepare(
        "SELECT p.PaymentID, p.Amount, p.PaymentDate, p.PaymentMethod,
                p.ItemTitle, p.ReceiptNumber, p.CourseID, c.CourseName
         FROM payment p
         INNER JOIN course c ON c.CourseID = p.CourseID
         WHERE p.StudentID = ?
           AND p.Status = 'Paid'
           AND p.ReceiptNumber IS NOT NULL
           AND p.ReceiptNumber <> ''
         ORDER BY p.PaymentDate DESC, p.PaymentID DESC"
    );
    $receiptStmt->bind_param("i", $studentId);
    $receiptStmt->execute();
    $receiptResult = $receiptStmt->get_result();

    $latestPaidTs = null;

    while ($row = $receiptResult->fetch_assoc()) {
        $amount = (float) $row['Amount'];
        $totalPaid += $amount;
        $courseId = (int) $row['CourseID'];
        $courseOptions[$courseId] = $row['CourseName'];

        $paidTs = $row['PaymentDate'] ? strtotime((string) $row['PaymentDate']) : false;
        $monthKey = $paidTs ? date('Y-m', $paidTs) : '';
        if ($paidTs) {
            $monthOptions[$monthKey] = date('F Y', $paidTs);
            if ($latestPaidTs === null || $paidTs > $latestPaidTs) {
                $latestPaidTs = $paidTs;
            }
        }

        $receiptNumber = (string) $row['ReceiptNumber'];

        $receiptRows[] = [
            'item_title' => $row['ItemTitle'] ?: 'Tuition Fee',
            'receipt_number' => $receiptNumber,
            'receipt_number_slug' => strtolower($receiptNumber),
            'course_name' => $row['CourseName'],
            'course_slug' => receipts_course_slug($row['CourseName']),
            'course_icon' => receipts_course_icon($row['CourseName']),
            'amount_label' => receipts_format_lkr($amount),
            'paid_on' => receipts_format_date($row['PaymentDate']),
            'method' => $row['PaymentMethod'] ?: '—',
            'month_key' => $monthKey,
        ];
    }

    $receiptStmt->close();

    if ($latestPaidTs) {
        $currentYear = (int) date('Y', $latestPaidTs);
    }
}

krsort($monthOptions);

$hasRecords = count($receiptRows) > 0;
$receiptCount = count($receiptRows);

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Payment Receipts | LEARNFLOW</title>


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
                class="nav-link"
            >

                <i class="fa-solid fa-credit-card"></i>

                <span>Payment Status</span>

            </a>


            <a
                href="receipts.php"
                class="nav-link active"
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

                        <h2>Payment Receipts</h2>

                        <p>
                            View and download receipts for all completed payments.
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

            <div class="receipts-page-header">


                <div>

                    <h1>
                        Payment Receipts
                    </h1>

                    <p>
                        A record of every completed payment made for <?php echo htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8'); ?>'s tuition.
                    </p>

                </div>


                <div class="receipts-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $receiptCount; ?>
                        </strong>

                        <span>
                            Total Receipts
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo htmlspecialchars(receipts_format_lkr($totalPaid), ENT_QUOTES, 'UTF-8'); ?>
                        </strong>

                        <span>
                            Total Paid
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $currentYear; ?>
                        </strong>

                        <span>
                            Current Year
                        </span>

                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="receipts-filter-card">


                <div class="receipts-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="receiptSearch"
                        placeholder="Search by receipt number..."
                    >


                </div>



                <select
                    id="courseFilter"
                    class="receipts-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <?php foreach ($courseOptions as $courseName): ?>
                        <option value="<?php echo htmlspecialchars(receipts_course_slug($courseName), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>

                </select>



                <select
                    id="monthFilter"
                    class="receipts-filter"
                >

                    <option value="all">
                        All Months
                    </option>

                    <?php foreach ($monthOptions as $monthKey => $monthLabel): ?>
                        <option value="<?php echo htmlspecialchars($monthKey, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>

                </select>


            </div>



            <!-- RECEIPT CARDS -->

            <div
                class="receipts-grid"
                id="receiptsGrid"
                <?php echo $hasRecords ? '' : 'style="display: none;"'; ?>
            >

                <?php foreach ($receiptRows as $row): ?>
                    <div
                        class="receipt-card"
                        data-course="<?php echo htmlspecialchars($row['course_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-month="<?php echo htmlspecialchars($row['month_key'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-number="<?php echo htmlspecialchars($row['receipt_number_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                    >


                        <div class="receipt-card-top">

                            <div class="receipt-icon">

                                <i class="fa-solid fa-receipt"></i>

                            </div>

                            <span class="status-badge status-paid">
                                Paid
                            </span>

                        </div>


                        <div class="receipt-card-body">

                            <h3>
                                <?php echo htmlspecialchars($row['item_title'], ENT_QUOTES, 'UTF-8'); ?>
                            </h3>

                            <span class="receipt-number">
                                Receipt #<?php echo htmlspecialchars($row['receipt_number'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>

                            <p class="receipt-course">

                                <i class="fa-solid <?php echo htmlspecialchars($row['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                <?php echo htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'); ?>

                            </p>

                            <div class="receipt-meta">

                                <div class="receipt-meta-row">
                                    <span>Amount</span>
                                    <span><?php echo htmlspecialchars($row['amount_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <div class="receipt-meta-row">
                                    <span>Paid On</span>
                                    <span><?php echo htmlspecialchars($row['paid_on'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <div class="receipt-meta-row">
                                    <span>Method</span>
                                    <span><?php echo htmlspecialchars($row['method'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                            </div>

                        </div>


                        <div class="receipt-card-footer">

                            <button
                                class="receipt-download-btn"
                                onclick="downloadReceipt('<?php echo htmlspecialchars($row['receipt_number'], ENT_QUOTES, 'UTF-8'); ?>')"
                            >

                                <i class="fa-solid fa-download"></i>

                                Download PDF

                            </button>

                        </div>


                    </div>
                <?php endforeach; ?>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noReceipts"
                class="no-results"
                <?php echo $hasRecords ? '' : 'style="display: flex;"'; ?>
            >

                <i class="fa-solid fa-receipt"></i>

                <h3>
                    No Receipts Found
                </h3>

                <p>
                    <?php echo $hasRecords
                        ? 'Try changing your search or filter options.'
                        : 'There are no payment receipts to show yet.'; ?>
                </p>

            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Receipts JavaScript -->

<script>


const searchInput =
    document.getElementById("receiptSearch");


const courseFilter =
    document.getElementById("courseFilter");


const monthFilter =
    document.getElementById("monthFilter");


const receiptCards =
    document.querySelectorAll(".receipt-card");


const receiptsGrid =
    document.getElementById("receiptsGrid");


const noReceipts =
    document.getElementById("noReceipts");


const emptyMessage =
    noReceipts.querySelector("p");


const hasServerRecords =
    <?php echo $hasRecords ? 'true' : 'false'; ?>;



function filterReceipts() {


    const searchValue =
        searchInput.value
            .toLowerCase()
            .trim();


    const selectedCourse =
        courseFilter.value;


    const selectedMonth =
        monthFilter.value;


    let visibleCount = 0;



    receiptCards.forEach(
        function (card) {


            const number =
                card.getAttribute("data-number") || "";


            const course =
                card.getAttribute("data-course");


            const month =
                card.getAttribute("data-month");


            const matchesSearch =
                searchValue === "" ||
                number.includes(searchValue);


            const matchesCourse =
                selectedCourse === "all" ||
                course === selectedCourse;


            const matchesMonth =
                selectedMonth === "all" ||
                month === selectedMonth;



            if (
                matchesSearch &&
                matchesCourse &&
                matchesMonth
            ) {

                card.style.display = "flex";

                visibleCount++;

            }

            else {

                card.style.display = "none";

            }


        }
    );



    if (!hasServerRecords || visibleCount === 0) {

        receiptsGrid.style.display = "none";

        noReceipts.style.display = "flex";

        emptyMessage.textContent = hasServerRecords
            ? "Try changing your search or filter options."
            : "There are no payment receipts to show yet.";

    }

    else {

        receiptsGrid.style.display = "";

        noReceipts.style.display = "none";

    }


}



searchInput.addEventListener("input", filterReceipts);


courseFilter.addEventListener("change", filterReceipts);


monthFilter.addEventListener("change", filterReceipts);



/* =========================================================
   FRONTEND PLACEHOLDER ACTION
========================================================= */

function downloadReceipt(receiptNumber) {

    alert(
        "The PDF download for receipt \"" +
        receiptNumber +
        "\" will be connected to the backend later."
    );

}


</script>


</body>

</html>
