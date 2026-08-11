<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Student Attendance | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Attendance</h2>

                        <p>
                            Track your child's attendance across all enrolled courses.
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

            <div class="attendance-page-header">


                <div>

                    <h1>
                        Alex's Attendance
                    </h1>

                    <p>
                        Overview of class attendance for all enrolled courses this term.
                    </p>

                </div>


                <div class="attendance-summary">


                    <div class="summary-item">

                        <strong>
                            94%
                        </strong>

                        <span>
                            Attendance Rate
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            47
                        </strong>

                        <span>
                            Present
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            2
                        </strong>

                        <span>
                            Absent
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            1
                        </strong>

                        <span>
                            Late
                        </span>

                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="attendance-filter-card">


                <div class="attendance-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="attendanceSearch"
                        placeholder="Search by course..."
                    >


                </div>



                <select
                    id="courseFilter"
                    class="attendance-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <option value="mathematics">
                        Combined Mathematics
                    </option>

                    <option value="physics">
                        Physics
                    </option>

                    <option value="chemistry">
                        Chemistry
                    </option>

                    <option value="english">
                        General English
                    </option>

                </select>



                <select
                    id="statusFilter"
                    class="attendance-filter"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="present">
                        Present
                    </option>

                    <option value="absent">
                        Absent
                    </option>

                    <option value="late">
                        Late
                    </option>

                </select>


            </div>



            <!-- ATTENDANCE TABLE -->

            <div class="attendance-table-card">


                <table
                    class="attendance-table"
                    id="attendanceTable"
                >


                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>Course</th>

                            <th>Time</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr
                            class="attendance-row"
                            data-course="mathematics"
                            data-status="present"
                        >

                            <td>01 Aug 2026</td>

                            <td>

                                <span class="attendance-course">

                                    <i class="fa-solid fa-calculator"></i>

                                    Combined Mathematics

                                </span>

                            </td>

                            <td>3:00 PM - 5:00 PM</td>

                            <td>

                                <span class="status-badge status-present">
                                    Present
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="attendance-row"
                            data-course="physics"
                            data-status="present"
                        >

                            <td>31 Jul 2026</td>

                            <td>

                                <span class="attendance-course">

                                    <i class="fa-solid fa-atom"></i>

                                    Physics

                                </span>

                            </td>

                            <td>1:00 PM - 3:00 PM</td>

                            <td>

                                <span class="status-badge status-present">
                                    Present
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="attendance-row"
                            data-course="chemistry"
                            data-status="late"
                        >

                            <td>30 Jul 2026</td>

                            <td>

                                <span class="attendance-course">

                                    <i class="fa-solid fa-flask"></i>

                                    Chemistry

                                </span>

                            </td>

                            <td>9:00 AM - 11:00 AM</td>

                            <td>

                                <span class="status-badge status-late">
                                    Late
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="attendance-row"
                            data-course="mathematics"
                            data-status="absent"
                        >

                            <td>29 Jul 2026</td>

                            <td>

                                <span class="attendance-course">

                                    <i class="fa-solid fa-calculator"></i>

                                    Combined Mathematics

                                </span>

                            </td>

                            <td>3:00 PM - 5:00 PM</td>

                            <td>

                                <span class="status-badge status-absent">
                                    Absent
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="attendance-row"
                            data-course="english"
                            data-status="present"
                        >

                            <td>28 Jul 2026</td>

                            <td>

                                <span class="attendance-course">

                                    <i class="fa-solid fa-language"></i>

                                    General English

                                </span>

                            </td>

                            <td>10:00 AM - 11:30 AM</td>

                            <td>

                                <span class="status-badge status-present">
                                    Present
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="attendance-row"
                            data-course="physics"
                            data-status="absent"
                        >

                            <td>24 Jul 2026</td>

                            <td>

                                <span class="attendance-course">

                                    <i class="fa-solid fa-atom"></i>

                                    Physics

                                </span>

                            </td>

                            <td>1:00 PM - 3:00 PM</td>

                            <td>

                                <span class="status-badge status-absent">
                                    Absent
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="attendance-row"
                            data-course="chemistry"
                            data-status="present"
                        >

                            <td>23 Jul 2026</td>

                            <td>

                                <span class="attendance-course">

                                    <i class="fa-solid fa-flask"></i>

                                    Chemistry

                                </span>

                            </td>

                            <td>9:00 AM - 11:00 AM</td>

                            <td>

                                <span class="status-badge status-present">
                                    Present
                                </span>

                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noAttendance"
                class="no-attendance"
            >

                <i class="fa-solid fa-calendar-xmark"></i>

                <h3>
                    No Attendance Records Found
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


<!-- Attendance JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById("attendanceSearch");


        const courseFilter =
            document.getElementById("courseFilter");


        const statusFilter =
            document.getElementById("statusFilter");


        const attendanceRows =
            document.querySelectorAll(".attendance-row");


        const attendanceTableCard =
            document.querySelector(".attendance-table-card");


        const noAttendance =
            document.getElementById("noAttendance");



        function filterAttendance() {


            const searchValue =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const selectedCourse =
                courseFilter.value;


            const selectedStatus =
                statusFilter.value;


            let visibleCount = 0;



            attendanceRows.forEach(
                function (row) {


                    const course =
                        row.getAttribute("data-course");


                    const status =
                        row.getAttribute("data-status");


                    const courseName =
                        row
                            .querySelector(".attendance-course")
                            .textContent
                            .toLowerCase()
                            .trim();


                    const matchesSearch =
                        courseName.includes(searchValue);


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesStatus =
                        selectedStatus === "all" ||
                        status === selectedStatus;



                    if (
                        matchesSearch &&
                        matchesCourse &&
                        matchesStatus
                    ) {

                        row.style.display = "";

                        visibleCount++;

                    }

                    else {

                        row.style.display = "none";

                    }


                }
            );



            if (visibleCount === 0) {

                attendanceTableCard.style.display = "none";

                noAttendance.style.display = "flex";

            }

            else {

                attendanceTableCard.style.display = "block";

                noAttendance.style.display = "none";

            }


        }



        searchInput.addEventListener("input", filterAttendance);


        courseFilter.addEventListener("change", filterAttendance);


        statusFilter.addEventListener("change", filterAttendance);


    }
);


</script>


</body>

</html>
