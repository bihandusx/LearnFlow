<?php
$coordinatorName = 'Coordinator Name';
$projectName = 'LearnFlow';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $projectName; ?> | Institute Performance
    </title>

    <link rel="stylesheet" href="../css/teacher.css">
    <link rel="stylesheet" href="../css/coordinator.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>


<body>

<div class="dashboard-shell">


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">

        <div class="brand-panel">

            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>

            <div>
                <p class="brand-label">LearnFlow</p>

                <p class="brand-subtitle">
                    Academic Coordinator Portal
                </p>
            </div>

        </div>


        <nav class="sidebar-menu">


            <a href="dashboard.php" class="menu-link">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>


            <a href="profile.php" class="menu-link">
                <i class="fas fa-user"></i>
                Profile
            </a>


            <a href="courses.php" class="menu-link">
                <i class="fas fa-book-open"></i>
                Courses
            </a>


            <a href="academic_schedules.php" class="menu-link">
                <i class="fas fa-calendar-alt"></i>
                Academic Schedules
            </a>


            <a href="semester_plans.php" class="menu-link">
                <i class="fas fa-calendar-week"></i>
                Semester Plans
            </a>


            <a href="Review_assignments.php" class="menu-link">
                <i class="fas fa-chalkboard-teacher"></i>
                Review Assignments
            </a>


            <a href="exam_schedules.php" class="menu-link">
                <i class="fas fa-file-alt"></i>
                Examination Schedules
            </a>


            <a href="course_progress.php" class="menu-link">
                <i class="fas fa-chart-line"></i>
                Course Completion
            </a>


            <a href="teacher_performance.php" class="menu-link">
                <i class="fas fa-user-check"></i>
                Teacher Performance
            </a>


            <a href="student_engagement.php" class="menu-link">
                <i class="fas fa-users"></i>
                Student Engagement
            </a>


            <a href="attendance_statistics.php" class="menu-link">
                <i class="fas fa-clipboard-check"></i>
                Attendance Statistics
            </a>


            <a href="learning_resources.php" class="menu-link">
                <i class="fas fa-folder-open"></i>
                Learning Resources
            </a>


            <a href="recording_reviews.php" class="menu-link">
                <i class="fas fa-video"></i>
                Recording Reviews
            </a>


            <a href="content_standards.php" class="menu-link">
                <i class="fas fa-check-circle"></i>
                Content Standards
            </a>


            <a href="question_bank.php" class="menu-link">
                <i class="fas fa-question-circle"></i>
                Question Bank
            </a>


            <a href="academic_reports.php" class="menu-link">
                <i class="fas fa-chart-bar"></i>
                Academic Reports
            </a>


            <!-- ACTIVE PAGE -->

            <a href="institute_performance.php"
               class="menu-link active">

                <i class="fas fa-chart-pie"></i>
                Institute Performance

            </a>


            <a href="learning_outcomes.php" class="menu-link">
                <i class="fas fa-graduation-cap"></i>
                Learning Outcomes
            </a>


            <a href="announcements.php" class="menu-link">
                <i class="fas fa-bullhorn"></i>
                Announcements
            </a>


            <a href="../auth/logout.php"
               class="menu-link logout-link">

                <i class="fas fa-sign-out-alt"></i>
                Logout

            </a>


        </nav>

    </aside>



    <!-- ================= CONTENT ================= -->

    <div class="content-area">


        <!-- TOP BAR -->

        <header class="topbar">


            <div class="topbar-left">

                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>


                <div class="dashboard-title">

                    <p class="small-label">
                        Academic Reporting
                    </p>

                    <h1>
                        Institute Performance
                    </h1>

                </div>

            </div>



            <div class="topbar-right">


                <div class="project-pill">
                    LearnFlow
                </div>


                <button class="icon-btn">
                    <i class="fas fa-bell"></i>
                </button>


                <div class="profile-chip">

                    <div class="avatar-placeholder">
                        <i class="fas fa-user-circle"></i>
                    </div>

                    <div>

                        <span>Hello,</span>

                        <strong>
                            <?php echo $coordinatorName; ?>
                        </strong>

                    </div>

                </div>


            </div>


        </header>



        <!-- ================= MAIN ================= -->

        <main class="dashboard-main">


            <!-- PAGE INTRO -->

            <section class="page-intro-card">

                <div>

                    <p class="card-label">
                        Academic Reporting
                    </p>

                    <h2>
                        Institute Performance Overview
                    </h2>

                    <p>
                        Monitor key academic indicators to evaluate
                        the overall performance of learning activities
                        managed through LearnFlow.
                    </p>

                </div>


                <div class="page-intro-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>

            </section>



            <!-- SUMMARY CARDS -->

            <section class="course-summary-grid">


                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-user-graduate"></i>
                    </div>

                    <div>
                        <p>Total Students</p>
                        <h3>30</h3>
                    </div>

                </article>



                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>

                    <div>
                        <p>Course Completion</p>
                        <h3>50%</h3>
                    </div>

                </article>



                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>

                    <div>
                        <p>Average Attendance</p>
                        <h3>88%</h3>
                    </div>

                </article>



                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>

                    <div>
                        <p>Student Engagement</p>
                        <h3>83%</h3>
                    </div>

                </article>


            </section>



            <!-- PERFORMANCE TABLE -->

            <section class="course-table-card">


                <div class="course-table-heading">

                    <div>

                        <h2>
                            Academic Performance Indicators
                        </h2>

                        <p>
                            Overview of key indicators used to
                            monitor academic performance.
                        </p>

                    </div>

                </div>



                <div class="table-responsive">


                    <table class="coordinator-table">


                        <thead>

                            <tr>

                                <th>Performance Indicator</th>

                                <th>Current Value</th>

                                <th>Target</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>


                            <tr>

                                <td>
                                    <strong>
                                        Course Completion
                                    </strong>
                                </td>

                                <td>50%</td>

                                <td>100%</td>

                                <td>
                                    <span class="course-status">
                                        In Progress
                                    </span>
                                </td>

                            </tr>



                            <tr>

                                <td>
                                    <strong>
                                        Student Attendance
                                    </strong>
                                </td>

                                <td>88%</td>

                                <td>80%</td>

                                <td>
                                    <span class="course-status active-status">
                                        On Track
                                    </span>
                                </td>

                            </tr>



                            <tr>

                                <td>
                                    <strong>
                                        Student Engagement
                                    </strong>
                                </td>

                                <td>83%</td>

                                <td>75%</td>

                                <td>
                                    <span class="course-status active-status">
                                        On Track
                                    </span>
                                </td>

                            </tr>



                            <tr>

                                <td>
                                    <strong>
                                        Content Standards
                                    </strong>
                                </td>

                                <td>75%</td>

                                <td>100%</td>

                                <td>
                                    <span class="course-status">
                                        In Review
                                    </span>
                                </td>

                            </tr>


                        </tbody>


                    </table>


                </div>


            </section>


        </main>


    </div>


</div>


</body>

</html>