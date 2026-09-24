<?php
$coordinatorName = 'Coordinator Name';
$projectName = 'LearnFlow';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $projectName; ?> | Profile</title>

    <link rel="stylesheet" href="../css/teacher.css">
    <link rel="stylesheet" href="../css/coordinator.css">
    <link rel="stylesheet" href="../css/profile.css">

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

    <a href="profile.php" class="menu-link active">
        <i class="fas fa-user"></i>
        Profile
    </a>

    <a href="courses.php" class="menu-link">
        <i class="fas fa-book-open"></i>
        Courses
    </a>

    <a href="academic_schedules.php" class="menu-link">
        <i class="fas fa-calendar-alt"></i>
        Academic Schedules
    </a>

    <a href="semester_plans.php" class="menu-link">
        <i class="fas fa-calendar-week"></i>
        Semester Plans
    </a>

    <a href="Review_assignments.php" class="menu-link">
        <i class="fas fa-chalkboard-teacher"></i>
        Review Assignments
    </a>

    <a href="exam_schedules.php" class="menu-link">
        <i class="fas fa-file-alt"></i>
        Examination Schedules
    </a>

    <a href="course_progress.php" class="menu-link">
        <i class="fas fa-chart-line"></i>
        Course Completion
    </a>

    <a href="teacher_performance.php" class="menu-link">
        <i class="fas fa-user-check"></i>
        Teacher Performance
    </a>

    <a href="student_engagement.php" class="menu-link">
        <i class="fas fa-users"></i>
        Student Engagement
    </a>

    <a href="attendance_statistics.php" class="menu-link">
        <i class="fas fa-clipboard-check"></i>
        Attendance Statistics
    </a>

    <a href="learning_resources.php" class="menu-link">
        <i class="fas fa-folder-open"></i>
        Learning Resources
    </a>

    <a href="recording_reviews.php" class="menu-link">
        <i class="fas fa-video"></i>
        Recording Reviews
    </a>

    <a href="content_standards.php" class="menu-link">
        <i class="fas fa-check-circle"></i>
        Content Standards
    </a>

    <a href="question_bank.php" class="menu-link">
        <i class="fas fa-question-circle"></i>
        Question Bank
    </a>

    <a href="academic_reports.php" class="menu-link">
        <i class="fas fa-chart-bar"></i>
        Academic Reports
    </a>

    <a href="institute_performance.php" class="menu-link">
        <i class="fas fa-chart-pie"></i>
        Institute Performance
    </a>

    <a href="learning_outcomes.php" class="menu-link">
        <i class="fas fa-graduation-cap"></i>
        Learning Outcomes
    </a>

    <a href="announcements.php" class="menu-link">
        <i class="fas fa-bullhorn"></i>
        Announcements
    </a>

    <a href="../auth/logout.php" class="menu-link logout-link">
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

                    <p class="small-label">Account</p>
                    <h1>My Profile</h1>

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

    <!-- PAGE INTRO -->

    <section class="page-intro-card">

        <div>

            <p class="card-label">
                ACCOUNT INFORMATION
            </p>

            <h2>
                Academic Coordinator Profile
            </h2>

            <p>
                Manage your personal and account information.
            </p>

        </div>

        <div class="page-intro-icon">
            <i class="fas fa-user"></i>
        </div>

    </section>


    <!-- PROFILE CARD -->

    <section class="profile-page-card">

        <!-- BLUE COVER -->

        <div class="profile-cover"></div>


        <!-- PROFILE HEADER -->

        <div class="profile-header-content">

            <div class="profile-identity">

                <div class="profile-avatar-large">
                    <i class="fas fa-user"></i>
                </div>

                <div class="profile-name-area">

                    <h2 id="displayProfileName">
                        Coordinator Name
                    </h2>

                    <p>
                        Academic Coordinator
                    </p>

                </div>

            </div>


            <button type="button"
                    class="profile-edit-btn"
                    id="editProfileBtn"
                    onclick="enableProfileEdit()">

                <i class="fas fa-pen"></i>
                Edit Profile

            </button>

        </div>


        <!-- PROFILE INFORMATION -->

        <div class="profile-body">


            <div class="profile-section-title">

                <h3>Personal Information</h3>

                <p>
                    View and update your personal details.
                </p>

            </div>


            <div class="profile-form-grid">


                <!-- FULL NAME -->

                <div class="profile-field">

                    <label for="profileName">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="profileName"
                        value="Coordinator Name"
                        readonly>

                </div>


                <!-- EMAIL -->

                <div class="profile-field">

                    <label for="profileEmail">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="profileEmail"
                        value="coordinator@learnflow.lk"
                        readonly>

                </div>


                <!-- PHONE -->

                <div class="profile-field">

                    <label for="profilePhone">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        id="profilePhone"
                        placeholder="Add phone number"
                        readonly>

                </div>


                <!-- ADDRESS -->

                <div class="profile-field">

                    <label for="profileAddress">
                        Address
                    </label>

                    <textarea
                        id="profileAddress"
                        placeholder="Add your address"
                        readonly></textarea>

                </div>


                <!-- ROLE -->

                <div class="profile-field">

                    <label>
                        Role
                    </label>

                    <div class="profile-role">
                        Academic Coordinator
                    </div>

                </div>


                <!-- ACCOUNT STATUS -->

                <div class="profile-field">

                    <label>
                        Account Status
                    </label>

                    <div class="profile-status">

                        <span class="profile-status-dot"></span>

                        Active

                    </div>

                </div>


            </div>


            <!-- SAVE / CANCEL -->

            <div class="profile-form-actions"
                 id="profileActions">


                <button type="button"
                        class="profile-cancel-btn"
                        onclick="cancelProfileEdit()">

                    <i class="fas fa-xmark"></i>
                    Cancel

                </button>


                <button type="button"
                        class="profile-save-btn"
                        onclick="saveProfileUI()">

                    <i class="fas fa-check"></i>
                    Save Changes

                </button>


            </div>


            <div class="profile-ui-note">

                <i class="fas fa-circle-info"></i>

                <span>
                    Profile editing is currently a user-interface
                    demonstration. Changes are not stored in the database.
                </span>

            </div>


        </div>

    </section>

</main>


</div>
</div>
<script>

const editableProfileFields = [
    "profileName",
    "profileEmail",
    "profilePhone",
    "profileAddress"
];

let originalProfileValues = {};


function enableProfileEdit() {

    editableProfileFields.forEach(function(id) {

        const field = document.getElementById(id);

        originalProfileValues[id] = field.value;

        field.removeAttribute("readonly");

        field.parentElement.classList.add("editing");

    });


    document.getElementById("editProfileBtn").style.display = "none";

    document.getElementById("profileActions")
            .classList.add("show");
}


function cancelProfileEdit() {

    editableProfileFields.forEach(function(id) {

        const field = document.getElementById(id);

        field.value = originalProfileValues[id];

        field.setAttribute("readonly", true);

        field.parentElement.classList.remove("editing");

    });


    document.getElementById("editProfileBtn").style.display = "inline-flex";

    document.getElementById("profileActions")
            .classList.remove("show");
}


function saveProfileUI() {

    editableProfileFields.forEach(function(id) {

        const field = document.getElementById(id);

        field.setAttribute("readonly", true);

        field.parentElement.classList.remove("editing");

    });


    const newName = document.getElementById("profileName").value;

    if (newName.trim() !== "") {

        document.getElementById("displayProfileName").textContent = newName;

    }


    document.getElementById("editProfileBtn").style.display = "inline-flex";

    document.getElementById("profileActions")
            .classList.remove("show");

}

</script>

</body>

</html>