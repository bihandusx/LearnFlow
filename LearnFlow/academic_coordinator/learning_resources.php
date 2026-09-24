<?php

include "../config/db.php";

$coordinatorName = 'Coordinator Name';
$projectName = 'LearnFlow';

// CREATE - Add new learning resource__________________________
if (isset($_POST['add_resource'])) {

    $title = $_POST['title'];
    $resourceType = $_POST['resource_type'];
    $description = $_POST['description'];
    $fileURL = $_POST['file_url'];
    $uploadDate = date('Y-m-d');
    $approvalStatus = 'Pending';

    $stmt = $conn->prepare(
        "INSERT INTO learning_resource
        (Title, ResourceType, Description, FileURL, UploadDate, ApprovalStatus)
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssssss",
        $title,
        $resourceType,
        $description,
        $fileURL,
        $uploadDate,
        $approvalStatus
    );

    $stmt->execute();
    $stmt->close();

    header("Location: learning_resources.php");
    exit();
}

//create end____________________________________________________

// DELETE - Delete learning resource
if (isset($_GET['delete'])) {
    $deleteID = (int) $_GET['delete'];

    $stmt = $conn->prepare(
        "DELETE FROM learning_resource WHERE ResourceID = ?"
    );
    $stmt->bind_param("i", $deleteID);
    $stmt->execute();
    $stmt->close();

    header("Location: learning_resources.php");
    exit();
}

//delete end____________________________________________________

// UPDATE - Edit learning resource
if (isset($_POST['update_resource'])) {

    $resourceID = $_POST['resource_id'];
    $title = $_POST['title'];
    $resourceType = $_POST['resource_type'];
    $description = $_POST['description'];
    $fileURL = $_POST['file_url'];
    $approvalStatus = $_POST['approval_status'];

    $stmt = $conn->prepare(
        "UPDATE learning_resource
         SET Title = ?,
             ResourceType = ?,
             Description = ?,
             FileURL = ?,
             ApprovalStatus = ?
         WHERE ResourceID = ?"
    );

    $stmt->bind_param(
        "sssssi",
        $title,
        $resourceType,
        $description,
        $fileURL,
        $approvalStatus,
        $resourceID
    );

    $stmt->execute();
    $stmt->close();

    header("Location: learning_resources.php");
    exit();
}
// end____________________________________________________
$editResource = null;

if (isset($_GET['edit'])) {

    $editID = (int) $_GET['edit'];

    $stmt = $conn->prepare(
        "SELECT * FROM learning_resource
         WHERE ResourceID = ?"
    );

    $stmt->bind_param("i", $editID);
    $stmt->execute();

    $editResult = $stmt->get_result();
    $editResource = $editResult->fetch_assoc();

    $stmt->close();
}
//end update____________________________________________________

// Get all learning resources
$sql = "SELECT * FROM learning_resource ORDER BY ResourceID DESC";
$result = mysqli_query($conn, $sql);

// Count resources by approval status
$pendingResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM learning_resource
     WHERE ApprovalStatus = 'Pending'"
);
$pendingCount = mysqli_fetch_assoc($pendingResult)['total'];

$approvedResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM learning_resource
     WHERE ApprovalStatus = 'Approved'"
);
$approvedCount = mysqli_fetch_assoc($approvedResult)['total'];

$rejectedResult = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM learning_resource
     WHERE ApprovalStatus = 'Rejected'"
);
$rejectedCount = mysqli_fetch_assoc($rejectedResult)['total'];

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo $projectName; ?> | Learning Resources</title>

    <link rel="stylesheet" href="../css/teacher.css">
    <link rel="stylesheet" href="../css/coordinator.css">

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

    <a href="profile.php" class="menu-link">
        <i class="fas fa-user"></i>
        Profile
    </a>

    <a href="courses.php" class="menu-link">
        <i class="fas fa-book-open"></i>
        Courses
    </a>

    <<a href="academic_schedules.php" class="menu-link">
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

    <a href="learning_resources.php" class="menu-link active">
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

                    <p class="small-label">Content Quality</p>
                    <h1>Learning Resources</h1>

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

    <section class="page-intro-card">

        <div>
            <p class="card-label">Content Quality Management</p>

            <h2>Learning Resources</h2>

            <p>
                Review and approve learning materials before publication.
            </p>
        </div>

        <div class="page-intro-icon">
            <i class="fas fa-file-alt"></i>
        </div>

    </section>


    <section class="course-summary-grid">

        <article class="mini-stat-card">

            <div class="mini-stat-icon">
                <i class="fas fa-clock"></i>
            </div>

            <div>
                <p>Pending Review</p>
                <h3><?php echo $pendingCount; ?></h3>
            </div>

        </article>


        <article class="mini-stat-card">

            <div class="mini-stat-icon">
                <i class="fas fa-circle-check"></i>
            </div>

            <div>
                <p>Approved</p>
                <h3><?php echo $approvedCount; ?></h3>
            </div>

        </article>


        <article class="mini-stat-card">

            <div class="mini-stat-icon">
                <i class="fas fa-ban"></i>
            </div>

            <div>
                <p>Rejected</p>
                <h3><?php echo $rejectedCount; ?></h3>
            </div>

        </article>

    </section>


    <section class="course-table-card">

        <div class="course-table-heading">

            <div>
                <h2>Resource Management</h2>

                <p>
                    Add, review, update, approve, reject, or delete learning resources.
                </p>
            </div>

        </div>

        <?php if ($editResource): ?>

        <h3>Edit Resource</h3>

