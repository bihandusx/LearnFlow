<?php
$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Quizzes';

$navItems = array(
    array('href' => 'dashboard.php', 'icon' => 'tachometer-alt', 'label' => 'Dashboard'),
    array('href' => 'profile.php', 'icon' => 'user', 'label' => 'Profile'),
    array('href' => 'courses.php', 'icon' => 'book-open', 'label' => 'Courses'),
    array('href' => 'students.php', 'icon' => 'users', 'label' => 'Students'),
    array('href' => 'assignments.php', 'icon' => 'file-alt', 'label' => 'Assignments'),
    array('href' => 'quizzes.php', 'icon' => 'check-square', 'label' => 'Quizzes', 'active' => true),
    array('href' => 'attendance.php', 'icon' => 'calendar-check', 'label' => 'Attendance'),
    array('href' => 'announcements.php', 'icon' => 'bullhorn', 'label' => 'Announcements'),
    array('href' => 'modules.php', 'icon' => 'layer-group', 'label' => 'Modules'),
    array('href' => 'resources.php', 'icon' => 'folder-open', 'label' => 'Resources'),
    array('href' => 'recordings.php', 'icon' => 'video', 'label' => 'Recordings'),
    array('href' => 'discussions.php', 'icon' => 'comments', 'label' => 'Discussions')
);
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

            <nav class="sidebar-menu" aria-label="Teacher navigation">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?php echo $item['href']; ?>" class="menu-link<?php echo !empty($item['active']) ? ' active' : ''; ?>">
                        <i class="fas fa-<?php echo $item['icon']; ?>"></i>
                        <?php echo $item['label']; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <main class="content-area">
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-btn" type="button" aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="dashboard-title">
                        <p class="small-label">Teacher Portal</p>
                        <h1>Quizzes</h1>
                    </div>
                </div>

                <div class="topbar-right">
                    <div class="project-pill">LearnFlow</div>
                    <button class="icon-btn" type="button" aria-label="Notifications">
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

            <div class="dashboard-main">
                <section class="page-header">
                    <div>
                        <p class="card-label">Examination tools</p>
                        <h2>Create quizzes and exams with structured questions.</h2>
                    </div>
                </section>

                <div class="panel-grid">
                    <article class="panel">
                        <div class="panel-header">
                            <p class="card-label">New quiz</p>
                            <h3>Quiz details</h3>
                        </div>
                        <form class="form-grid">
                            <div class="form-field">
                                <label>Quiz title</label>
                                <input type="text" value="Midterm Review Quiz">
                            </div>
                            <div class="form-field">
                                <label>Course</label>
                                <input type="text" value="Database Systems">
                            </div>
                            <div class="form-field full-width">
                                <label>Instructions</label>
                                <textarea rows="4">Select the best answer for each statement. You have 30 minutes to complete.</textarea>
                            </div>
                        </form>
                    </article>

                    <article class="panel">
                        <div class="panel-header">
                            <p class="card-label">Question bank</p>
                            <h3>Sample questions</h3>
                        </div>
                        <div class="panel-list">
                            <div class="resource-item">
                                <div>
                                    <h3>Question 1 • SQL Joins</h3>
                                    <p>Which JOIN returns only matching rows?</p>
                                </div>
                                <button class="btn btn-ghost" type="button">Edit</button>
                            </div>
                            <div class="resource-item">
                                <div>
                                    <h3>Question 2 • Normalization</h3>
                                    <p>What is the goal of 3NF?</p>
                                </div>
                                <button class="btn btn-ghost" type="button">Edit</button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

