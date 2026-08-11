<?php
$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Discussions';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $projectName; ?> | <?php echo $pageTitle; ?></title>

    <link rel="stylesheet" href="../css/teacher.css">
</head>

<body>

    <div class="dashboard-shell">

        <!-- Sidebar -->
        <aside class="sidebar">

            <div class="brand-panel">
                <div class="brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>

                <div>
                    <p class="brand-label">LearnFlow</p>
                    <p class="brand-subtitle">Teacher Portal</p>
                </div>
            </div>

            <nav class="sidebar-menu">
                <a href="dashboard.php" class="menu-link">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>

                <a href="profile.php" class="menu-link">
                    <i class="fas fa-user"></i>Profile
                </a>

                <a href="courses.php" class="menu-link">
                    <i class="fas fa-book-open"></i>Courses
                </a>

                <a href="students.php" class="menu-link">
                    <i class="fas fa-users"></i>Students
                </a>

                <a href="assignments.php" class="menu-link">
                    <i class="fas fa-file-alt"></i>Assignments
                </a>

                <a href="quizzes.php" class="menu-link">
                    <i class="fas fa-check-square"></i>Quizzes
                </a>

                <a href="attendance.php" class="menu-link">
                    <i class="fas fa-calendar-check"></i>Attendance
                </a>

                <a href="announcements.php" class="menu-link">
                    <i class="fas fa-bullhorn"></i>Announcements
                </a>

                <a href="modules.php" class="menu-link">
                    <i class="fas fa-layer-group"></i>Modules
                </a>

                <a href="resources.php" class="menu-link">
                    <i class="fas fa-folder-open"></i>Resources
                </a>

                <a href="recordings.php" class="menu-link">
                    <i class="fas fa-video"></i>Recordings
                </a>

                <a href="discussions.php" class="menu-link active">
                    <i class="fas fa-comments"></i>Discussions
                </a>
            </nav>

        </aside>

        <!-- Main Content -->
        <div class="content-area">

            <!-- Top Bar -->
            <header class="topbar">

                <div class="topbar-left">
                    <button class="mobile-menu-btn">
                        <i class="fas fa-bars"></i>
                    </button>

                    <div class="dashboard-title">
                        <p class="small-label">Teacher Portal</p>
                        <h1>Discussions</h1>
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
                            <strong><?php echo $teacherName; ?></strong>
                        </div>

                    </div>

                </div>

            </header>

            <!-- Dashboard Main -->
            <main class="dashboard-main">

                <section class="page-header">
                    <div>
                        <p class="card-label">Forum</p>
                        <h2>Respond to student questions and guide discussions.</h2>
                    </div>
                </section>

                <div class="panel-grid">

                    <!-- Open Threads -->
                    <article class="panel">

                        <div class="panel-header">
                            <p class="card-label">Questions</p>
                            <h3>Open threads</h3>
                        </div>

                        <div class="panel-list">

                            <div class="announcement-item">
                                <div>
                                    <h3>How do we normalize a many-to-many relationship?</h3>
                                    <p>Asked by Aisha Rahman • 14 min ago</p>
                                </div>
                            </div>

                            <div class="announcement-item">
                                <div>
                                    <h3>Can you clarify the difference between inner and outer joins?</h3>
                                    <p>Asked by David Kim • 1 hr ago</p>
                                </div>
                            </div>

                        </div>

                    </article>

                    <!-- Reply Panel -->
                    <article class="panel">

                        <div class="panel-header">
                            <p class="card-label">Reply</p>
                            <h3>Post an answer</h3>
                        </div>

                        <form class="form-grid">

                            <div class="form-field full-width">
                                <label>Topic</label>
                                <input type="text" value="Normalization guidance">
                            </div>

                            <div class="form-field full-width">
                                <label>Response</label>
                                <textarea rows="5">Introduce an associative table to break many-to-many relationships into two one-to-many relationships.</textarea>
                            </div>

                        </form>

                    </article>

                </div>

            </main>

        </div>

    </div>

</body>

</html>