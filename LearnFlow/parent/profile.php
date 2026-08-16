<?php

session_start();

include "../config/db.php";

if (
    !isset($_SESSION['user_id'], $_SESSION['role'])
    || $_SESSION['role'] !== 'Parent'
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';

$ensureParent = $conn->prepare("INSERT IGNORE INTO parent (ParentID) VALUES (?)");
$ensureParent->bind_param("i", $parentId);
$ensureParent->execute();
$ensureParent->close();

$allowedRelationships = array('Father', 'Mother', 'Guardian');

if (isset($_POST['link_student'])) {
    $registrationNo = strtoupper(trim($_POST['registration_no'] ?? ''));

    $existingLinkStmt = $conn->prepare(
        "SELECT StudentID FROM parent_student WHERE ParentID = ? LIMIT 1"
    );
    $existingLinkStmt->bind_param("i", $parentId);
    $existingLinkStmt->execute();
    $existingLinkResult = $existingLinkStmt->get_result();
    $existingLink = $existingLinkResult ? $existingLinkResult->fetch_assoc() : null;
    $existingLinkStmt->close();

    if ($existingLink) {
        $errorMessage = "A student is already linked to your account.";
    } elseif ($registrationNo === '') {
        $errorMessage = "Please enter a student ID.";
    } else {
        $studentLookup = $conn->prepare(
            "SELECT s.StudentID
             FROM student s
             INNER JOIN users u ON u.UserID = s.StudentID
             WHERE s.RegistrationNo = ? AND u.Role = 'Student'
             LIMIT 1"
        );
        $studentLookup->bind_param("s", $registrationNo);
        $studentLookup->execute();
        $studentLookupResult = $studentLookup->get_result();
        $studentRow = $studentLookupResult ? $studentLookupResult->fetch_assoc() : null;
        $studentLookup->close();

        if (!$studentRow) {
            $errorMessage = "No student found with that ID.";
        } else {
            $studentIdToLink = (int) $studentRow['StudentID'];

            $alreadyLinkedStmt = $conn->prepare(
                "SELECT ParentID FROM parent_student WHERE StudentID = ? LIMIT 1"
            );
            $alreadyLinkedStmt->bind_param("i", $studentIdToLink);
            $alreadyLinkedStmt->execute();
            $alreadyLinkedResult = $alreadyLinkedStmt->get_result();
            $alreadyLinked = $alreadyLinkedResult ? $alreadyLinkedResult->fetch_assoc() : null;
            $alreadyLinkedStmt->close();

            if ($alreadyLinked) {
                $errorMessage = "This student is already linked to a parent.";
            } else {
                $insertLink = $conn->prepare(
                    "INSERT INTO parent_student (ParentID, StudentID, RelationshipType)
                     VALUES (?, ?, NULL)"
                );
                $insertLink->bind_param("ii", $parentId, $studentIdToLink);

                if ($insertLink->execute()) {
                    $insertLink->close();
                    $_SESSION['profile_success'] = "Student linked successfully.";
                    header("Location: profile.php");
                    exit();
                }

                $insertLink->close();
                $errorMessage = "Could not link student. Please try again.";
            }
        }
    }
}

if (isset($_POST['save_profile'])) {
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $nic = trim($_POST['nic'] ?? '');
    $relationship = trim($_POST['relationship'] ?? '');

    if ($fullName === '' || $email === '') {
        $errorMessage = "Full name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } elseif ($relationship !== '' && !in_array($relationship, $allowedRelationships, true)) {
        $errorMessage = "Invalid relationship selected.";
    } else {
        $emailCheck = $conn->prepare(
            "SELECT UserID FROM users WHERE Email = ? AND UserID <> ?"
        );
        $emailCheck->bind_param("si", $email, $parentId);
        $emailCheck->execute();
        $emailResult = $emailCheck->get_result();

        if ($emailResult && $emailResult->num_rows > 0) {
            $errorMessage = "That email is already in use by another account.";
            $emailCheck->close();
        } else {
            $emailCheck->close();

            $updateUser = $conn->prepare(
                "UPDATE users
                 SET Name = ?, Email = ?, Phone = ?, Address = ?
                 WHERE UserID = ? AND Role = 'Parent'"
            );
            $updateUser->bind_param(
                "ssssi",
                $fullName,
                $email,
                $phone,
                $address,
                $parentId
            );

            $updateNic = $conn->prepare(
                "UPDATE parent SET NIC = ? WHERE ParentID = ?"
            );
            $updateNic->bind_param("si", $nic, $parentId);

            $userOk = $updateUser->execute();
            $nicOk = $updateNic->execute();
            $updateUser->close();
            $updateNic->close();

            if (!$userOk || !$nicOk) {
                $errorMessage = "Could not save profile changes. Please try again.";
            } else {
                $linkStmt = $conn->prepare(
                    "SELECT StudentID
                     FROM parent_student
                     WHERE ParentID = ?
                     LIMIT 1"
                );
                $linkStmt->bind_param("i", $parentId);
                $linkStmt->execute();
                $linkResult = $linkStmt->get_result();
                $linkRow = $linkResult ? $linkResult->fetch_assoc() : null;
                $linkStmt->close();

                if ($linkRow && $relationship !== '') {
                    $studentId = (int) $linkRow['StudentID'];
                    $updateRel = $conn->prepare(
                        "UPDATE parent_student
                         SET RelationshipType = ?
                         WHERE ParentID = ? AND StudentID = ?"
                    );
                    $updateRel->bind_param("sii", $relationship, $parentId, $studentId);
                    $updateRel->execute();
                    $updateRel->close();
                }

                $_SESSION['name'] = $fullName;
                $_SESSION['profile_success'] = "Profile changes saved successfully.";
                header("Location: profile.php");
                exit();
            }
        }
    }
}

if (isset($_SESSION['profile_success'])) {
    $successMessage = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}

$parentStmt = $conn->prepare(
    "SELECT u.UserID, u.Name, u.Email, u.Phone, u.Address, u.Status, p.NIC
     FROM users u
     INNER JOIN parent p ON p.ParentID = u.UserID
     WHERE u.UserID = ? AND u.Role = 'Parent'
     LIMIT 1"
);
$parentStmt->bind_param("i", $parentId);
$parentStmt->execute();
$parentResult = $parentStmt->get_result();
$parent = $parentResult ? $parentResult->fetch_assoc() : null;
$parentStmt->close();

if (!$parent) {
    header("Location: ../auth/login.php");
    exit();
}

$studentStmt = $conn->prepare(
    "SELECT ps.StudentID,
            ps.RelationshipType,
            s.RegistrationNo,
            su.Name AS StudentName
     FROM parent_student ps
     INNER JOIN student s ON s.StudentID = ps.StudentID
     INNER JOIN users su ON su.UserID = s.StudentID
     WHERE ps.ParentID = ?
     LIMIT 1"
);
$studentStmt->bind_param("i", $parentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$linkedStudent = $studentResult ? $studentResult->fetch_assoc() : null;
$studentStmt->close();

$program = '';
$stream = '';

if ($linkedStudent) {
    $linkedStudentId = (int) $linkedStudent['StudentID'];
    $courseStmt = $conn->prepare(
        "SELECT c.CourseName, c.Stream
         FROM enrollment e
         INNER JOIN batch b ON b.BatchID = e.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         WHERE e.StudentID = ?
         ORDER BY e.EnrollmentID
         LIMIT 1"
    );
    $courseStmt->bind_param("i", $linkedStudentId);
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    $courseRow = $courseResult ? $courseResult->fetch_assoc() : null;
    $courseStmt->close();

    if ($courseRow) {
        $program = (string) ($courseRow['CourseName'] ?? '');
        $stream = (string) ($courseRow['Stream'] ?? '');
    }
}

function parent_initials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    if (!empty($parts[0])) {
        $initials .= strtoupper(substr($parts[0], 0, 1));
    }

    if (count($parts) > 1 && !empty($parts[count($parts) - 1])) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }

    return $initials !== '' ? $initials : 'P';
}

