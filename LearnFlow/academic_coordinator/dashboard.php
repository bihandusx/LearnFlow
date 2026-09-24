<?php
$coordinatorName = 'Coordinator Name';
$projectName = 'LearnFlow';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo $projectName; ?> | Academic Coordinator Dashboard
    </title>


    <!-- Teacher dashboard base design -->
    <link rel="stylesheet" href="../css/teacher.css">

    <!-- Coordinator specific styles -->
    <link rel="stylesheet" href="../css/coordinator.css">


    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

</head>


<body>

<div class="dashboard-shell">


    <!-- ==========================================
         SIDEBAR
         ========================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

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

    <a href="dashboard.php" class="menu-link active">
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

    <a href="announcements.php" class="menu-link">
        <i class="fas fa-bullhorn"></i>
        Announcements
    </a>

    <a href="../auth/logout.php" class="menu-link logout-link">
        <i class="fas fa-sign-out-alt"></i>
        Logout
    </a>

</nav>

        

    </aside>



    <!-- ==========================================
         RIGHT SIDE
         ========================================== -->

    <div class="content-area">


        <!-- ======================================
             TOP BAR
             ====================================== -->

        <header class="topbar">


            <div class="topbar-left">


                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>


                <div class="dashboard-title">

                    <p class="small-label">
                        Dashboard
                    </p>

                    <h1>
                        Welcome, Coordinator!
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



        <!-- ======================================
             MAIN DASHBOARD
             ====================================== -->
        <main class="dashboard-main">

    <!-- ================= WELCOME ================= -->

    <section class="coordinator-welcome">

        <div class="welcome-content">

            <p class="card-label">ACADEMIC COORDINATOR</p>

            <h2>Welcome back, <?php echo $coordinatorName; ?></h2>

            <p>
                Manage academic planning, monitor learning progress,
                maintain content quality and review academic performance.
            </p>

        </div>

        <div class="welcome-icon">
            <i class="fas fa-user-graduate"></i>
        </div>

    </section>


    <!-- ================= QUICK OVERVIEW ================= -->

    <section class="dashboard-overview-grid">

        <article class="overview-card">

            <div class="overview-icon">
                <i class="fas fa-book-open"></i>
            </div>

            <div>
                <p>Course</p>
                <h3>1</h3>
                <span>Academic course</span>
            </div>

        </article>


        <article class="overview-card">

            <div class="overview-icon">
                <i class="fas fa-layer-group"></i>
            </div>

            <div>
                <p>Modules</p>
                <h3>4</h3>
                <span>Course modules</span>
            </div>

        </article>


        <article class="overview-card">

            <div class="overview-icon">
                <i class="fas fa-chart-line"></i>
            </div>

            <div>
                <p>Course Progress</p>
                <h3>50%</h3>
                <span>Overall completion</span>
            </div>

        </article>


        <article class="overview-card">

            <div class="overview-icon">
                <i class="fas fa-clock"></i>
            </div>

            <div>
                <p>Pending Reviews</p>
                <h3>2</h3>
                <span>Require attention</span>
            </div>

        </article>

    </section>



    <!-- ==================================================
         ACADEMIC PLANNING
    =================================================== -->

   <section class="dashboard-feature-section planning-section">


        <div class="feature-section-heading">

            <div class="section-heading-icon">
                <i class="fas fa-calendar-check"></i>
            </div>

            <div>
                <h2>Academic Planning</h2>
                <p>Plan and coordinate academic activities.</p>
            </div>

        </div>


        <div class="professional-feature-grid">


            <a href="courses.php" class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-book-open"></i>
                </div>

                <div>
                    <h3>Course Overview</h3>
                    <p>View and coordinate the academic course.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="academic_schedules.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>

                <div>
                    <h3>Academic Schedules</h3>
                    <p>Create and manage academic schedules.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="semester_plans.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>

                <div>
                    <h3>Semester Plans</h3>
                    <p>Plan and organize semester activities.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="Review_assignments.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>

                <div>
                    <h3>Review Assignments</h3>
                    <p>Review teacher and module assignments.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="exam_schedules.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-file-alt"></i>
                </div>

                <div>
                    <h3>Examination Schedules</h3>
                    <p>Plan and review examination schedules.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>


        </div>

    </section>



    <!-- ==================================================
         ACADEMIC MONITORING
    =================================================== -->

    <section class="dashboard-feature-section monitoring-section">


        <div class="feature-section-heading">

            <div class="section-heading-icon">
                <i class="fas fa-chart-line"></i>
            </div>

            <div>
                <h2>Academic Monitoring</h2>
                <p>Monitor progress, performance and engagement.</p>
            </div>

        </div>


        <div class="professional-feature-grid">


            <a href="course_progress.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>

                <div>
                    <h3>Course Completion</h3>
                    <p>Monitor module and course completion.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="teacher_performance.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-user-check"></i>
                </div>

                <div>
                    <h3>Teacher Performance</h3>
                    <p>Review teaching activity and performance.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="student_engagement.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>

                <div>
                    <h3>Student Engagement</h3>
                    <p>Monitor student participation and engagement.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="attendance_statistics.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>

                <div>
                    <h3>Attendance Statistics</h3>
                    <p>Review student attendance information.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>


        </div>

    </section>



    <!-- ==================================================
         CONTENT QUALITY MANAGEMENT
    =================================================== -->

    <section class="dashboard-feature-section quality-section">

        <div class="feature-section-heading">

            <div class="section-heading-icon">
                <i class="fas fa-shield-alt"></i>
            </div>

            <div>
                <h2>Content Quality Management</h2>
                <p>Review and maintain academic content quality.</p>
            </div>

        </div>


        <div class="professional-feature-grid">


            <a href="learning_resources.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-folder-open"></i>
                </div>

                <div>
                    <h3>Learning Resources</h3>
                    <p>Manage and approve learning resources.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="recording_reviews.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-video"></i>
                </div>

                <div>
                    <h3>Recording Reviews</h3>
                    <p>Review uploaded course recordings.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="content_standards.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-check-circle"></i>
                </div>

                <div>
                    <h3>Content Standards</h3>
                    <p>Ensure academic content meets standards.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="question_bank.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-question-circle"></i>
                </div>

                <div>
                    <h3>Question Bank</h3>
                    <p>Maintain questions for assessments.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>


        </div>

    </section>



    <!-- ==================================================
         REPORTING & COMMUNICATION
    =================================================== -->

    <section class="dashboard-feature-section reporting-section">

        <div class="feature-section-heading">

            <div class="section-heading-icon">
                <i class="fas fa-chart-bar"></i>
            </div>

            <div>
                <h2>Reporting & Communication</h2>
                <p>Review academic performance and communicate updates.</p>
            </div>

        </div>


        <div class="professional-feature-grid">


            <a href="academic_reports.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>

                <div>
                    <h3>Academic Reports</h3>
                    <p>Generate and review academic reports.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="institute_performance.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>

                <div>
                    <h3>Institute Performance</h3>
                    <p>Track overall academic performance.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="learning_outcomes.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>

                <div>
                    <h3>Learning Outcomes</h3>
                    <p>Monitor achievement of learning outcomes.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>



            <a href="announcements.php"
               class="professional-feature-card">

                <div class="feature-icon">
                    <i class="fas fa-bullhorn"></i>
                </div>

                <div>
                    <h3>Announcements</h3>
                    <p>Publish academic announcements.</p>
                </div>

                <i class="fas fa-arrow-right feature-arrow"></i>

            </a>


        </div>

    </section>


</main>
        

    </div>


</div>


</body>

</html> 