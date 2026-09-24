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
        <?php echo $projectName; ?> | Course Completion
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


        <!-- LOGO -->

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


        <!-- MENU -->

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


            <!-- ACTIVE PAGE -->

            <a href="course_progress.php"
               class="menu-link active">

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


            <a href="institute_performance.php" class="menu-link">
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
                        Academic Monitoring
                    </p>

                    <h1>
                        Course Completion
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
            <p class="card-label">Academic Monitoring</p>

            <h2>Course Completion</h2>

            <p>
                Monitor the overall completion progress of the
                LearnFlow course and its modules.
            </p>
        </div>

        <div class="page-intro-icon">
            <i class="fas fa-chart-line"></i>
        </div>

    </section>


    <!-- SUMMARY CARDS -->
    <section class="course-summary-grid">

        <article class="mini-stat-card">

            <div class="mini-stat-icon">
                <i class="fas fa-book-open"></i>
            </div>

            <div>
                <p>Total Courses</p>
                <h3>1</h3>
            </div>

        </article>


        <article class="mini-stat-card">

            <div class="mini-stat-icon">
                <i class="fas fa-layer-group"></i>
            </div>

            <div>
                <p>Total Modules</p>
                <h3>4</h3>
            </div>

        </article>


        <article class="mini-stat-card">

            <div class="mini-stat-icon">
                <i class="fas fa-circle-check"></i>
            </div>

            <div>
                <p>Completed Modules</p>
                <h3>2</h3>
            </div>

        </article>


        <article class="mini-stat-card">

            <div class="mini-stat-icon">
                <i class="fas fa-percent"></i>
            </div>

            <div>
                <p>Overall Completion</p>
                <h3>50%</h3>
            </div>

        </article>

    </section>


    <!-- COURSE OVERVIEW -->
    <section class="course-table-card">

        <div class="course-table-heading">

            <div>
                <h2>Course Progress Overview</h2>

                <p>
                    Track module completion within the course.
                </p>
            </div>

        </div>


        <div class="table-responsive">

            <table class="coordinator-table">

                <thead>

                    <tr>
                        <th>Module ID</th>
                        <th>Module Name</th>
                        <th>Progress</th>
                        <th>Status</th>
                    </tr>

                </thead>


                <tbody>

                    <tr>

                        <td>#M001</td>

                        <td>
                            <strong>Module 1</strong>
                        </td>

                        <td>
                            <strong>100%</strong>
                        </td>

                        <td>
                            <span class="course-status active-status">
                                Completed
                            </span>
                        </td>

                    </tr>


                    <tr>

                        <td>#M002</td>

                        <td>
                            <strong>Module 2</strong>
                        </td>

                        <td>
                            <strong>100%</strong>
                        </td>

                        <td>
                            <span class="course-status active-status">
                                Completed
                            </span>
                        </td>

                    </tr>


                    <tr>

                        <td>#M003</td>

                        <td>
                            <strong>Module 3</strong>
                        </td>

                        <td>
                            <strong>0%</strong>
                        </td>

                        <td>
                            <span class="course-status">
                                Not Started
                            </span>
                        </td>

                    </tr>


                    <tr>

                        <td>#M004</td>

                        <td>
                            <strong>Module 4</strong>
                        </td>

                        <td>
                            <strong>0%</strong>
                        </td>

                        <td>
                            <span class="course-status">
                                Not Started
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