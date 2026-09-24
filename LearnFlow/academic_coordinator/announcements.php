<?php
$coordinatorName = 'Coordinator Name';
$projectName = 'LearnFlow';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $projectName; ?> | Announcements</title>

    <link rel="stylesheet" href="../css/teacher.css">
    <link rel="stylesheet" href="../css/coordinator.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>


<body>

<div class="dashboard-shell">


    <!-- ================= SIDEBAR ================= -->

    <aside class="sidebar">


        <!-- BRAND -->

        <div class="brand-panel">

            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>

            <div>

                <p class="brand-label">
                    LearnFlow
                </p>

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


            <a href="institute_performance.php" class="menu-link">
                <i class="fas fa-chart-pie"></i>
                Institute Performance
            </a>


            <a href="learning_outcomes.php" class="menu-link">
                <i class="fas fa-graduation-cap"></i>
                Learning Outcomes
            </a>


            <!-- ACTIVE PAGE -->

            <a href="announcements.php"
               class="menu-link active">

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
                        Communication
                    </p>

                    <h1>
                        Announcements
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
                        Communication Management
                    </p>

                    <h2>
                        Announcements
                    </h2>

                    <p>
                        Publish and manage academic announcements
                        related to the course.
                    </p>

                </div>


                <div class="page-intro-icon">

                    <i class="fas fa-bullhorn"></i>

                </div>


            </section>



            <!-- SUMMARY CARDS -->

            <section class="course-summary-grid">


                <article class="mini-stat-card">


                    <div class="mini-stat-icon">

                        <i class="fas fa-bullhorn"></i>

                    </div>


                    <div>

                        <p>Total Announcements</p>

                        <h3>2</h3>

                    </div>


                </article>



                <article class="mini-stat-card">


                    <div class="mini-stat-icon">

                        <i class="fas fa-circle-check"></i>

                    </div>


                    <div>

                        <p>Published</p>

                        <h3>2</h3>

                    </div>


                </article>


            </section>



            <!-- ANNOUNCEMENT TABLE -->

            <section class="course-table-card">


                <div class="course-table-heading">


                    <div>

                        <h2>
                            Announcement Overview
                        </h2>

                        <p>
                            View academic announcements published
                            for students and teachers.
                        </p>

                    </div>
<br>

                    <button type="button"
                            class="btn-primary">

                        <i class="fas fa-plus"></i>
                        Add Announcement

                    </button>


                </div>



                <div class="table-responsive">


                    <table class="coordinator-table">


                        <thead>

                            <tr>

                                <th>Announcement ID</th>

                                <th>Title</th>

                                <th>Audience</th>

                                <th>Published Date</th>

                                <th>Status</th>

                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>


                            <!-- SAMPLE ANNOUNCEMENT 1 -->

                            <tr>

                                <td>#A001</td>

                                <td>

                                    <strong>
                                        Examination Schedule Released
                                    </strong>

                                </td>

                                <td>
                                    Students
                                </td>

                                <td>
                                    2026-09-20
                                </td>

                                <td>

                                    <span class="course-status active-status">
                                        Published
                                    </span>

                                </td>

                                <td>

                                    <button type="button"
                                            class="btn-secondary">

                                        <i class="fas fa-eye"></i>
                                        View

                                    </button>

                                </td>

                            </tr>



                            <!-- SAMPLE ANNOUNCEMENT 2 -->

                            <tr>

                                <td>#A002</td>

                                <td>

                                    <strong>
                                        New Learning Materials Available
                                    </strong>

                                </td>

                                <td>
                                    Students
                                </td>

                                <td>
                                    2026-09-22
                                </td>

                                <td>

                                    <span class="course-status active-status">
                                        Published
                                    </span>

                                </td>

                                <td>

                                    <button type="button"
                                            class="btn-secondary">

                                        <i class="fas fa-eye"></i>
                                        View

                                    </button>

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