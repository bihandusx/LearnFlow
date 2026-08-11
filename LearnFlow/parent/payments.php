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

            <div class="payments-page-header">


                <div>

                    <h1>
                        Alex's Payment Status
                    </h1>

                    <p>
                        Overview of tuition fees, dues and payment history.
                    </p>

                </div>


                <div class="payments-summary">


                    <div class="summary-item">

                        <strong>
                            LKR 9,500
                        </strong>

                        <span>
                            Outstanding
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            LKR 13,500
                        </strong>

                        <span>
                            Total Paid
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            05 Aug
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

            <div class="payments-table-card">


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


                        <tr
                            class="payments-row"
                            data-course="mathematics"
                            data-status="due"
                        >

                            <td>

                                <span class="payments-item">

                                    <i class="fa-solid fa-file-invoice-dollar"></i>

                                    August Tuition Fee

                                </span>

                            </td>

                            <td>Combined Mathematics</td>

                            <td class="payments-amount">LKR 5,000</td>

                            <td>Due: 05 Aug 2026</td>

                            <td>

                                <span class="status-badge status-due">
                                    Due
                                </span>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="pay-now-btn"
                                >

                                    Pay Now

                                </button>

                            </td>

                        </tr>


                        <tr
                            class="payments-row"
                            data-course="chemistry"
                            data-status="due"
                        >

                            <td>

                                <span class="payments-item">

                                    <i class="fa-solid fa-file-invoice-dollar"></i>

                                    August Tuition Fee

                                </span>

                            </td>

                            <td>Chemistry</td>

                            <td class="payments-amount">LKR 4,500</td>

                            <td>Due: 05 Aug 2026</td>

                            <td>

                                <span class="status-badge status-due">
                                    Due
                                </span>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="pay-now-btn"
                                >

                                    Pay Now

                                </button>

                            </td>

                        </tr>


                        <tr
                            class="payments-row"
                            data-course="physics"
                            data-status="overdue"
                        >

                            <td>

                                <span class="payments-item">

                                    <i class="fa-solid fa-book"></i>

                                    Physics Tute Book

                                </span>

                            </td>

                            <td>Physics</td>

                            <td class="payments-amount">LKR 1,200</td>

                            <td>Due: 20 Jul 2026</td>

                            <td>

                                <span class="status-badge status-overdue">
                                    Overdue
                                </span>

                            </td>

                            <td>

                                <button
                                    type="button"
                                    class="pay-now-btn"
                                >

                                    Pay Now

                                </button>

                            </td>

                        </tr>


                        <tr
                            class="payments-row"
                            data-course="mathematics"
                            data-status="paid"
                        >

                            <td>

                                <span class="payments-item">

                                    <i class="fa-solid fa-file-invoice-dollar"></i>

                                    July Tuition Fee

                                </span>

                            </td>

                            <td>Combined Mathematics</td>

                            <td class="payments-amount">LKR 5,000</td>

                            <td>Paid: 03 Jul 2026</td>

                            <td>

                                <span class="status-badge status-paid">
                                    Paid
                                </span>

                            </td>

                            <td>

                                <a
                                    href="receipts.php"
                                    class="view-receipt-link"
                                >

                                    View Receipt

                                </a>

                            </td>

                        </tr>


                        <tr
                            class="payments-row"
                            data-course="chemistry"
                            data-status="paid"
                        >

                            <td>

                                <span class="payments-item">

                                    <i class="fa-solid fa-file-invoice-dollar"></i>

                                    July Tuition Fee

                                </span>

                            </td>

                            <td>Chemistry</td>

                            <td class="payments-amount">LKR 4,500</td>

                            <td>Paid: 03 Jul 2026</td>

                            <td>

                                <span class="status-badge status-paid">
                                    Paid
                                </span>

                            </td>

                            <td>

                                <a
                                    href="receipts.php"
                                    class="view-receipt-link"
                                >

                                    View Receipt

                                </a>

                            </td>

                        </tr>


                        <tr
                            class="payments-row"
                            data-course="physics"
                            data-status="paid"
                        >

                            <td>

                                <span class="payments-item">

                                    <i class="fa-solid fa-file-invoice-dollar"></i>

                                    June Tuition Fee

                                </span>

                            </td>

                            <td>Physics</td>

                            <td class="payments-amount">LKR 4,000</td>

                            <td>Paid: 02 Jun 2026</td>

                            <td>

                                <span class="status-badge status-paid">
                                    Paid
                                </span>

                            </td>

                            <td>

                                <a
                                    href="receipts.php"
                                    class="view-receipt-link"
                                >

                                    View Receipt

                                </a>

                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noPayments"
                class="no-results"
            >

                <i class="fa-solid fa-credit-card"></i>

                <h3>
                    No Payment Records Found
                </h3>

                <p>
                    Try changing your filter options.
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


        const payNowButtons =
            document.querySelectorAll(".pay-now-btn");



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



            if (visibleCount === 0) {

                paymentsTableCard.style.display = "none";

                noPayments.style.display = "flex";

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
