<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Assignments | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Assignments</h2>

                        <p>
                            Track your child's assignments, submissions and grades.
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

            <div class="assignments-page-header">


                <div>

                    <h1>
                        Alex's Assignments
                    </h1>

                    <p>
                        Overview of assignments across all enrolled courses.
                    </p>

                </div>


                <div class="assignments-summary">


                    <div class="summary-item">

                        <strong>
                            6
                        </strong>

                        <span>
                            Total
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            2
                        </strong>

                        <span>
                            Pending
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            1
                        </strong>

                        <span>
                            Overdue
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            3
                        </strong>

                        <span>
                            Graded
                        </span>

                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="assignments-filter-card">


                <div class="assignments-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="assignmentSearch"
                        placeholder="Search assignments..."
                    >


                </div>



                <select
                    id="courseFilter"
                    class="assignments-filter"
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
                    class="assignments-filter"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="submitted">
                        Submitted
                    </option>

                    <option value="graded">
                        Graded
                    </option>

                    <option value="overdue">
                        Overdue
                    </option>

                </select>


            </div>



            <!-- ASSIGNMENT CARDS -->

            <div
                class="assignments-grid"
                id="assignmentsGrid"
            >


                <!-- ASSIGNMENT 1 -->

                <div
                    class="assignment-card"
                    data-course="mathematics"
                    data-status="pending"
                    data-title="Calculus Assignment 02"
                >


                    <div class="assignment-card-top">

                        <div class="assignment-type-icon">

                            <i class="fa-solid fa-file-pen"></i>

                        </div>

                        <span class="status-badge status-pending">
                            Pending
                        </span>

                    </div>


                    <div class="assignment-card-body">

                        <h3>
                            Calculus Assignment 02
                        </h3>

                        <p class="assignment-course">

                            <i class="fa-solid fa-calculator"></i>

                            Combined Mathematics

                        </p>

                        <p class="assignment-due">

                            <i class="fa-regular fa-calendar"></i>

                            Due: 15 Aug 2026

                        </p>

                    </div>


                    <div class="assignment-card-footer">

                        <span class="assignment-score">
                            <span>Awaiting Submission</span>
                        </span>

                    </div>


                </div>



                <!-- ASSIGNMENT 2 -->

                <div
                    class="assignment-card"
                    data-course="chemistry"
                    data-status="pending"
                    data-title="Organic Chemistry Worksheet"
                >


                    <div class="assignment-card-top">

                        <div class="assignment-type-icon">

                            <i class="fa-solid fa-file-pen"></i>

                        </div>

                        <span class="status-badge status-pending">
                            Pending
                        </span>

                    </div>


                    <div class="assignment-card-body">

                        <h3>
                            Organic Chemistry Worksheet
                        </h3>

                        <p class="assignment-course">

                            <i class="fa-solid fa-flask"></i>

                            Chemistry

                        </p>

                        <p class="assignment-due">

                            <i class="fa-regular fa-calendar"></i>

                            Due: 18 Aug 2026

                        </p>

                    </div>


                    <div class="assignment-card-footer">

                        <span class="assignment-score">
                            <span>Awaiting Submission</span>
                        </span>

                    </div>


                </div>



                <!-- ASSIGNMENT 3 -->

                <div
                    class="assignment-card"
                    data-course="physics"
                    data-status="overdue"
                    data-title="Mechanics Problem Set"
                >


                    <div class="assignment-card-top">

                        <div class="assignment-type-icon">

                            <i class="fa-solid fa-file-pen"></i>

                        </div>

                        <span class="status-badge status-overdue">
                            Overdue
                        </span>

                    </div>


                    <div class="assignment-card-body">

                        <h3>
                            Mechanics Problem Set
                        </h3>

                        <p class="assignment-course">

                            <i class="fa-solid fa-atom"></i>

                            Physics

                        </p>

                        <p class="assignment-due">

                            <i class="fa-regular fa-calendar"></i>

                            Due: 05 Aug 2026

                        </p>

                    </div>


                    <div class="assignment-card-footer">

                        <span class="assignment-score">
                            <span>Not Submitted</span>
                        </span>

                    </div>


                </div>



                <!-- ASSIGNMENT 4 -->

                <div
                    class="assignment-card"
                    data-course="english"
                    data-status="graded"
                    data-title="Essay - My Favourite Book"
                >


                    <div class="assignment-card-top">

                        <div class="assignment-type-icon">

                            <i class="fa-solid fa-file-pen"></i>

                        </div>

                        <span class="status-badge status-graded">
                            Graded
                        </span>

                    </div>


                    <div class="assignment-card-body">

                        <h3>
                            Essay - My Favourite Book
                        </h3>

                        <p class="assignment-course">

                            <i class="fa-solid fa-language"></i>

                            General English

                        </p>

                        <p class="assignment-due">

                            <i class="fa-regular fa-calendar"></i>

                            Submitted: 28 Jul 2026

                        </p>

                    </div>


                    <div class="assignment-card-footer">

                        <span class="assignment-score">
                            85%
                            <span>Score</span>
                        </span>

                    </div>


                </div>



                <!-- ASSIGNMENT 5 -->

                <div
                    class="assignment-card"
                    data-course="mathematics"
                    data-status="graded"
                    data-title="Integration Practice Sheet"
                >


                    <div class="assignment-card-top">

                        <div class="assignment-type-icon">

                            <i class="fa-solid fa-file-pen"></i>

                        </div>

                        <span class="status-badge status-graded">
                            Graded
                        </span>

                    </div>


                    <div class="assignment-card-body">

                        <h3>
                            Integration Practice Sheet
                        </h3>

                        <p class="assignment-course">

                            <i class="fa-solid fa-calculator"></i>

                            Combined Mathematics

                        </p>

                        <p class="assignment-due">

                            <i class="fa-regular fa-calendar"></i>

                            Submitted: 20 Jul 2026

                        </p>

                    </div>


                    <div class="assignment-card-footer">

                        <span class="assignment-score">
                            92%
                            <span>Score</span>
                        </span>

                    </div>


                </div>



                <!-- ASSIGNMENT 6 -->

                <div
                    class="assignment-card"
                    data-course="chemistry"
                    data-status="graded"
                    data-title="Titration Lab Report"
                >


                    <div class="assignment-card-top">

                        <div class="assignment-type-icon">

                            <i class="fa-solid fa-file-pen"></i>

                        </div>

                        <span class="status-badge status-graded">
                            Graded
                        </span>

                    </div>


                    <div class="assignment-card-body">

                        <h3>
                            Titration Lab Report
                        </h3>

                        <p class="assignment-course">

                            <i class="fa-solid fa-flask"></i>

                            Chemistry

                        </p>

                        <p class="assignment-due">

                            <i class="fa-regular fa-calendar"></i>

                            Submitted: 15 Jul 2026

                        </p>

                    </div>


                    <div class="assignment-card-footer">

                        <span class="assignment-score">
                            78%
                            <span>Score</span>
                        </span>

                    </div>


                </div>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noAssignments"
                class="no-assignments"
            >

                <i class="fa-solid fa-folder-open"></i>

                <h3>
                    No Assignments Found
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


