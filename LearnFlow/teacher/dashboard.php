<?php
$teacherName = 'Teacher Name';
$projectName = 'LearnFlow';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $projectName; ?> | Teacher Dashboard</title>
    <link rel="stylesheet" href="../css/teacher.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
<div class="dashboard-shell">
    <aside class="sidebar">
        <div class="brand-panel"><div class="brand-icon"><i class="fas fa-graduation-cap"></i></div><div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Teacher Portal</p></div></div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-link active"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="profile.php" class="menu-link"><i class="fas fa-user"></i>Profile</a>
            <a href="courses.php" class="menu-link"><i class="fas fa-book-open"></i>Courses</a>
            <a href="students.php" class="menu-link"><i class="fas fa-users"></i>Students</a>
            <a href="assignments.php" class="menu-link"><i class="fas fa-file-alt"></i>Assignments</a>
            <a href="quizzes.php" class="menu-link"><i class="fas fa-check-square"></i>Quizzes</a>
            <a href="attendance.php" class="menu-link"><i class="fas fa-calendar-check"></i>Attendance</a>
            <a href="announcements.php" class="menu-link"><i class="fas fa-bullhorn"></i>Announcements</a>
            <a href="modules.php" class="menu-link"><i class="fas fa-layer-group"></i>Modules</a>
            <a href="resources.php" class="menu-link"><i class="fas fa-folder-open"></i>Resources</a>
            <a href="recordings.php" class="menu-link"><i class="fas fa-video"></i>Recordings</a>
            <a href="discussions.php" class="menu-link"><i class="fas fa-comments"></i>Discussions</a>
            <a href="../auth/logout.php" class="menu-link logout-link"><i class="fas fa-sign-out-alt"></i>Logout</a>
        </nav>
    </aside>
    <div class="content-area">
        <header class="topbar"><div class="topbar-left"><button class="mobile-menu-btn"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Dashboard</p><h1>Welcome, Teacher!</h1></div></div><div class="topbar-right"><div class="project-pill">LearnFlow</div><button class="icon-btn"><i class="fas fa-bell"></i></button><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?php echo $teacherName; ?></strong></div></div></div></header>
        <nav class="horizontal-nav"><a href="dashboard.php" class="nav-item active">Dashboard</a><a href="profile.php" class="nav-item">Profile</a><a href="courses.php" class="nav-item">Courses</a><a href="students.php" class="nav-item">Students</a><a href="assignments.php" class="nav-item">Assignments</a><a href="quizzes.php" class="nav-item">Quizzes</a><a href="attendance.php" class="nav-item">Attendance</a><a href="announcements.php" class="nav-item">Announcements</a><a href="modules.php" class="nav-item">Modules</a><a href="resources.php" class="nav-item">Resources</a><a href="recordings.php" class="nav-item">Recordings</a><a href="discussions.php" class="nav-item">Discussions</a></nav>
        <main class="dashboard-main">
            <section class="overview-cards">
                <article class="summary-card"><div><p class="card-label">Welcome back</p><h2>Ready to shape the next generation?</h2></div><div class="summary-icon"><i class="fas fa-chalkboard-teacher"></i></div></article>
                <article class="stat-card"><div><p class="card-label">Active classes</p><h3>8</h3></div><span class="stat-badge">Updated today</span></article>
                <article class="stat-card"><div><p class="card-label">Pending tasks</p><h3>14</h3></div><span class="stat-badge">Review now</span></article>
            </section>
            <section class="card-grid">
                <a href="courses.php" class="dashboard-card"><div class="card-icon bg-blue"><i class="fas fa-book-open"></i></div><h3>Manage Courses</h3><p>Create and organize course content.</p></a>
                <a href="students.php" class="dashboard-card"><div class="card-icon bg-green"><i class="fas fa-users"></i></div><h3>Manage Students</h3><p>Track participation and support student progress.</p></a>
                <a href="assignments.php" class="dashboard-card"><div class="card-icon bg-purple"><i class="fas fa-file-alt"></i></div><h3>Assignments</h3><p>Review submissions and share briefs.</p></a>
                <a href="quizzes.php" class="dashboard-card"><div class="card-icon bg-teal"><i class="fas fa-question-circle"></i></div><h3>Quizzes</h3><p>Design exams and practice tests.</p></a>
                <a href="attendance.php" class="dashboard-card"><div class="card-icon bg-orange"><i class="fas fa-calendar-check"></i></div><h3>Attendance</h3><p>Keep classroom records current.</p></a>
                <a href="announcements.php" class="dashboard-card"><div class="card-icon bg-cyan"><i class="fas fa-bullhorn"></i></div><h3>Announcements</h3><p>Post updates and class notices.</p></a>
                <a href="modules.php" class="dashboard-card"><div class="card-icon bg-indigo"><i class="fas fa-layer-group"></i></div><h3>Modules</h3><p>Arrange lessons into structured modules.</p></a>
                <a href="resources.php" class="dashboard-card"><div class="card-icon bg-red"><i class="fas fa-folder-open"></i></div><h3>Resources</h3><p>Upload learning materials and references.</p></a>
            </section>
        </main>
    </div>
</div>
</body></html>
