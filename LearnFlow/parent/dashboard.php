<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Parent Dashboard | LEARNFLOW</title>


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
                class="nav-link active"
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


                <div style="display: flex; align-items: center; gap: 15px;">


                    <button
                        class="menu-toggle"
                        id="menuToggle"
                    >

                        <i class="fa-solid fa-bars"></i>

                    </button>


                    <div>

                        <h2>Parent Dashboard</h2>

                        <p>
                            Stay connected with your child's learning journey.
                        </p>

                    </div>


                </div>


            </div>



            <div class="user-area">


                <!-- Notification -->

                <button
                    class="notification-btn"
                    id="notificationButton"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-badge"></span>

                </button>


                <!-- User -->

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
             DASHBOARD CONTENT
        ================================================== -->

        <section class="dashboard-content">



            <!-- WELCOME BANNER -->

            <div class="welcome-banner">


                <div class="welcome-content">


                    <h1>
                        Welcome back, Nimal! 👋
                    </h1>


                    <p>
                        Here's how Alex is progressing in their studies.
                    </p>


                </div>


                <div class="welcome-icon">

                    <i class="fa-solid fa-people-roof"></i>

                </div>


            </div>



            <!-- =================================================
                 STATISTICS
            ================================================== -->

            <div class="stats-grid">


                <!-- Attendance -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-calendar-check"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            94%
                        </span>

                        <span class="stat-label">
                            Attendance Rate
                        </span>

                    </div>


                </div>



                <!-- Assignments -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-file-pen"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            3
                        </span>

                        <span class="stat-label">
                            Pending Assignments
                        </span>

                    </div>


                </div>



                <!-- Results -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-award"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            85%
                        </span>

                        <span class="stat-label">
                            Average Grade
                        </span>

                    </div>


                </div>



                <!-- Payments -->

                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-credit-card"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            LKR 5,000
                        </span>

                        <span class="stat-label">
                            Payment Due
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 DASHBOARD GRID
            ================================================== -->

            <div class="dashboard-grid">


                <!-- LEFT COLUMN -->

                <div>


                    <!-- COURSE PARTICIPATION -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Course Participation
                            </h3>


                            <a
                                href="courses.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>



                        <!-- Course 1 -->

                        <div class="course-item">


                            <div class="course-icon">

                                <i class="fa-solid fa-calculator"></i>

                            </div>


                            <div class="course-info">

                                <h4>
                                    Combined Mathematics
                                </h4>

                                <p>
                                    Advanced Level • Physical Science
                                </p>

                            </div>


                            <div class="progress-container">


                                <div class="progress-label">

                                    <span>
                                        Progress
                                    </span>

                                    <span>
                                        80%
                                    </span>

                                </div>


                                <div class="progress-bar">

                                    <div
                                        class="progress-fill"
                                        style="width: 80%;"
                                    ></div>

                                </div>


                            </div>


                        </div>



                        <!-- Course 2 -->

                        <div class="course-item">


                            <div class="course-icon">

                                <i class="fa-solid fa-flask"></i>

                            </div>


                            <div class="course-info">

                                <h4>
                                    Chemistry
                                </h4>

                                <p>
                                    Advanced Level • Physical Science
                                </p>

                            </div>


                            <div class="progress-container">


                                <div class="progress-label">

                                    <span>
                                        Progress
                                    </span>

                                    <span>
                                        65%
                                    </span>

                                </div>


                                <div class="progress-bar">

                                    <div
                                        class="progress-fill"
                                        style="width: 65%;"
                                    ></div>

                                </div>


                            </div>


                        </div>



                        <!-- Course 3 -->

                        <div class="course-item">


                            <div class="course-icon">

                                <i class="fa-solid fa-atom"></i>

                            </div>


                            <div class="course-info">

                                <h4>
                                    Physics
                                </h4>

                                <p>
                                    Advanced Level • Physical Science
                                </p>

                            </div>


                            <div class="progress-container">


                                <div class="progress-label">

                                    <span>
                                        Progress
                                    </span>

                                    <span>
                                        72%
                                    </span>

                                </div>


                                <div class="progress-bar">

                                    <div
                                        class="progress-fill"
                                        style="width: 72%;"
                                    ></div>

                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- UPCOMING ASSIGNMENTS & EXAMS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Upcoming Assignments & Exams
                            </h3>


                            <a
                                href="assignments.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>



                        <!-- Assignment 1 -->

                        <div class="assignment-item">


                            <div class="assignment-icon">

                                <i class="fa-solid fa-file-pen"></i>

                            </div>


                            <div class="assignment-info">

                                <h4>
                                    Calculus Assignment 02
                                </h4>

                                <p>
                                    Due: 15 August 2026
                                </p>

                            </div>


                            <span class="status-badge status-pending">

                                Pending

                            </span>


                        </div>



                        <!-- Assignment 2 -->

                        <div class="assignment-item">


                            <div class="assignment-icon">

                                <i class="fa-solid fa-clipboard-check"></i>

                            </div>


                            <div class="assignment-info">

                                <h4>
                                    Mid-Term Examination - Physics
                                </h4>

                                <p>
                                    Date: 20 August 2026
                                </p>

                            </div>


                            <span class="status-badge status-pending">

                                Upcoming

                            </span>


                        </div>



                    </div>


                </div>



                <!-- RIGHT COLUMN -->

                <div>


                    <!-- ANNOUNCEMENTS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Teacher Announcements
                            </h3>


                            <a
                                href="announcements.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>



                        <div class="announcement-item">


                            <h4>
                                New Physics Recording Available
                            </h4>


                            <p>
                                The latest lesson recording has been uploaded to the Physics course.
                            </p>


                            <div class="announcement-date">

                                31 July 2026

                            </div>


                        </div>



                        <div class="announcement-item">


                            <h4>
                                Mid-Term Examination Schedule
                            </h4>


                            <p>
                                The examination schedule has been published. Please check the timetable.
                            </p>


                            <div class="announcement-date">

                                29 July 2026

                            </div>


                        </div>


                    </div>



                    <!-- PAYMENT STATUS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Payment Status
                            </h3>


                            <a
                                href="payments.php"
                                class="view-all"
                            >

                                View All

                            </a>


                        </div>



                        <div class="assignment-item">


                            <div class="assignment-icon">

                                <i class="fa-solid fa-credit-card"></i>

                            </div>


                            <div class="assignment-info">

                                <h4>
                                    August Tuition Fee
                                </h4>

                                <p>
                                    Due: 05 August 2026
                                </p>

                            </div>


                            <span class="status-badge status-due">

                                Due

                            </span>


                        </div>



                        <div class="assignment-item">


                            <div class="assignment-icon">

                                <i class="fa-solid fa-receipt"></i>

                            </div>


                            <div class="assignment-info">

                                <h4>
                                    July Tuition Fee
                                </h4>

                                <p>
                                    Paid: 03 July 2026
                                </p>

                            </div>


                            <span class="status-badge status-paid">

                                Paid

                            </span>


                        </div>


                    </div>



                    <!-- QUICK ACTIONS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Quick Actions
                            </h3>


                        </div>



                        <div class="quick-actions">


                            <a
                                href="results.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-award"></i>

                                Exam Results

                            </a>


                            <a
                                href="attendance.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-calendar-check"></i>

                                Attendance

                            </a>


                            <a
                                href="contact.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-comments"></i>

                                Contact Teacher

                            </a>


                            <a
                                href="meetings.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-handshake"></i>

                                Request Meeting

                            </a>


                        </div>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


</body>

</html>