<!-- Assignments JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById("assignmentSearch");


        const courseFilter =
            document.getElementById("courseFilter");


        const statusFilter =
            document.getElementById("statusFilter");


        const assignmentCards =
            document.querySelectorAll(".assignment-card");


        const noAssignments =
            document.getElementById("noAssignments");



        function filterAssignments() {


            const searchValue =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const selectedCourse =
                courseFilter.value;


            const selectedStatus =
                statusFilter.value;


            let visibleCount = 0;



            assignmentCards.forEach(
                function (card) {


                    const title =
                        card
                            .getAttribute("data-title")
                            .toLowerCase();


                    const course =
                        card.getAttribute("data-course");


                    const status =
                        card.getAttribute("data-status");


                    const matchesSearch =
                        title.includes(searchValue);


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

                        card.style.display = "flex";

                        visibleCount++;

                    }

                    else {

                        card.style.display = "none";

                    }


                }
            );



            if (visibleCount === 0) {

                noAssignments.style.display = "flex";

            }

            else {

                noAssignments.style.display = "none";

            }


        }



        searchInput.addEventListener("input", filterAssignments);


        courseFilter.addEventListener("change", filterAssignments);


        statusFilter.addEventListener("change", filterAssignments);


    }
);


</script>


</body>

</html>
