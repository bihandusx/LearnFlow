<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Announcements | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Announcements</h2>

                        <p>
                            Stay updated with the latest teacher announcements.
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

            <div class="announcements-page-header">


                <div>

                    <h1>
                        Teacher Announcements
                    </h1>

                    <p>
                        Updates and notices shared by Alex's teachers and the institute.
                    </p>

                </div>


                <div class="announcements-summary">


                    <div class="summary-item">

                        <strong>
                            7
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
                            Unread
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            2
                        </strong>

                        <span>
                            Urgent
                        </span>

                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="announcements-filter-card">


                <div class="announcements-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="announcementSearch"
                        placeholder="Search announcements..."
                    >


                </div>



                <select
                    id="courseFilter"
                    class="announcements-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <option value="general">
                        General
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
                    id="priorityFilter"
                    class="announcements-filter"
                >

                    <option value="all">
                        All Priorities
                    </option>

                    <option value="urgent">
                        Urgent
                    </option>

                    <option value="normal">
                        Normal
                    </option>

                </select>


            </div>



            <!-- ANNOUNCEMENTS LIST -->

            <div
                class="announcements-list"
                id="announcementsList"
            >


                <!-- ANNOUNCEMENT 1 -->

                <div
                    class="announcement-card unread"
                    data-course="general"
                    data-priority="urgent"
                    data-title="Mid-Term Examination Schedule"
                >


                    <div class="announcement-icon urgent-icon">

                        <i class="fa-solid fa-clipboard-check"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    Mid-Term Examination Schedule
                                </h3>

                                <span class="unread-dot"></span>

                            </div>

                            <span class="announcement-date">
                                29 Jul 2026
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid fa-bullhorn"></i>

                            General

                        </span>


                        <p class="announcement-text">

                            The mid-term examination schedule has been published for
                            all Advanced Level subjects. Please check the class
                            timetable for exact dates and times.

                        </p>


                        <span class="priority-badge priority-urgent">
                            Urgent
                        </span>


                    </div>


                </div>



                <!-- ANNOUNCEMENT 2 -->

                <div
                    class="announcement-card unread"
                    data-course="physics"
                    data-priority="normal"
                    data-title="New Physics Recording Available"
                >


                    <div class="announcement-icon">

                        <i class="fa-solid fa-video"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    New Physics Recording Available
                                </h3>

                                <span class="unread-dot"></span>

                            </div>

                            <span class="announcement-date">
                                31 Jul 2026
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid fa-atom"></i>

                            Physics

                        </span>


                        <p class="announcement-text">

                            The latest lesson recording covering mechanics has been
                            uploaded to the Physics course for students who missed
                            the live session.

                        </p>


                        <span class="priority-badge priority-normal">
                            Normal
                        </span>


                    </div>


                </div>



                <!-- ANNOUNCEMENT 3 -->

                <div
                    class="announcement-card"
                    data-course="general"
                    data-priority="urgent"
                    data-title="Tuition Fee Reminder"
                >


                    <div class="announcement-icon urgent-icon">

                        <i class="fa-solid fa-credit-card"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    Tuition Fee Reminder
                                </h3>

                            </div>

                            <span class="announcement-date">
                                25 Jul 2026
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid fa-bullhorn"></i>

                            General

                        </span>


                        <p class="announcement-text">

                            Kindly settle August tuition fees before the 5th of the
                            month to avoid late payment charges. Contact the office
                            for payment plan options.

                        </p>


                        <span class="priority-badge priority-urgent">
                            Urgent
                        </span>


                    </div>


                </div>



                <!-- ANNOUNCEMENT 4 -->

                <div
                    class="announcement-card"
                    data-course="chemistry"
                    data-priority="normal"
                    data-title="Chemistry Lab Session Rescheduled"
                >


                    <div class="announcement-icon">

                        <i class="fa-solid fa-flask"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    Chemistry Lab Session Rescheduled
                                </h3>

                            </div>

                            <span class="announcement-date">
                                27 Jul 2026
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid fa-flask"></i>

                            Chemistry

                        </span>


                        <p class="announcement-text">

                            This week's practical lab session has been moved from
                            Wednesday to Friday at the same time due to a venue
                            change.

                        </p>


                        <span class="priority-badge priority-normal">
                            Normal
                        </span>


                    </div>


                </div>



                <!-- ANNOUNCEMENT 5 -->

                <div
                    class="announcement-card"
                    data-course="mathematics"
                    data-priority="normal"
                    data-title="Mathematics Assignment Deadline Extended"
                >


                    <div class="announcement-icon">

                        <i class="fa-solid fa-file-pen"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    Mathematics Assignment Deadline Extended
                                </h3>

                            </div>

                            <span class="announcement-date">
                                22 Jul 2026
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid fa-calculator"></i>

                            Combined Mathematics

                        </span>


                        <p class="announcement-text">

                            The deadline for Calculus Assignment 02 has been extended
                            by three days to give students more time to practice
                            integration problems.

                        </p>


                        <span class="priority-badge priority-normal">
                            Normal
                        </span>


                    </div>


                </div>



                <!-- ANNOUNCEMENT 6 -->

                <div
                    class="announcement-card"
                    data-course="general"
                    data-priority="urgent"
                    data-title="Parent-Teacher Meeting Announcement"
                >


                    <div class="announcement-icon urgent-icon">

                        <i class="fa-solid fa-handshake"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    Parent-Teacher Meeting Announcement
                                </h3>

                            </div>

                            <span class="announcement-date">
                                18 Jul 2026
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid fa-bullhorn"></i>

                            General

                        </span>


                        <p class="announcement-text">

                            A parent-teacher meeting has been scheduled for next
                            month to discuss student progress ahead of the final
                            examinations. Details will follow shortly.

                        </p>


                        <span class="priority-badge priority-urgent">
                            Urgent
                        </span>


                    </div>


                </div>



                <!-- ANNOUNCEMENT 7 -->

                <div
                    class="announcement-card"
                    data-course="english"
                    data-priority="normal"
                    data-title="English Essay Competition"
                >


                    <div class="announcement-icon">

                        <i class="fa-solid fa-language"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    English Essay Competition
                                </h3>

                            </div>

                            <span class="announcement-date">
                                15 Jul 2026
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid fa-language"></i>

                            General English

                        </span>


                        <p class="announcement-text">

                            Students are invited to take part in the inter-class
                            essay writing competition. Entries can be submitted to
                            the English department by the end of the month.

                        </p>


                        <span class="priority-badge priority-normal">
                            Normal
                        </span>


                    </div>


                </div>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noAnnouncements"
                class="no-announcements"
            >

                <i class="fa-solid fa-bullhorn"></i>

                <h3>
                    No Announcements Found
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


