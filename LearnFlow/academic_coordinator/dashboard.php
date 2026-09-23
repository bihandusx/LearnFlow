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


            <a href="dashboard.php"
               class="menu-link active">

                <i class="fas fa-tachometer-alt"></i>
                Dashboard

            </a>


            <a href="profile.php"
               class="menu-link">

                <i class="fas fa-user"></i>
                Profile

            </a>


            <a href="courses.php"
               class="menu-link">

                <i class="fas fa-book-open"></i>
                Courses

            </a>


            <a href="academic_terms.php"
               class="menu-link">

                <i class="fas fa-calendar-week"></i>
                Academic Terms

            </a>


            <a href="teacher_courses.php"
               class="menu-link">

                <i class="fas fa-chalkboard-teacher"></i>
                Teacher Assignments

            </a>


            <a href="question_bank.php"
               class="menu-link">

                <i class="fas fa-question-circle"></i>
                Question Bank

            </a>


            <a href="learning_resources.php"
               class="menu-link">

                <i class="fas fa-folder-open"></i>
                Learning Resources

            </a>


            <a href="announcements.php"
               class="menu-link">

                <i class="fas fa-bullhorn"></i>
                Announcements

            </a>


            <a href="academic_reports.php"
               class="menu-link">

                <i class="fas fa-chart-bar"></i>
                Academic Reports

            </a>


            <a href="../auth/logout.php"
               class="menu-link logout-link">

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


            <!-- ==================================
                 OVERVIEW
                 ================================== -->

            <section class="overview-cards">


                <!-- Welcome card -->

                <article class="summary-card">


                    <div>

                        <p class="card-label">
                            Welcome back
                        </p>

                        <h2>
                            Keep learning on track and
                            academic standards high.
                        </h2>

                    </div>


                    <div class="summary-icon">

                        <i class="fas fa-user-graduate"></i>

                    </div>


                </article>



                <!-- Active courses -->

                <article class="stat-card">


                    <div>

                        <p class="card-label">
                            Active Courses
                        </p>

                        <h3>
                            0
                        </h3>

                    </div>


                    <span class="stat-badge">
                        Current term
                    </span>


                </article>



                <!-- Pending approvals -->

                <article class="stat-card">


                    <div>

                        <p class="card-label">
                            Pending Approvals
                        </p>

                        <h3>
                            0
                        </h3>

                    </div>


                    <span class="stat-badge">
                        Review resources
                    </span>


                </article>


            </section>



            <!-- ==================================
                 COORDINATOR FEATURES
                 ================================== -->

            <section class="card-grid">


                <!-- COURSES -->

                <a href="courses.php"
                   class="dashboard-card">


                    <div class="card-icon bg-blue">

                        <i class="fas fa-book-open"></i>

                    </div>


                    <h3>
                        Courses
                    </h3>


                    <p>
                        Coordinate and oversee academic courses.
                    </p>


                </a>



                <!-- ACADEMIC TERMS -->

                <a href="academic_terms.php"
                   class="dashboard-card">


                    <div class="card-icon bg-purple">

                        <i class="fas fa-calendar-week"></i>

                    </div>


                    <h3>
                        Academic Terms
                    </h3>


                    <p>
                        Plan and manage academic terms.
                    </p>


                </a>



                <!-- TEACHER ASSIGNMENTS -->

                <a href="teacher_courses.php"
                   class="dashboard-card">


                    <div class="card-icon bg-green">

                        <i class="fas fa-chalkboard-teacher"></i>

                    </div>


                    <h3>
                        Teacher Assignments
                    </h3>


                    <p>
                        Assign teachers to academic courses.
                    </p>


                </a>



                <!-- QUESTION BANK -->

                <a href="question_bank.php"
                   class="dashboard-card">


                    <div class="card-icon bg-indigo">

                        <i class="fas fa-question-circle"></i>

                    </div>


                    <h3>
                        Question Bank
                    </h3>


                    <p>
                        Maintain academic question banks.
                    </p>


                </a>



                <!-- LEARNING RESOURCES -->

                <a href="learning_resources.php"
                   class="dashboard-card">


                    <div class="card-icon bg-cyan">

                        <i class="fas fa-folder-open"></i>

                    </div>


                    <h3>
                        Learning Resources
                    </h3>


                    <p>
                        Review and approve learning resources.
                    </p>


                </a>



                <!-- ANNOUNCEMENTS -->

                <a href="announcements.php"
                   class="dashboard-card">


                    <div class="card-icon bg-orange">

                        <i class="fas fa-bullhorn"></i>

                    </div>


                    <h3>
                        Announcements
                    </h3>


                    <p>
                        Publish academic announcements.
                    </p>


                </a>



                <!-- ACADEMIC REPORTS -->

                <a href="academic_reports.php"
                   class="dashboard-card">


                    <div class="card-icon bg-red">

                        <i class="fas fa-chart-bar"></i>

                    </div>


                    <h3>
                        Academic Reports
                    </h3>


                    <p>
                        Generate and review academic reports.
                    </p>


                </a>


            </section>


        </main>


    </div>


</div>


</body>

</html> 