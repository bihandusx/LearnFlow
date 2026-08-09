<?php
$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Announcements';

$navItems = array(
    array('href' => 'dashboard.php', 'icon' => 'tachometer-alt', 'label' => 'Dashboard'),
    array('href' => 'profile.php', 'icon' => 'user', 'label' => 'Profile'),
    array('href' => 'courses.php', 'icon' => 'book-open', 'label' => 'Courses'),
    array('href' => 'students.php', 'icon' => 'users', 'label' => 'Students'),
    array('href' => 'assignments.php', 'icon' => 'file-alt', 'label' => 'Assignments'),
    array('href' => 'quizzes.php', 'icon' => 'check-square', 'label' => 'Quizzes'),
    array('href' => 'attendance.php', 'icon' => 'calendar-check', 'label' => 'Attendance'),
    array('href' => 'announcements.php', 'icon' => 'bullhorn', 'label' => 'Announcements', 'active' => true),
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
            <!-- Brand panel -->
            <div class="brand-panel">
                <div class="brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div>
                    <p class="brand-label">LearnFlow</p>
                    <p class="brand-subtitle">Teacher Portal</p>
                </div>
            </div>

            <!-- Sidebar navigation -->
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
            <!-- Top bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="mobile-menu-btn" type="button" aria-label="Toggle navigation">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="dashboard-title">
                        <p class="small-label">Teacher Portal</p>
                        <h1>Announcements</h1>
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

            <!-- Main content -->
            <div class="dashboard-main">
                <section class="page-header">
                    <div>
                        <p class="card-label">Communication</p>
                        <h2>Share updates and important notices with students.</h2>
                    </div>
                </section>

                <div class="panel-grid">
                    <article class="panel">
                        <div class="panel-header">
                            <p class="card-label">New announcement</p>
                            <h3>Create message</h3>
                        </div>
                        <form class="form-grid">
                            <div class="form-field full-width">
                                <label>Title</label>
                                <input type="text" value="Lab session moved to Room 204">
                            </div>
                            <div class="form-field full-width">
                                <label>Message</label>
                                <textarea rows="4">Please report to Room 204 for the lab session tomorrow. Bring your laptops and completed reading notes.</textarea>
                            </div>
                        </form>
                    </article>

                    <article class="panel">
                        <div class="panel-header">
                            <p class="card-label">Posted notices</p>
                            <h3>Recent announcements</h3>
                        </div>
                        <div class="panel-list">
                            <div class="announcement-item">
                                <div>
                                    <h3>Assignment deadline extended</h3>
                                    <p>Posted on 01 Aug 2026 • Dr. Amelia Chen</p>
                                </div>
                            </div>
                            <div class="announcement-item">
                                <div>
                                    <h3>Midterm review session</h3>
                                    <p>Posted on 29 Jul 2026 • Dr. Amelia Chen</p>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