<!-- Announcements JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById("announcementSearch");


        const courseFilter =
            document.getElementById("courseFilter");


        const priorityFilter =
            document.getElementById("priorityFilter");


        const announcementCards =
            document.querySelectorAll(".announcement-card");


        const noAnnouncements =
            document.getElementById("noAnnouncements");



        function filterAnnouncements() {


            const searchValue =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const selectedCourse =
                courseFilter.value;


            const selectedPriority =
                priorityFilter.value;


            let visibleCount = 0;



            announcementCards.forEach(
                function (card) {


                    const title =
                        card
                            .getAttribute("data-title")
                            .toLowerCase();


                    const course =
                        card.getAttribute("data-course");


                    const priority =
                        card.getAttribute("data-priority");


                    const matchesSearch =
                        title.includes(searchValue);


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesPriority =
                        selectedPriority === "all" ||
                        priority === selectedPriority;



                    if (
                        matchesSearch &&
                        matchesCourse &&
                        matchesPriority
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

                noAnnouncements.style.display = "flex";

            }

            else {

                noAnnouncements.style.display = "none";

            }


        }



        searchInput.addEventListener("input", filterAnnouncements);


        courseFilter.addEventListener("change", filterAnnouncements);


        priorityFilter.addEventListener("change", filterAnnouncements);



        /* -------------------------
           MARK AS READ ON CLICK
        ------------------------- */

        announcementCards.forEach(
            function (card) {


                card.addEventListener(
                    "click",
                    function () {

                        card.classList.remove("unread");

                    }
                );


            }
        );


    }
);


</script>


</body>

</html>
