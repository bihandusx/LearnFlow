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

                        NF

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            Nimal Fernando
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
                        A record of every completed payment made for Alex's tuition.
                    </p>

                </div>


                <div class="receipts-summary">


                    <div class="summary-item">

                        <strong>
                            6
                        </strong>

                        <span>
                            Total Receipts
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            LKR 27,000
                        </strong>

                        <span>
                            Total Paid
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            2026
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

                    <option value="mathematics">
                        Combined Mathematics
                    </option>

                    <option value="chemistry">
                        Chemistry
                    </option>

                    <option value="physics">
                        Physics
                    </option>

                </select>



                <select
                    id="monthFilter"
                    class="receipts-filter"
                >

                    <option value="all">
                        All Months
                    </option>

                    <option value="july">
                        July 2026
                    </option>

                    <option value="june">
                        June 2026
                    </option>

                    <option value="may">
                        May 2026
                    </option>

                    <option value="april">
                        April 2026
                    </option>

                </select>


            </div>



            <!-- RECEIPT CARDS -->

            <div
                class="receipts-grid"
                id="receiptsGrid"
            >


                <!-- RECEIPT 1 -->

                <div
                    class="receipt-card"
                    data-course="mathematics"
                    data-month="july"
                    data-number="lf-2026-0071"
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
                            July Tuition Fee
                        </h3>

                        <span class="receipt-number">
                            Receipt #LF-2026-0071
                        </span>

                        <p class="receipt-course">

                            <i class="fa-solid fa-calculator"></i>

                            Combined Mathematics

                        </p>

                        <div class="receipt-meta">

                            <div class="receipt-meta-row">
                                <span>Amount</span>
                                <span>LKR 5,000</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Paid On</span>
                                <span>03 Jul 2026</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Method</span>
                                <span>Bank Transfer</span>
                            </div>

                        </div>

                    </div>


                    <div class="receipt-card-footer">

                        <button
                            class="receipt-download-btn"
                            onclick="downloadReceipt('LF-2026-0071')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download PDF

                        </button>

                    </div>


                </div>



                <!-- RECEIPT 2 -->

                <div
                    class="receipt-card"
                    data-course="chemistry"
                    data-month="july"
                    data-number="lf-2026-0072"
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
                            July Tuition Fee
                        </h3>

                        <span class="receipt-number">
                            Receipt #LF-2026-0072
                        </span>

                        <p class="receipt-course">

                            <i class="fa-solid fa-flask"></i>

                            Chemistry

                        </p>

                        <div class="receipt-meta">

                            <div class="receipt-meta-row">
                                <span>Amount</span>
                                <span>LKR 4,500</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Paid On</span>
                                <span>03 Jul 2026</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Method</span>
                                <span>Card Payment</span>
                            </div>

                        </div>

                    </div>


                    <div class="receipt-card-footer">

                        <button
                            class="receipt-download-btn"
                            onclick="downloadReceipt('LF-2026-0072')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download PDF

                        </button>

                    </div>


                </div>



                <!-- RECEIPT 3 -->

                <div
                    class="receipt-card"
                    data-course="physics"
                    data-month="june"
                    data-number="lf-2026-0065"
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
                            June Tuition Fee
                        </h3>

                        <span class="receipt-number">
                            Receipt #LF-2026-0065
                        </span>

                        <p class="receipt-course">

                            <i class="fa-solid fa-atom"></i>

                            Physics

                        </p>

                        <div class="receipt-meta">

                            <div class="receipt-meta-row">
                                <span>Amount</span>
                                <span>LKR 4,000</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Paid On</span>
                                <span>02 Jun 2026</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Method</span>
                                <span>Cash</span>
                            </div>

                        </div>

                    </div>


                    <div class="receipt-card-footer">

                        <button
                            class="receipt-download-btn"
                            onclick="downloadReceipt('LF-2026-0065')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download PDF

                        </button>

                    </div>


                </div>



                <!-- RECEIPT 4 -->

                <div
                    class="receipt-card"
                    data-course="mathematics"
                    data-month="may"
                    data-number="lf-2026-0058"
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
                            May Tuition Fee
                        </h3>

                        <span class="receipt-number">
                            Receipt #LF-2026-0058
                        </span>

                        <p class="receipt-course">

                            <i class="fa-solid fa-calculator"></i>

                            Combined Mathematics

                        </p>

                        <div class="receipt-meta">

                            <div class="receipt-meta-row">
                                <span>Amount</span>
                                <span>LKR 5,000</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Paid On</span>
                                <span>04 May 2026</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Method</span>
                                <span>Bank Transfer</span>
                            </div>

                        </div>

                    </div>


                    <div class="receipt-card-footer">

                        <button
                            class="receipt-download-btn"
                            onclick="downloadReceipt('LF-2026-0058')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download PDF

                        </button>

                    </div>


                </div>



                <!-- RECEIPT 5 -->

                <div
                    class="receipt-card"
                    data-course="chemistry"
                    data-month="may"
                    data-number="lf-2026-0059"
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
                            May Tuition Fee
                        </h3>

                        <span class="receipt-number">
                            Receipt #LF-2026-0059
                        </span>

                        <p class="receipt-course">

                            <i class="fa-solid fa-flask"></i>

                            Chemistry

                        </p>

                        <div class="receipt-meta">

                            <div class="receipt-meta-row">
                                <span>Amount</span>
                                <span>LKR 4,500</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Paid On</span>
                                <span>04 May 2026</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Method</span>
                                <span>Bank Transfer</span>
                            </div>

                        </div>

                    </div>


                    <div class="receipt-card-footer">

                        <button
                            class="receipt-download-btn"
                            onclick="downloadReceipt('LF-2026-0059')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download PDF

                        </button>

                    </div>


                </div>



                <!-- RECEIPT 6 -->

                <div
                    class="receipt-card"
                    data-course="physics"
                    data-month="april"
                    data-number="lf-2026-0045"
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
                            April Tuition Fee
                        </h3>

                        <span class="receipt-number">
                            Receipt #LF-2026-0045
                        </span>

                        <p class="receipt-course">

                            <i class="fa-solid fa-atom"></i>

                            Physics

                        </p>

                        <div class="receipt-meta">

                            <div class="receipt-meta-row">
                                <span>Amount</span>
                                <span>LKR 4,000</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Paid On</span>
                                <span>05 Apr 2026</span>
                            </div>

                            <div class="receipt-meta-row">
                                <span>Method</span>
                                <span>Cash</span>
                            </div>

                        </div>

                    </div>


                    <div class="receipt-card-footer">

                        <button
                            class="receipt-download-btn"
                            onclick="downloadReceipt('LF-2026-0045')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download PDF

                        </button>

                    </div>


                </div>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noReceipts"
                class="no-results"
            >

                <i class="fa-solid fa-receipt"></i>

                <h3>
                    No Receipts Found
                </h3>

                <p>
                    Try changing your search or filter options.
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


const noReceipts =
    document.getElementById("noReceipts");



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
                card.getAttribute("data-number");


            const course =
                card.getAttribute("data-course");


            const month =
                card.getAttribute("data-month");


            const matchesSearch =
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



    if (visibleCount === 0) {

        noReceipts.style.display = "flex";

    }

    else {

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
