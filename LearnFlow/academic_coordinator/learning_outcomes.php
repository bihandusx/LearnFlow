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
        <?php echo $projectName; ?> | Learning Outcomes
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


            <a href="institute_performance.php" class="menu-link">
                <i class="fas fa-chart-pie"></i>
                Institute Performance
            </a>


            <!-- ACTIVE PAGE -->

            <a href="learning_outcomes.php"
               class="menu-link active">

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



    <!-- ================= CONTENT AREA ================= -->

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
                        Learning Outcomes
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
                        Learning Outcomes
                    </h2>

                    <p>
                        Monitor student achievement against
                        the expected learning outcomes of
                        the course.
                    </p>

                </div>


                <div class="page-intro-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>

            </section>



            <!-- SUMMARY CARDS -->

            <section class="course-summary-grid">


                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-list-check"></i>
                    </div>

                    <div>
                        <p>Total Learning Outcomes</p>
                        <h3>4</h3>
                    </div>

                </article>



                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-circle-check"></i>
                    </div>

                    <div>
                        <p>Outcomes Achieved</p>
                        <h3>2</h3>
                    </div>

                </article>



                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>

                    <div>
                        <p>Overall Achievement</p>
                        <h3>75%</h3>
                    </div>

                </article>


            </section>



            <!-- LEARNING OUTCOME TABLE -->

            <section class="course-table-card">


                <div class="course-table-heading">

                    <div>

                        <h2>
                            Learning Outcome Achievement
                        </h2>

                        <p>
                            Review student achievement for each
                            expected learning outcome.
                        </p>

                    </div>

                </div>



                <div class="table-responsive">


                    <table class="coordinator-table">


                        <thead>

                            <tr>

                                <th>Outcome ID</th>

                                <th>Learning Outcome</th>

                                <th>Related Module</th>

                                <th>Achievement Rate</th>

                                <th>Status</th>

                            </tr>

                        </thead>


                        <tbody>


                            <!-- SAMPLE OUTCOME 1 -->

                            <tr>

                                <td>LO1</td>

                                <td>
                                    <strong>
                                        Understand fundamental concepts
                                    </strong>
                                </td>

                                <td>
                                    Module 1
                                </td>

                                <td>
                                    <strong>90%</strong>
                                </td>

                                <td>

                                    <span class="course-status active-status">
                                        Achieved
                                    </span>

                                </td>

                            </tr>



                            <!-- SAMPLE OUTCOME 2 -->

                            <tr>

                                <td>LO2</td>

                                <td>
                                    <strong>
                                        Apply theoretical knowledge
                                    </strong>
                                </td>

                                <td>
                                    Module 2
                                </td>

                                <td>
                                    <strong>82%</strong>
                                </td>

                                <td>

                                    <span class="course-status active-status">
                                        Achieved
                                    </span>

                                </td>

                            </tr>



                            <!-- SAMPLE OUTCOME 3 -->

                            <tr>

                                <td>LO3</td>

                                <td>
                                    <strong>
                                        Demonstrate practical skills
                                    </strong>
                                </td>

                                <td>
                                    Module 3
                                </td>

                                <td>
                                    <strong>68%</strong>
                                </td>

                                <td>

                                    <span class="course-status">
                                        In Progress
                                    </span>

                                </td>

                            </tr>



                            <!-- SAMPLE OUTCOME 4 -->

                            <tr>

                                <td>LO4</td>

                                <td>
                                    <strong>
                                        Complete practical activities
                                        independently
                                    </strong>
                                </td>

                                <td>
                                    Module 4
                                </td>

                                <td>
                                    <strong>60%</strong>
                                </td>

                                <td>

                                    <span class="course-status">
                                        In Progress
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