<?php
$coordinatorName = 'Coordinator Name';
$projectName = 'LearnFlow';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $projectName; ?> | Courses</title>

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

            <a href="courses.php" class="menu-link active">
                <i class="fas fa-book-open"></i>
                Courses
            </a>

            <a href="academic_terms.php" class="menu-link">
                <i class="fas fa-calendar-week"></i>
                Academic Terms
            </a>

            <a href="teacher_courses.php" class="menu-link">
                <i class="fas fa-chalkboard-teacher"></i>
                Teacher Assignments
            </a>

            <a href="question_bank.php" class="menu-link">
                <i class="fas fa-question-circle"></i>
                Question Bank
            </a>

            <a href="learning_resources.php" class="menu-link">
                <i class="fas fa-folder-open"></i>
                Learning Resources
            </a>

            <a href="announcements.php" class="menu-link">
                <i class="fas fa-bullhorn"></i>
                Announcements
            </a>

            <a href="academic_reports.php" class="menu-link">
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
                        Academic Management
                    </p>

                    <h1>Courses</h1>

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


            <!-- PAGE INFORMATION -->

            <section class="page-intro-card">

                <div>

                    <p class="card-label">
                        Course Coordination
                    </p>

                    <h2>Academic Courses</h2>

                    <p>
                        View and coordinate courses available
                        through LearnFlow.
                    </p>

                </div>

                <div class="page-intro-icon">
                    <i class="fas fa-book-open"></i>
                </div>

            </section>



            <!-- COURSE SUMMARY -->

            <section class="course-summary-grid">


                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-book"></i>
                    </div>

                    <div>
                        <p>Total Courses</p>
                        <h3>1</h3>
                    </div>

                </article>


                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-circle-check"></i>
                    </div>

                    <div>
                        <p>Active Courses</p>
                        <h3>1</h3>
                    </div>

                </article>


                <article class="mini-stat-card">

                    <div class="mini-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>

                    <div>
                        <p>Current Batches</p>
                        <h3>1</h3>
                    </div>

                </article>


            </section>



            <!-- COURSE TABLE -->

            <section class="course-table-card">


                <div class="course-table-heading">

                    <div>

                        <h2>Course Overview</h2>

                        <p>
                            Academic courses currently available
                            in the system.
                        </p>

                    </div>

                </div>



                <div class="table-responsive">


                    <table class="coordinator-table">


                        <thead>

                        <tr>
                            <th>Course ID</th>
                            <th>Course Name</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>

                        </thead>


                        <tbody>


                        <!-- UI sample data -->

                        <tr>

                            <td>#001</td>

                            <td>
                                <strong>
                                    Web Development
                                </strong>
                            </td>

                            <td>
                                Web development course
                            </td>

                            <td>

                                <span class="course-status active-status">
                                    Active
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