<?php
require_once '../config/db.php';

$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Edit Module';

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

$moduleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$message = '';
$module = null;

if ($moduleId > 0) {
    $stmt = $conn->prepare('SELECT ModuleID, ModuleName, ModuleOrder, Description, BatchID FROM MODULE WHERE ModuleID = ?');
    if ($stmt) {
        $stmt->bind_param('i', $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        $module = $result->fetch_assoc();
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = isset($_POST['module_id']) ? (int) $_POST['module_id'] : 0;
    $moduleName = trim($_POST['module_name'] ?? '');
    $moduleOrder = trim($_POST['module_order'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $batchId = trim($_POST['batch_id'] ?? '');

    if ($moduleId > 0 && $moduleName !== '' && $moduleOrder !== '' && $description !== '' && $batchId !== '') {
        $stmt = $conn->prepare('UPDATE MODULE SET ModuleName = ?, ModuleOrder = ?, Description = ?, BatchID = ? WHERE ModuleID = ?');
        if ($stmt) {
            $stmt->bind_param('sissi', $moduleName, $moduleOrder, $description, $batchId, $moduleId);
            if ($stmt->execute()) {
                header('Location: modules.php');
                exit;
            } else {
                $message = 'Unable to update module.';
            }
            $stmt->close();
        } else {
            $message = 'Database error.';
        }
    } else {
        $message = 'Please fill in all fields.';
    }
}

if ($module === null) {
    $module = array('ModuleID' => 0, 'ModuleName' => '', 'ModuleOrder' => '', 'Description' => '', 'BatchID' => '');
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
                        <h1>Edit Module</h1>
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
                        <h2>Update an existing learning module.</h2>
                    </div>
                </section>

                <section class="panel">
                    <?php if ($message !== ''): ?>
                        <p class="card-label" style="margin-bottom: 12px; color: #c92a2a;"><?php echo htmlspecialchars($message); ?></p>
                    <?php endif; ?>

                    <form class="form-grid" method="POST" action="edit_module.php">
                        <input type="hidden" name="module_id" value="<?php echo (int) $module['ModuleID']; ?>">
                        <div class="form-field">
                            <label for="module_name">Module Name</label>
                            <input id="module_name" name="module_name" type="text" value="<?php echo htmlspecialchars($module['ModuleName']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="module_order">Module Order</label>
                            <input id="module_order" name="module_order" type="number" value="<?php echo htmlspecialchars($module['ModuleOrder']); ?>" required>
                        </div>
                        <div class="form-field full-width">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($module['Description']); ?></textarea>
                        </div>
                        <div class="form-field">
                            <label for="batch_id">Batch ID</label>
                            <input id="batch_id" name="batch_id" type="number" value="<?php echo htmlspecialchars($module['BatchID']); ?>" required>
                        </div>
                        <div class="form-field full-width">
                            <button class="btn btn-primary" type="submit">Update Module</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
