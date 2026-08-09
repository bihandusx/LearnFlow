<?php
$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Resources';

$navItems = array(
    array('href' => 'dashboard.php', 'icon' => 'tachometer-alt', 'label' => 'Dashboard'),
    array('href' => 'profile.php', 'icon' => 'user', 'label' => 'Profile'),
    array('href' => 'courses.php', 'icon' => 'book-open', 'label' => 'Courses'),
    array('href' => 'students.php', 'icon' => 'users', 'label' => 'Students'),
    array('href' => 'assignments.php', 'icon' => 'file-alt', 'label' => 'Assignments'),
    array('href' => 'quizzes.php', 'icon' => 'check-square', 'label' => 'Quizzes'),
    array('href' => 'attendance.php', 'icon' => 'calendar-check', 'label' => 'Attendance'),
    array('href' => 'announcements.php', 'icon' => 'bullhorn', 'label' => 'Announcements'),
    array('href' => 'modules.php', 'icon' => 'layer-group', 'label' => 'Modules'),
    array('href' => 'resources.php', 'icon' => 'folder-open', 'label' => 'Resources', 'active' => true),
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
                        <h1>Resources</h1>
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
                        <p class="card-label">Course materials</p>
                        <h2>Upload and organize learning resources for your students.</h2>
                    </div>
                    <div class="page-actions">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-upload"></i>
                            Upload Resource
                        </button>
                    </div>
                </section>

                <section class="panel upload-panel">
                    <div class="upload-box">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <h3>Drop files here or browse</h3>
                        <p>PDFs, slides, and handouts are supported.</p>
                        <button class="btn btn-primary" type="button">Choose Files</button>
                    </div>
                </section>

                <section class="panel-list">
                    <article class="resource-item">
                        <div>
                            <h3>Lecture Notes - Week 03.pdf</h3>
                            <p>Uploaded on 22 Jul 2026 • 2.4 MB</p>
                        </div>
                        <div class="action-group">
                            <button class="btn btn-ghost" type="button">Download</button>
                            <button class="btn btn-secondary" type="button">Delete</button>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