$parentName = $parent['Name'] ?? '';
$parentEmail = $parent['Email'] ?? '';
$parentPhone = $parent['Phone'] ?? '';
$parentAddress = $parent['Address'] ?? '';
$parentNic = $parent['NIC'] ?? '';
$parentStatus = trim((string) ($parent['Status'] ?? ''));
$statusLabel = $parentStatus !== '' ? $parentStatus : 'Active';
$relationship = ($linkedStudent ?? [])['RelationshipType'] ?? '';
$studentName = ($linkedStudent ?? [])['StudentName'] ?? '';
$studentRegNo = ($linkedStudent ?? [])['RegistrationNo'] ?? '';
$initials = parent_initials($parentName);

if ($errorMessage !== '' && isset($_POST['save_profile'])) {
    $parentName = trim($_POST['fullName'] ?? $parentName);
    $parentEmail = trim($_POST['email'] ?? $parentEmail);
    $parentPhone = trim($_POST['phone'] ?? $parentPhone);
    $parentAddress = trim($_POST['address'] ?? $parentAddress);
    $parentNic = trim($_POST['nic'] ?? $parentNic);
    $relationship = trim($_POST['relationship'] ?? $relationship);
    $initials = parent_initials($parentName);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Profile | LEARNFLOW</title>


    <!-- Google Font -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <!-- Parent CSS -->

    <link
        rel="stylesheet"
        href="../css/parent.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >


        <!-- Brand -->

        <div class="sidebar-brand">

            <i class="fa-solid fa-graduation-cap"></i>

            <span>LEARNFLOW</span>

        </div>


        <!-- Navigation -->

        <nav class="sidebar-nav">


            <!-- Main -->

            <div class="nav-section-title">
                Main
            </div>


            <a
                href="dashboard.php"
                class="nav-link"
            >

                <i class="fa-solid fa-house"></i>

                <span>Dashboard</span>

            </a>


            <a
                href="profile.php"
                class="nav-link active"
            >

                <i class="fa-solid fa-user"></i>

                <span>My Profile</span>

            </a>


            <!-- Student Monitoring -->

            <div class="nav-section-title">
                Student Monitoring
            </div>


            <a
                href="attendance.php"
                class="nav-link"
            >

                <i class="fa-solid fa-calendar-check"></i>

                <span>Attendance</span>

            </a>


            <a
                href="progress.php"
                class="nav-link"
            >

                <i class="fa-solid fa-chart-line"></i>

                <span>Academic Progress</span>

            </a>


            <a
                href="assignments.php"
                class="nav-link"
            >

                <i class="fa-solid fa-file-pen"></i>

                <span>Assignments</span>

            </a>


            <a
                href="results.php"
                class="nav-link"
            >

                <i class="fa-solid fa-award"></i>

                <span>Examination Results</span>

            </a>


            <a
                href="courses.php"
                class="nav-link"
            >

                <i class="fa-solid fa-book-open"></i>

                <span>Course Participation</span>

            </a>


            <!-- Communication -->

            <div class="nav-section-title">
                Communication
            </div>


            <a
                href="announcements.php"
                class="nav-link"
            >

                <i class="fa-solid fa-bullhorn"></i>

                <span>Announcements</span>

            </a>


            <a
                href="contact.php"
                class="nav-link"
            >

                <i class="fa-solid fa-comments"></i>

                <span>Contact Teachers/Admins</span>

            </a>


            <a
                href="meetings.php"
                class="nav-link"
            >

                <i class="fa-solid fa-handshake"></i>

                <span>Request Meeting</span>

            </a>


            <!-- Payments -->

            <div class="nav-section-title">
                Payments
            </div>


            <a
                href="payments.php"
                class="nav-link"
            >

                <i class="fa-solid fa-credit-card"></i>

                <span>Payment Status</span>

            </a>


            <a
                href="receipts.php"
                class="nav-link"
            >

                <i class="fa-solid fa-receipt"></i>

                <span>Payment Receipts</span>

            </a>


            <!-- Settings -->

            <div class="nav-section-title">
                Settings
            </div>


            <a
                href="emergency-contact.php"
                class="nav-link"
            >

                <i class="fa-solid fa-phone-volume"></i>

                <span>Emergency Contact</span>

            </a>


        </nav>


        <!-- Sidebar Footer -->

        <div class="sidebar-footer">

            <a
                href="../auth/logout.php"
                class="nav-link"
            >

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

            </a>

        </div>


    </aside>



    <!-- =================================================
         MAIN AREA
    ================================================== -->

    <main class="main-area">


        <!-- TOP NAVBAR -->

        <header class="top-navbar">


            <div class="page-title">


                <div class="profile-top-title">


                    <button
                        class="menu-toggle"
                        id="menuToggle"
                    >

                        <i class="fa-solid fa-bars"></i>

                    </button>


                    <div>

                        <h2>My Profile</h2>

                        <p>
                            Manage your personal information.
                        </p>

                    </div>


                </div>


            </div>



            <!-- User Area -->

            <div class="user-area">


                <button
                    class="notification-btn"
                    id="notificationButton"
                >

                    <i class="fa-regular fa-bell"></i>

                    <span class="notification-badge"></span>

                </button>


                <div class="user-profile">


                    <div class="user-avatar">

                        <?php echo htmlspecialchars($initials); ?>

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            <?php echo htmlspecialchars($parentName); ?>
                        </span>

                        <span class="user-role">
                            Parent
                        </span>

                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             PROFILE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <?php if ($successMessage !== ''): ?>
                <div class="profile-alert profile-alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>


            <?php if ($errorMessage !== ''): ?>
                <div class="profile-alert profile-alert-error">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>


            <!-- PROFILE HEADER -->

            <div class="profile-header-card">


                <div class="profile-main-info">


                    <div class="large-profile-avatar">

                        <?php echo htmlspecialchars($initials); ?>

                    </div>


                    <div>


                        <h1>
                            <?php echo htmlspecialchars($parentName); ?>
                        </h1>


                        <p>
                            Parent ID: <?php echo (int) $parentId; ?>
                        </p>


                        <span class="profile-status">

                            <i class="fa-solid fa-circle"></i>

                            <?php echo htmlspecialchars($statusLabel); ?>

                        </span>


                    </div>


                </div>


                <button
                    type="button"
                    class="profile-edit-btn"
                    id="editProfileButton"
                >

                    <i class="fa-solid fa-pen"></i>

                    Edit Profile

                </button>


            </div>



            <!-- PERSONAL INFORMATION -->

            <div class="profile-card">


                <div class="profile-card-header">


                    <div>


                        <h3>
                            Personal Information
                        </h3>


                        <p>
                            Your basic personal details and contact information.
                        </p>


                    </div>


                </div>



                <form
                    id="profileForm"
                    method="POST"
                    action="profile.php"
                >


                    <div class="profile-form-grid">


                        <!-- Full Name -->

                        <div class="profile-form-group">


                            <label for="fullName">
                                Full Name
                            </label>


                            <input
                                type="text"
                                id="fullName"
                                name="fullName"
                                value="<?php echo htmlspecialchars($parentName); ?>"
                                required
                                disabled
                            >


                        </div>



                        <!-- Parent ID -->

                        <div class="profile-form-group">


                            <label for="parentId">
                                Parent ID
                            </label>


                            <input
                                type="text"
                                id="parentId"
                                value="<?php echo (int) $parentId; ?>"
                                disabled
                            >


                        </div>



                        <!-- Email -->

                        <div class="profile-form-group">


                            <label for="email">
                                Email Address
                            </label>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($parentEmail); ?>"
                                required
                                disabled
                            >


                        </div>



                        <!-- Phone -->

                        <div class="profile-form-group">


                            <label for="phone">
                                Phone Number
                            </label>


                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                value="<?php echo htmlspecialchars($parentPhone); ?>"
                                disabled
                            >


                        </div>



                        <!-- Relationship to Student -->

                        <div class="profile-form-group">


                            <label for="relationship">
                                Relationship to Student
                            </label>


                            <select
                                id="relationship"
                                name="relationship"
                                disabled
                            >

                                <option value="" <?php echo $relationship === '' ? 'selected' : ''; ?>>
                                    Select relationship
                                </option>

                                <?php foreach ($allowedRelationships as $option): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($option); ?>"
                                        <?php echo $relationship === $option ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($option); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>


                        </div>



                        <!-- NIC Number -->

                        <div class="profile-form-group">


                            <label for="nic">
                                NIC Number
                            </label>


                            <input
                                type="text"
                                id="nic"
                                name="nic"
                                value="<?php echo htmlspecialchars($parentNic); ?>"
                                disabled
                            >


                        </div>



                        <!-- Address -->

                        <div class="profile-form-group full-width">


                            <label for="address">
                                Address
                            </label>


                            <textarea
                                id="address"
                                name="address"
                                rows="3"
                                disabled
                            ><?php echo htmlspecialchars($parentAddress); ?></textarea>


                        </div>


                    </div>



                    <!-- FORM ACTIONS -->

                    <div
                        class="profile-form-actions"
                        id="profileFormActions"
                    >


                        <button
                            type="button"
                            class="profile-cancel-btn"
                            id="cancelEditButton"
                        >

                            Cancel

                        </button>


                        <button
                            type="submit"
                            name="save_profile"
                            value="1"
                            class="profile-save-btn"
                        >

                            <i class="fa-solid fa-check"></i>

                            Save Changes

                        </button>


                    </div>


                </form>


            </div>



            <!-- LINKED STUDENT / LINK YOUR STUDENT -->

            <div class="profile-card">


                <div class="profile-card-header">


                    <div>


                        <?php if ($linkedStudent): ?>

                            <h3>
                                Linked Student Information
                            </h3>


                            <p>
                                Details of the student linked to your parent account.
                            </p>

                        <?php else: ?>

                            <h3>
                                Link your student
                            </h3>


                            <p>
                                Enter the student ID (for example STU1234) to link your child.
                            </p>

                        <?php endif; ?>


                    </div>


                </div>


                <?php if ($linkedStudent): ?>

                    <div class="academic-info-grid">


                        <div class="academic-info-item">


                            <span class="academic-label">
                                Student Name
                            </span>


                            <strong>
                                <?php echo htmlspecialchars($studentName); ?>
                            </strong>


                        </div>



                        <div class="academic-info-item">


                            <span class="academic-label">
                                Student ID
                            </span>


                            <strong>
                                <?php echo htmlspecialchars($studentRegNo); ?>
                            </strong>


                        </div>



                        <div class="academic-info-item">


                            <span class="academic-label">
                                Program
                            </span>


                            <strong>
                                <?php echo htmlspecialchars($program); ?>
                            </strong>


                        </div>



                        <div class="academic-info-item">


                            <span class="academic-label">
                                Stream
                            </span>


                            <strong>
                                <?php echo htmlspecialchars($stream); ?>
                            </strong>


                        </div>


                    </div>

                <?php else: ?>

                    <form method="POST" action="">

                        <div class="profile-form-grid">

                            <div class="profile-form-group">

                                <label for="registration_no">
                                    Student ID
                                </label>

                                <input
                                    type="text"
                                    id="registration_no"
                                    name="registration_no"
                                    placeholder="STU1234"
                                    value="<?php echo isset($_POST['link_student']) ? htmlspecialchars(strtoupper(trim($_POST['registration_no'] ?? ''))) : ''; ?>"
                                    required
                                >

                            </div>

                        </div>

                        <div class="profile-form-actions" style="display: flex;">

                            <button
                                type="submit"
                                name="link_student"
                                value="1"
                                class="profile-save-btn"
                            >

                                <i class="fa-solid fa-link"></i>

                                Link Student

                            </button>

                        </div>

                    </form>

                <?php endif; ?>


            </div>



        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Profile JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const editButton =
            document.getElementById("editProfileButton");


        const cancelButton =
            document.getElementById("cancelEditButton");


        const formActions =
            document.getElementById("profileFormActions");


        const profileForm =
            document.getElementById("profileForm");


        const editableFields =
            document.querySelectorAll(
                "#profileForm input, #profileForm select, #profileForm textarea"
            );


        const namedFields =
            profileForm.querySelectorAll(
                "input[name], select[name], textarea[name]"
            );


        const originalValues = {};


        namedFields.forEach(
            function (field) {

                originalValues[field.name] = field.value;

            }
        );


        function profileHasChanges() {

            for (let i = 0; i < namedFields.length; i++) {

                const field = namedFields[i];

                if (field.value !== originalValues[field.name]) {

                    return true;

                }

            }

            return false;

        }


        /* -------------------------
           EDIT PROFILE
        ------------------------- */

        editButton.addEventListener(
            "click",
            function () {


                editableFields.forEach(
                    function (field) {

                        /*
                         Parent ID is not editable
                         because it identifies the account.
                        */

                        if (field.id !== "parentId") {

                            field.disabled = false;

                        }

                    }
                );


                formActions.classList.add("show");


                editButton.style.display = "none";


            }
        );



        /* -------------------------
           CANCEL EDIT
        ------------------------- */

        cancelButton.addEventListener(
            "click",
            function () {

                window.location.href = "profile.php";

            }
        );



        /* -------------------------
           SAVE PROFILE
        ------------------------- */

        profileForm.addEventListener(
            "submit",
            function (event) {

                if (!profileHasChanges()) {

                    event.preventDefault();

                    editableFields.forEach(
                        function (field) {

                            field.disabled = true;

                        }
                    );

                    formActions.classList.remove("show");

                    editButton.style.display = "inline-flex";

                    return;

                }

                editableFields.forEach(
                    function (field) {

                        if (field.id !== "parentId") {

                            field.disabled = false;

                        }

                    }
                );

            }
        );


    }
);


</script>


</body>

</html>
