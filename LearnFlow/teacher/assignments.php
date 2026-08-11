<?php
$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Assignments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $projectName; ?> | <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../css/teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="dashboard-shell">
    <aside class="sidebar">
        <div class="brand-panel"><div class="brand-icon"><i class="fas fa-graduation-cap"></i></div><div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Teacher Portal</p></div></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a>
            <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a>
            <a href="students.php" class="menu-link"><i class="fas fa-users"></i>Students</a>
            <a href="assignments.php" class="menu-link active"><i class="fas fa-file-alt"></i>Assignments</a>
            <a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i>Quizzes</a>
            <a href="attendance.php" class="menu-link"><i class="fas fa-calendar-check"></i>Attendance</a>
            <a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i>Announcements</a>
            <a href="modules.php" class="menu-link"><i class="fas fa-layer-group"></i>Modules</a>
            <a href="resources.php" class="menu-link"><i class="fas fa-folder-open"></i>Resources</a>
            <a href="recordings.php" class="menu-link"><i class="fas fa-video"></i>Recordings</a>
            <a href="discussions.php" class="menu-link"><i class="fas fa-comments"></i>Discussions</a>
        </nav>
    </aside>
    <div class="content-area">
        <header class="topbar"><div class="topbar-left"><button class="mobile-menu-btn"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Teacher Portal</p><h1>Assignments</h1></div></div><div class="topbar-right"><div class="project-pill">LearnFlow</div><button class="icon-btn"><i class="fas fa-bell"></i></button><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?php echo $teacherName; ?></strong></div></div></div></header>
        <main class="dashboard-main">
            <section class="page-header"><div><p class="card-label">Assessment center</p><h2>Create assignments and review submissions.</h2></div></section>
            <div class="panel-grid">
                <article class="panel">
                    <div class="panel-header"><p class="card-label">Create assignment</p><h3>New task</h3></div>
                    <form class="form-grid">
                        <div class="form-field"><label>Title</label><input type="text" value="Research Paper Review"></div>
                        <div class="form-field"><label>Course</label><input type="text" value="Database Systems"></div>
                        <div class="form-field full-width"><label>Instructions</label><textarea rows="4">Evaluate the provided paper and submit a structured review with references.</textarea></div>
                        <div class="form-field"><label>Due date</label><input type="text" value="12 Aug 2026"></div>
                    </form>
                </article>
                <article class="panel">
                    <div class="panel-header"><p class="card-label">Submission review</p><h3>Recent submissions</h3></div>
                    <div class="table-card"><table class="table"><thead><tr><th>Student</th><th>Status</th><th>Action</th></tr></thead><tbody><tr><td><strong>Daniel Ortiz</strong></td><td><span class="status-pill">Pending</span></td><td><div class="action-group"><button class="btn btn-ghost">Grade</button><button class="btn btn-secondary">Feedback</button></div></td></tr><tr><td><strong>Nadia Lopez</strong></td><td><span class="status-pill active">Reviewed</span></td><td><div class="action-group"><button class="btn btn-ghost">Grade</button><button class="btn btn-secondary">Feedback</button></div></td></tr></tbody></table></div>
                </article>
            </div>
        </main>
    </div>
</div>
</body></html>
