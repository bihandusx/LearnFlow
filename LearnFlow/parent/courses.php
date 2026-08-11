<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Course Participation | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Course Participation</h2>

                        <p>
                            Monitor your child's engagement across enrolled courses.
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
             COURSE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="courses-page-header">


                <div>

                    <h1>
                        Alex's Course Participation
                    </h1>

                    <p>
                        Track enrollment, progress and engagement in every course.
                    </p>

                </div>


                <button
                    type="button"
                    class="download-report-btn"
                    id="downloadReportButton"
                >

                    <i class="fa-solid fa-download"></i>

                    Download Report

                </button>


            </div>



            <!-- PARTICIPATION STATISTICS -->

            <div class="stats-grid">


                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            4
                        </span>

                        <span class="stat-label">
                            Enrolled Courses
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-calendar-check"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            94%
                        </span>

                        <span class="stat-label">
                            Average Attendance
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-file-circle-check"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            83%
                        </span>

                        <span class="stat-label">
                            Assignment Completion
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            79%
                        </span>

                        <span class="stat-label">
                            Average Progress
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 ENROLLED COURSES
            ================================================== -->

            <div class="course-grid">



                <!-- COURSE 1 -->

                <div class="course-card">


                    <div class="course-card-banner mathematics-banner">

                        <i class="fa-solid fa-calculator"></i>

                        <span>
                            PHYSICAL SCIENCE
                        </span>

                    </div>


                    <div class="course-card-body">


                        <div class="course-status">

                            <span class="course-active">
                                Active
                            </span>

                        </div>


                        <h3>
                            Combined Mathematics
                        </h3>


                        <div class="course-meta">

                            <span>

                                <i class="fa-solid fa-user-tie"></i>

                                Mr. Perera

                            </span>

                            <span>

                                <i class="fa-solid fa-layer-group"></i>

                                12 Modules

                            </span>

                        </div>


                        <div class="course-progress">


                            <div class="course-progress-label">

                                <span>
                                    Course Progress
                                </span>

                                <strong>
                                    80%
                                </strong>

                            </div>


                            <div class="course-progress-bar">

                                <div
                                    class="course-progress-fill"
                                    style="width: 80%;"
                                ></div>

                            </div>


                        </div>


                        <div class="participation-metrics">


                            <div class="participation-metric">

                                <strong>96%</strong>

                                <span>Attendance</span>

                            </div>


                            <div class="participation-metric">

                                <strong>5/6</strong>

                                <span>Assignments</span>

                            </div>


                            <div class="participation-metric">

                                <strong>12</strong>

                                <span>Forum Posts</span>

                            </div>


                        </div>


                        <a
                            href="progress.php"
                            class="view-details-btn"
                        >

                            View Details

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    </div>


                </div>



                <!-- COURSE 2 -->

                <div class="course-card">


                    <div class="course-card-banner chemistry-banner">

                        <i class="fa-solid fa-flask"></i>

                        <span>
                            PHYSICAL SCIENCE
                        </span>

                    </div>


                    <div class="course-card-body">


                        <div class="course-status">

                            <span class="course-active">
                                Active
                            </span>

                        </div>


                        <h3>
                            Chemistry
                        </h3>


                        <div class="course-meta">

                            <span>

                                <i class="fa-solid fa-user-tie"></i>

                                Dr. Fernando

                            </span>

                            <span>

                                <i class="fa-solid fa-layer-group"></i>

                                10 Modules

                            </span>

                        </div>


                        <div class="course-progress">


                            <div class="course-progress-label">

                                <span>
                                    Course Progress
                                </span>

                                <strong>
                                    65%
                                </strong>

                            </div>


                            <div class="course-progress-bar">

                                <div
                                    class="course-progress-fill"
                                    style="width: 65%;"
                                ></div>

                            </div>


                        </div>


                        <div class="participation-metrics">


                            <div class="participation-metric">

                                <strong>90%</strong>

                                <span>Attendance</span>

                            </div>


                            <div class="participation-metric">

                                <strong>4/6</strong>

                                <span>Assignments</span>

                            </div>


                            <div class="participation-metric">

                                <strong>8</strong>

                                <span>Forum Posts</span>

                            </div>


                        </div>


                        <a
                            href="progress.php"
                            class="view-details-btn"
                        >

                            View Details

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    </div>


                </div>



                <!-- COURSE 3 -->

                <div class="course-card">


                    <div class="course-card-banner physics-banner">

                        <i class="fa-solid fa-atom"></i>

                        <span>
                            PHYSICAL SCIENCE
                        </span>

                    </div>


                    <div class="course-card-body">


                        <div class="course-status">

                            <span class="course-active">
                                Active
                            </span>

                        </div>


                        <h3>
                            Physics
                        </h3>


                        <div class="course-meta">

                            <span>

                                <i class="fa-solid fa-user-tie"></i>

                                Mr. Silva

                            </span>

                            <span>

                                <i class="fa-solid fa-layer-group"></i>

                                14 Modules

                            </span>

                        </div>


                        <div class="course-progress">


                            <div class="course-progress-label">

                                <span>
                                    Course Progress
                                </span>

                                <strong>
                                    72%
                                </strong>

                            </div>


                            <div class="course-progress-bar">

                                <div
                                    class="course-progress-fill"
                                    style="width: 72%;"
                                ></div>

                            </div>


                        </div>


                        <div class="participation-metrics">


                            <div class="participation-metric">

                                <strong>92%</strong>

                                <span>Attendance</span>

                            </div>


                            <div class="participation-metric">

                                <strong>5/6</strong>

                                <span>Assignments</span>

                            </div>


                            <div class="participation-metric">

                                <strong>10</strong>

                                <span>Forum Posts</span>

                            </div>


                        </div>


                        <a
                            href="progress.php"
                            class="view-details-btn"
                        >

                            View Details

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    </div>


                </div>



                <!-- COURSE 4 -->

                <div class="course-card">


                    <div class="course-card-banner english-banner">

                        <i class="fa-solid fa-language"></i>

                        <span>
                            GENERAL
                        </span>

                    </div>


                    <div class="course-card-body">


                        <div class="course-status completed-status">

                            <span>
                                Completed
                            </span>

                        </div>


                        <h3>
                            General English
                        </h3>


                        <div class="course-meta">

                            <span>

                                <i class="fa-solid fa-user-tie"></i>

                                Ms. Perera

                            </span>

                            <span>

                                <i class="fa-solid fa-layer-group"></i>

                                8 Modules

                            </span>

                        </div>


                        <div class="course-progress">


                            <div class="course-progress-label">

                                <span>
                                    Course Progress
                                </span>

                                <strong>
                                    100%
                                </strong>

                            </div>


                            <div class="course-progress-bar">

                                <div
                                    class="course-progress-fill completed-fill"
                                    style="width: 100%;"
                                ></div>

                            </div>


                        </div>


                        <div class="participation-metrics">


                            <div class="participation-metric">

                                <strong>98%</strong>

                                <span>Attendance</span>

                            </div>


                            <div class="participation-metric">

                                <strong>6/6</strong>

                                <span>Assignments</span>

                            </div>


                            <div class="participation-metric">

                                <strong>15</strong>

                                <span>Forum Posts</span>

                            </div>


                        </div>


                        <a
                            href="progress.php"
                            class="view-details-btn"
                        >

                            View Details

                            <i class="fa-solid fa-arrow-right"></i>

                        </a>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Course Participation JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const downloadButton =
            document.getElementById("downloadReportButton");


        if (downloadButton) {


            downloadButton.addEventListener(
                "click",
                function () {


                    alert(
                        "The course participation report download will be connected to the backend later."
                    );


                }
            );


        }


    }
);


</script>


</body>

</html>
