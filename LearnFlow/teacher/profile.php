<?php
$teacherName = 'Dr. Amelia Chen';
$projectName = 'LearnFlow';
$pageTitle = 'Profile';
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
        <div class="brand-panel">
            <div class="brand-icon"><i class="fas fa-graduation-cap"></i></div>
            <div><p class="brand-label">LearnFlow</p><p class="brand-subtitle">Teacher Portal</p></div>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-link"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="profile.php" class="menu-link active"><i class="fas fa-user"></i>Profile</a>
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
        </nav>
    </aside>
    <div class="content-area">
        <header class="topbar"><div class="topbar-left"><button class="mobile-menu-btn"><i class="fas fa-bars"></i></button><div class="dashboard-title"><p class="small-label">Teacher Portal</p><h1>Profile</h1></div></div><div class="topbar-right"><div class="project-pill">LearnFlow</div><button class="icon-btn"><i class="fas fa-bell"></i></button><div class="profile-chip"><div class="avatar-placeholder"><i class="fas fa-user-circle"></i></div><div><span>Hello,</span><strong><?php echo $teacherName; ?></strong></div></div></div></header>
        <main class="dashboard-main">
            <section class="page-header"><div><p class="card-label">Account management</p><h2>Keep your teaching profile accurate and up to date.</h2></div><div class="page-actions"><button class="btn btn-primary">Update Profile</button></div></section>
            <div class="panel-grid">
                <article class="panel profile-card">
                    <div class="profile-photo"><div class="avatar-large"><i class="fas fa-user"></i></div><div><h3>Dr. Amelia Chen</h3><p>Senior Lecturer • Computer Science</p></div></div>
                    <div class="profile-meta"><div><span class="meta-label">Department</span><strong>School of Computing</strong></div><div><span class="meta-label">Experience</span><strong>12 years</strong></div><div><span class="meta-label">Office Hours</span><strong>Mon–Thu, 2:00 PM</strong></div></div>
                </article>
                <article class="panel">
                    <div class="panel-header"><p class="card-label">Personal details</p><h3>Profile information</h3></div>
                    <form class="form-grid">
                        <div class="form-field"><label>Name</label><input type="text" value="Dr. Amelia Chen"></div>
                        <div class="form-field"><label>Email</label><input type="email" value="amelia.chen@learnflow.edu"></div>
                        <div class="form-field"><label>Phone</label><input type="tel" value="+1 555 0148"></div>
                        <div class="form-field"><label>Department</label><input type="text" value="Computer Science"></div>
                        <div class="form-field full-width"><label>Teaching Information</label><textarea rows="4">Advanced databases, systems design, and research supervision.</textarea></div>
                        <div class="form-field full-width"><label>Qualifications</label><textarea rows="4">PhD in Computer Science, MSc in Software Engineering, Certified Teaching Professional.</textarea></div>
                    </form>
                </article>
            </div>
        </main>
    </div>
</div>
</body></html>
