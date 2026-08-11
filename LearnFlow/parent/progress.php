<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Academic Progress | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Academic Progress</h2>

                        <p>
                            Track your child's academic performance across all subjects.
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

            <div class="progress-page-header">


                <div>

                    <h1>
                        Alex's Academic Progress
                    </h1>

                    <p>
                        A summary of course completion and academic performance this term.
                    </p>

                </div>


                <div class="progress-summary">


                    <div class="summary-item">

                        <strong>
                            79%
                        </strong>

                        <span>
                            Overall Progress
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            B+
                        </strong>

                        <span>
                            Average Grade
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            3
                        </strong>

                        <span>
                            Improving
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            1
                        </strong>

                        <span>
                            Needs Attention
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 COURSE PROGRESS OVERVIEW
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Course Progress Overview
                        </h3>


                        <a
                            href="courses.php"
                            class="view-all"
                        >

                            View Courses

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
                                Mr. Perera • Advanced Level
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
                                Dr. Fernando • Advanced Level
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
                                Mr. Silva • Advanced Level
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



                    <!-- Course 4 -->

                    <div class="course-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-language"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                General English
                            </h4>

                            <p>
                                Ms. Perera • General
                            </p>

                        </div>


                        <div class="progress-container">


                            <div class="progress-label">

                                <span>
                                    Progress
                                </span>

                                <span>
                                    100%
                                </span>

                            </div>


                            <div class="progress-bar">

                                <div
                                    class="progress-fill"
                                    style="width: 100%;"
                                ></div>

                            </div>


                        </div>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 PERFORMANCE TREND
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Test & Exam Performance Trend
                        </h3>


                        <a
                            href="results.php"
                            class="view-all"
                        >

                            View All Results

                        </a>


                    </div>



                    <div class="performance-table-card">


                        <table class="performance-table">


                            <thead>

                                <tr>

                                    <th>Course</th>

                                    <th>Test 1</th>

                                    <th>Test 2</th>

                                    <th>Mid-Term</th>

                                    <th>Trend</th>

                                </tr>

                            </thead>


                            <tbody>


                                <tr>

                                    <td>

                                        <span class="performance-course">

                                            <i class="fa-solid fa-calculator"></i>

                                            Combined Mathematics

                                        </span>

                                    </td>

                                    <td>72%</td>

                                    <td>78%</td>

                                    <td>80%</td>

                                    <td>

                                        <span class="trend-indicator trend-up">

                                            <i class="fa-solid fa-arrow-trend-up"></i>

                                            Improving

                                        </span>

                                    </td>

                                </tr>


                                <tr>

                                    <td>

                                        <span class="performance-course">

                                            <i class="fa-solid fa-flask"></i>

                                            Chemistry

                                        </span>

                                    </td>

                                    <td>70%</td>

                                    <td>66%</td>

                                    <td>65%</td>

                                    <td>

                                        <span class="trend-indicator trend-down">

                                            <i class="fa-solid fa-arrow-trend-down"></i>

                                            Declining

                                        </span>

                                    </td>

                                </tr>


                                <tr>

                                    <td>

                                        <span class="performance-course">

                                            <i class="fa-solid fa-atom"></i>

                                            Physics

                                        </span>

                                    </td>

                                    <td>68%</td>

                                    <td>70%</td>

                                    <td>72%</td>

                                    <td>

                                        <span class="trend-indicator trend-up">

                                            <i class="fa-solid fa-arrow-trend-up"></i>

                                            Improving

                                        </span>

                                    </td>

                                </tr>


                                <tr>

                                    <td>

                                        <span class="performance-course">

                                            <i class="fa-solid fa-language"></i>

                                            General English

                                        </span>

                                    </td>

                                    <td>88%</td>

                                    <td>90%</td>

                                    <td>91%</td>

                                    <td>

                                        <span class="trend-indicator trend-stable">

                                            <i class="fa-solid fa-minus"></i>

                                            Stable

                                        </span>

                                    </td>

                                </tr>


                            </tbody>


                        </table>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 TEACHER REMARKS
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Teacher Remarks
                        </h3>


                    </div>



                    <div class="remark-item">


                        <div class="remark-header">

                            <span class="remark-course">

                                <i class="fa-solid fa-flask"></i>

                                Chemistry

                            </span>

                            <span class="remark-date">
                                30 Jul 2026
                            </span>

                        </div>


                        <p class="remark-text">

                            Alex's performance has declined slightly over the last two
                            tests. Additional practice on organic chemistry topics is
                            recommended before the mid-term examination.

                        </p>


                    </div>



                    <div class="remark-item">


                        <div class="remark-header">

                            <span class="remark-course">

                                <i class="fa-solid fa-calculator"></i>

                                Combined Mathematics

                            </span>

                            <span class="remark-date">
                                26 Jul 2026
                            </span>

                        </div>


                        <p class="remark-text">

                            Great improvement in calculus problem-solving. Alex is
                            actively participating in class discussions and completing
                            all assignments on time.

                        </p>


                    </div>



                    <div class="remark-item">


                        <div class="remark-header">

                            <span class="remark-course">

                                <i class="fa-solid fa-atom"></i>

                                Physics

                            </span>

                            <span class="remark-date">
                                20 Jul 2026
                            </span>

                        </div>


                        <p class="remark-text">

                            Steady progress in mechanics and wave theory. Encourage
                            Alex to revise past paper questions ahead of the next
                            assessment.

                        </p>


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