<form method="POST" class="resource-form">

    <input type="hidden"
           name="resource_id"
           value="<?php echo $editResource['ResourceID']; ?>">

    <div class="form-group">
        <label>Title</label>

        <input type="text"
               name="title"
               value="<?php echo htmlspecialchars($editResource['Title']); ?>"
               required>
    </div>


    <div class="form-group">
        <label>Resource Type</label>

        <select name="resource_type" required>

            <option value="Recording"
                <?php if ($editResource['ResourceType'] == 'Recording') echo 'selected'; ?>>
                Recording
            </option>

            <option value="Note"
                <?php if ($editResource['ResourceType'] == 'Note') echo 'selected'; ?>>
                Note
            </option>

            <option value="Tute"
                <?php if ($editResource['ResourceType'] == 'Tute') echo 'selected'; ?>>
                Tute
            </option>

            <option value="Reference"
                <?php if ($editResource['ResourceType'] == 'Reference') echo 'selected'; ?>>
                Reference
            </option>

        </select>
    </div>


    <div class="form-group">
        <label>Description</label>

        <textarea name="description"><?php
            echo htmlspecialchars($editResource['Description'] ?? '');
        ?></textarea>
    </div>


    <div class="form-group">
        <label>File URL</label>

        <input type="text"
               name="file_url"
               value="<?php echo htmlspecialchars($editResource['FileURL'] ?? ''); ?>">
    </div>


    <div class="form-group">
        <label>Approval Status</label>

        <select name="approval_status" required>

            <option value="Pending"
                <?php if ($editResource['ApprovalStatus'] == 'Pending') echo 'selected'; ?>>
                Pending
            </option>

            <option value="Approved"
                <?php if ($editResource['ApprovalStatus'] == 'Approved') echo 'selected'; ?>>
                Approved
            </option>

            <option value="Rejected"
                <?php if ($editResource['ApprovalStatus'] == 'Rejected') echo 'selected'; ?>>
                Rejected
            </option>

        </select>
    </div>


    <button type="submit"
            name="update_resource"
            class="btn-submit">

        <i class="fas fa-save"></i>
        Update Resource

    </button>

</form>

<?php endif; ?>

        <h3>Add New Resource</h3>

        <form method="POST" class="resource-form">

    <div class="form-group">
        <label>Title</label>

        <input type="text"
               name="title"
               placeholder="Resource title"
               required>
    </div>


    <div class="form-group">
        <label>Resource Type</label>

        <select name="resource_type" required>

            <option value="">Select Type</option>
            <option value="Recording">Recording</option>
            <option value="Note">Note</option>
            <option value="Tute">Tute</option>
            <option value="Reference">Reference</option>

        </select>
    </div>


    <div class="form-group">
        <label>Description</label>

        <textarea name="description"
                  placeholder="Resource description"></textarea>
    </div>


    <div class="form-group">
        <label>File URL</label>

        <input type="text"
               name="file_url"
               placeholder="Resource file URL">
    </div>


    <button type="submit"
            name="add_resource"
            class="btn-submit">

        <i class="fas fa-plus"></i>
        Add Resource

    </button>

</form>


        <div class="table-responsive">

            <table class="coordinator-table">

                <thead>
                    <tr>
                        <th>Resource ID</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Upload Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

<?php

if (mysqli_num_rows($result) > 0) {

    while ($resource = mysqli_fetch_assoc($result)) {
?>

        <tr>

            <td>
                <?php echo $resource['ResourceID']; ?>
            </td>

            <td>
                <?php echo htmlspecialchars($resource['Title'] ?? ''); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($resource['ResourceType'] ?? ''); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($resource['UploadDate'] ?? ''); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($resource['ApprovalStatus'] ?? ''); ?>
            </td>

            <td>
    <div class="resource-actions">

        <a href="learning_resources.php?edit=<?php echo $resource['ResourceID']; ?>"
           class="action-btn edit-btn">
            <i class="fas fa-pen"></i>
            Edit
        </a>

        <a href="learning_resources.php?delete=<?php echo $resource['ResourceID']; ?>"
           class="action-btn delete-btn"
           onclick="return confirm('Are you sure you want to delete this resource?');">
            <i class="fas fa-trash"></i>
            Delete
        </a>

    </div>
</td>

        </tr>

<?php
    }

} else {
?>

        <tr>

            <td colspan="6" class="empty-state">

                <i class="fas fa-folder-open"></i>

                <p>No learning resources available.</p>

            </td>

        </tr>

<?php
}
?>

</tbody>

            </table>

        </div>

    </section>

</main>
        
</div>
</div>


</body>

</html>