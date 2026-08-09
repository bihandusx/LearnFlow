<?php
require_once '../config/db.php';

$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Modules';

$navItems = array(
    array('href' => 'dashboard.php', 'icon' => 'tachometer-alt', 'label' => 'Dashboard'),
    array('href' => 'profile.php', 'icon' => 'user', 'label' => 'Profile'),
    array('href' => 'courses.php', 'icon' => 'book-open', 'label' => 'Courses'),
    array('href' => 'students.php', 'icon' => 'users', 'label' => 'Students'),
    array('href' => 'assignments.php', 'icon' => 'file-alt', 'label' => 'Assignments'),
    array('href' => 'quizzes.php', 'icon' => 'check-square', 'label' => 'Quizzes'),
    array('href' => 'attendance.php', 'icon' => 'calendar-check', 'label' => 'Attendance'),
    array('href' => 'announcements.php', 'icon' => 'bullhorn', 'label' => 'Announcements'),
    array('href' => 'modules.php', 'icon' => 'layer-group', 'label' => 'Modules', 'active' => true),
    array('href' => 'resources.php', 'icon' => 'folder-open', 'label' => 'Resources'),
    array('href' => 'recordings.php', 'icon' => 'video', 'label' => 'Recordings'),
    array('href' => 'discussions.php', 'icon' => 'comments', 'label' => 'Discussions')
);

$modules = array();
$stmt = $conn->prepare('SELECT ModuleID, ModuleName, ModuleOrder, Description, BatchID FROM MODULE ORDER BY ModuleOrder ASC, ModuleName ASC');
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $modules[] = $row;
    }
    $stmt->close();
}
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
                        <h1>Modules</h1>
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
                        <p class="card-label">Module management</p>
                        <h2>Organize content into structured learning modules.</h2>
                    </div>
                    <div class="page-actions">
                        <a href="add_module.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Module
                        </a>
                    </div>
                </section>

                <section class="panel-list">
                    <?php if (!empty($modules)): ?>
                        <?php foreach ($modules as $module): ?>
                            <article class="resource-item">
                                <div>
                                    <h3><?php echo htmlspecialchars($module['ModuleName']); ?></h3>
                                    <p><?php echo nl2br(htmlspecialchars($module['Description'])); ?></p>
                                    <p><strong>Order:</strong> <?php echo (int) $module['ModuleOrder']; ?> · <strong>Batch ID:</strong> <?php echo (int) $module['BatchID']; ?></p>
                                </div>
                                <div class="action-group">
                                    <a href="edit_module.php?id=<?php echo (int) $module['ModuleID']; ?>" class="btn btn-ghost">Edit</a>
                                    <a href="delete_module.php?id=<?php echo (int) $module['ModuleID']; ?>" class="btn btn-secondary" onclick="return confirm('Delete this module?');">Delete</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <article class="resource-item">
                            <div>
                                <h3>No modules found</h3>
                                <p>Add your first module to get started.</p>
                            </div>
                        </article>
                    <?php endif; ?>
                </section>
            </div>
        </main>
    </div>
</body>
</html>

