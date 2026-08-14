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
$allowedBloodGroups = array('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-');

$parentStmt = $conn->prepare(
    "SELECT u.UserID, u.Name, u.Phone
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
    "SELECT s.StudentID,
            s.RegistrationNo,
            su.Name AS StudentName,
            ps.RelationshipType
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

function emergency_option_selected($current, $option)
{
    return (string) $current === (string) $option ? 'selected' : '';
}

$parentName = $parent['Name'] ?? '';
$parentPhone = trim((string) ($parent['Phone'] ?? ''));
$parentRelationship = trim((string) ($linkedStudent['RelationshipType'] ?? ''));
$parentInitials = parent_initials($parentName);
$studentName = $linkedStudent['StudentName'] ?? '';
$studentRegNo = $linkedStudent['RegistrationNo'] ?? '';
$studentId = $linkedStudent ? (int) $linkedStudent['StudentID'] : 0;
$hasEmergencyRow = false;

$form = array(
    'primaryName' => '',
    'primaryRelationship' => '',
    'primaryPhone' => '',
    'primaryAltPhone' => '',
    'secondaryName' => '',
    'secondaryRelationship' => '',
    'secondaryPhone' => '',
    'bloodGroup' => '',
    'familyDoctor' => '',
    'allergies' => '',
    'medicalConditions' => '',
);

if (isset($_POST['save_emergency']) && $linkedStudent) {
    $form['primaryName'] = trim($_POST['primaryName'] ?? '');
    $form['primaryRelationship'] = trim($_POST['primaryRelationship'] ?? '');
    $form['primaryPhone'] = trim($_POST['primaryPhone'] ?? '');
    $form['primaryAltPhone'] = trim($_POST['primaryAltPhone'] ?? '');
    $form['secondaryName'] = trim($_POST['secondaryName'] ?? '');
    $form['secondaryRelationship'] = trim($_POST['secondaryRelationship'] ?? '');
    $form['secondaryPhone'] = trim($_POST['secondaryPhone'] ?? '');
    $form['bloodGroup'] = trim($_POST['bloodGroup'] ?? '');
    $form['familyDoctor'] = trim($_POST['familyDoctor'] ?? '');
    $form['allergies'] = trim($_POST['allergies'] ?? '');
    $form['medicalConditions'] = trim($_POST['medicalConditions'] ?? '');

    if ($form['primaryName'] === '' || $form['primaryPhone'] === '') {
        $errorMessage = "Primary contact name and phone number are required.";
    } elseif (
        $form['primaryRelationship'] === ''
        || !in_array($form['primaryRelationship'], $allowedRelationships, true)
    ) {
        $errorMessage = "Please select a valid primary relationship.";
    } elseif (
        $form['secondaryRelationship'] !== ''
        && !in_array($form['secondaryRelationship'], $allowedRelationships, true)
    ) {
        $errorMessage = "Invalid secondary relationship selected.";
    } elseif (
        $form['bloodGroup'] !== ''
        && !in_array($form['bloodGroup'], $allowedBloodGroups, true)
    ) {
        $errorMessage = "Invalid blood group selected.";
    } else {
        $saveStmt = $conn->prepare(
            "INSERT INTO student_emergency
                (StudentID, PrimaryName, PrimaryRelationship, PrimaryPhone, PrimaryAltPhone,
                 SecondaryName, SecondaryRelationship, SecondaryPhone,
                 BloodGroup, FamilyDoctorContact, Allergies, MedicalConditions, UpdatedAt)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                PrimaryName = VALUES(PrimaryName),
                PrimaryRelationship = VALUES(PrimaryRelationship),
                PrimaryPhone = VALUES(PrimaryPhone),
                PrimaryAltPhone = VALUES(PrimaryAltPhone),
                SecondaryName = VALUES(SecondaryName),
                SecondaryRelationship = VALUES(SecondaryRelationship),
                SecondaryPhone = VALUES(SecondaryPhone),
                BloodGroup = VALUES(BloodGroup),
                FamilyDoctorContact = VALUES(FamilyDoctorContact),
                Allergies = VALUES(Allergies),
                MedicalConditions = VALUES(MedicalConditions),
                UpdatedAt = NOW()"
        );
        $saveStmt->bind_param(
            "isssssssssss",
            $studentId,
            $form['primaryName'],
            $form['primaryRelationship'],
            $form['primaryPhone'],
            $form['primaryAltPhone'],
            $form['secondaryName'],
            $form['secondaryRelationship'],
            $form['secondaryPhone'],
            $form['bloodGroup'],
            $form['familyDoctor'],
            $form['allergies'],
            $form['medicalConditions']
        );

        if (!$saveStmt->execute()) {
            $errorMessage = "Could not save emergency contact details. Please try again.";
            $saveStmt->close();
        } else {
            $saveStmt->close();
            $_SESSION['emergency_success'] = "Emergency contact information saved successfully.";
            header("Location: emergency-contact.php");
            exit();
        }
    }
}

if (isset($_SESSION['emergency_success'])) {
    $successMessage = $_SESSION['emergency_success'];
    unset($_SESSION['emergency_success']);
}

$emergency = null;

if ($linkedStudent) {
    $emergencyStmt = $conn->prepare(
        "SELECT PrimaryName, PrimaryRelationship, PrimaryPhone, PrimaryAltPhone,
                SecondaryName, SecondaryRelationship, SecondaryPhone,
                BloodGroup, FamilyDoctorContact, Allergies, MedicalConditions
         FROM student_emergency
         WHERE StudentID = ?
         LIMIT 1"
    );
    $emergencyStmt->bind_param("i", $studentId);
    $emergencyStmt->execute();
    $emergencyResult = $emergencyStmt->get_result();
    $emergency = $emergencyResult ? $emergencyResult->fetch_assoc() : null;
    $emergencyStmt->close();
    $hasEmergencyRow = (bool) $emergency;
}

if ($errorMessage === '') {
    $storedPrimaryName = trim((string) ($emergency['PrimaryName'] ?? ''));
    $storedPrimaryPhone = trim((string) ($emergency['PrimaryPhone'] ?? ''));
    $useParentFallback = !$emergency
        || ($storedPrimaryName === '' && $storedPrimaryPhone === '');

    if ($useParentFallback) {
        $form['primaryName'] = $parentName;
        $form['primaryRelationship'] = $parentRelationship;
        $form['primaryPhone'] = $parentPhone;
    } else {
        $form['primaryName'] = $storedPrimaryName;
        $form['primaryRelationship'] = trim((string) ($emergency['PrimaryRelationship'] ?? ''));
        $form['primaryPhone'] = $storedPrimaryPhone;
    }

    $form['primaryAltPhone'] = trim((string) ($emergency['PrimaryAltPhone'] ?? ''));
    $form['secondaryName'] = trim((string) ($emergency['SecondaryName'] ?? ''));
    $form['secondaryRelationship'] = trim((string) ($emergency['SecondaryRelationship'] ?? ''));
    $form['secondaryPhone'] = trim((string) ($emergency['SecondaryPhone'] ?? ''));
    $form['bloodGroup'] = trim((string) ($emergency['BloodGroup'] ?? ''));
    $form['familyDoctor'] = trim((string) ($emergency['FamilyDoctorContact'] ?? ''));
    $form['allergies'] = (string) ($emergency['Allergies'] ?? '');
    $form['medicalConditions'] = (string) ($emergency['MedicalConditions'] ?? '');
}

$studentLine = 'No linked student found for this parent account.';
if ($linkedStudent) {
    $studentLine = 'For: ' . $studentName;
    if ($studentRegNo !== '') {
        $studentLine .= ' • Student ID: ' . $studentRegNo;
    }
}

$statusLabel = $hasEmergencyRow ? 'Information Up to Date' : 'Not set yet';

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Emergency Contact | LEARNFLOW</title>


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
                class="nav-link"
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
                class="nav-link active"
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

                        <h2>Emergency Contact</h2>

                        <p>
                            Keep emergency contact and medical details up to date.
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

                        <?php echo htmlspecialchars($parentInitials, ENT_QUOTES, 'UTF-8'); ?>

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            <?php echo htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8'); ?>
                        </span>

                        <span class="user-role">
                            Parent
                        </span>

                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             PAGE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <?php if ($successMessage !== ''): ?>
                <div class="profile-alert profile-alert-success">
                    <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>


            <?php if ($errorMessage !== ''): ?>
                <div class="profile-alert profile-alert-error">
                    <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>


            <!-- PAGE HEADER -->

            <div class="profile-header-card">


                <div class="profile-main-info">


                    <div class="large-profile-avatar">

                        <i class="fa-solid fa-phone-volume"></i>

                    </div>


                    <div>


                        <h1>
                            Emergency Contact Information
                        </h1>


                        <p>
                            <?php echo htmlspecialchars($studentLine, ENT_QUOTES, 'UTF-8'); ?>
                        </p>


                        <?php if ($linkedStudent): ?>
                        <span class="profile-status">

                            <i class="fa-solid fa-circle"></i>

                            <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>

                        </span>
                        <?php endif; ?>


                    </div>


                </div>


                <?php if ($linkedStudent): ?>
                <button
                    type="button"
                    class="profile-edit-btn"
                    id="editEmergencyButton"
                >

                    <i class="fa-solid fa-pen"></i>

                    Edit Information

                </button>
                <?php endif; ?>


            </div>


            <?php if (!$linkedStudent): ?>

                <p>
                    No linked student found for this parent account.
                </p>

            <?php else: ?>


            <form
                id="emergencyForm"
                method="POST"
                action="emergency-contact.php"
            >


                <!-- PRIMARY EMERGENCY CONTACT -->

                <div class="profile-card">


                    <div class="profile-card-header">


                        <div>


                            <h3>
                                Primary Emergency Contact
                            </h3>


                            <p>
                                The first person the institute will contact in an emergency.
                            </p>


                        </div>


                    </div>



                    <div class="profile-form-grid">


                        <!-- FULL NAME -->

                        <div class="profile-form-group">


                            <label for="primaryName">
                                Full Name
                            </label>


                            <input
                                type="text"
                                id="primaryName"
                                name="primaryName"
                                value="<?php echo htmlspecialchars($form['primaryName'], ENT_QUOTES, 'UTF-8'); ?>"
                                required
                                disabled
                            >


                        </div>



                        <!-- RELATIONSHIP -->

                        <div class="profile-form-group">


                            <label for="primaryRelationship">
                                Relationship to Student
                            </label>


                            <select
                                id="primaryRelationship"
                                name="primaryRelationship"
                                required
                                disabled
                            >

                                <option value="" <?php echo emergency_option_selected($form['primaryRelationship'], ''); ?>>
                                    Select relationship
                                </option>

                                <?php foreach ($allowedRelationships as $option): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo emergency_option_selected($form['primaryRelationship'], $option); ?>
                                    >
                                        <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>


                        </div>



                        <!-- PHONE -->

                        <div class="profile-form-group">


                            <label for="primaryPhone">
                                Phone Number
                            </label>


                            <input
                                type="tel"
                                id="primaryPhone"
                                name="primaryPhone"
                                value="<?php echo htmlspecialchars($form['primaryPhone'], ENT_QUOTES, 'UTF-8'); ?>"
                                required
                                disabled
                            >


                        </div>



                        <!-- ALTERNATE PHONE -->

                        <div class="profile-form-group">


                            <label for="primaryAltPhone">
                                Alternate Phone Number
                            </label>


                            <input
                                type="tel"
                                id="primaryAltPhone"
                                name="primaryAltPhone"
                                value="<?php echo htmlspecialchars($form['primaryAltPhone'], ENT_QUOTES, 'UTF-8'); ?>"
                                disabled
                            >


                        </div>


                    </div>


                </div>



                <!-- SECONDARY EMERGENCY CONTACT -->

                <div class="profile-card">


                    <div class="profile-card-header">


                        <div>


                            <h3>
                                Secondary Emergency Contact
                            </h3>


                            <p>
                                Contacted if the primary contact cannot be reached.
                            </p>


                        </div>


                    </div>



                    <div class="profile-form-grid">


                        <!-- FULL NAME -->

                        <div class="profile-form-group">


                            <label for="secondaryName">
                                Full Name
                            </label>


                            <input
                                type="text"
                                id="secondaryName"
                                name="secondaryName"
                                value="<?php echo htmlspecialchars($form['secondaryName'], ENT_QUOTES, 'UTF-8'); ?>"
                                disabled
                            >


                        </div>



                        <!-- RELATIONSHIP -->

                        <div class="profile-form-group">


                            <label for="secondaryRelationship">
                                Relationship to Student
                            </label>


                            <select
                                id="secondaryRelationship"
                                name="secondaryRelationship"
                                disabled
                            >

                                <option value="" <?php echo emergency_option_selected($form['secondaryRelationship'], ''); ?>>
                                    Select relationship
                                </option>

                                <?php foreach ($allowedRelationships as $option): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo emergency_option_selected($form['secondaryRelationship'], $option); ?>
                                    >
                                        <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>


                        </div>



                        <!-- PHONE -->

                        <div class="profile-form-group full-width">


                            <label for="secondaryPhone">
                                Phone Number
                            </label>


                            <input
                                type="tel"
                                id="secondaryPhone"
                                name="secondaryPhone"
                                value="<?php echo htmlspecialchars($form['secondaryPhone'], ENT_QUOTES, 'UTF-8'); ?>"
                                disabled
                            >


                        </div>


                    </div>


                </div>



                <!-- MEDICAL INFORMATION -->

                <div class="profile-card">


                    <div class="profile-card-header">


                        <div>


                            <h3>
                                Medical Information
                            </h3>


                            <p>
                                Important medical details to share with teachers in an emergency.
                            </p>


                        </div>


                    </div>



                    <div class="profile-form-grid">


                        <!-- BLOOD GROUP -->

                        <div class="profile-form-group">


                            <label for="bloodGroup">
                                Blood Group
                            </label>


                            <select
                                id="bloodGroup"
                                name="bloodGroup"
                                disabled
                            >

                                <option value="" <?php echo emergency_option_selected($form['bloodGroup'], ''); ?>>
                                    Select blood group
                                </option>

                                <?php foreach ($allowedBloodGroups as $option): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>"
                                        <?php echo emergency_option_selected($form['bloodGroup'], $option); ?>
                                    >
                                        <?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>

                            </select>


                        </div>



                        <!-- FAMILY DOCTOR -->

                        <div class="profile-form-group">


                            <label for="familyDoctor">
                                Family Doctor / Clinic Contact
                            </label>


                            <input
                                type="tel"
                                id="familyDoctor"
                                name="familyDoctor"
                                value="<?php echo htmlspecialchars($form['familyDoctor'], ENT_QUOTES, 'UTF-8'); ?>"
                                disabled
                            >


                        </div>



                        <!-- ALLERGIES -->

                        <div class="profile-form-group full-width">


                            <label for="allergies">
                                Known Allergies
                            </label>


                            <textarea
                                id="allergies"
                                name="allergies"
                                rows="2"
                                disabled
                            ><?php echo htmlspecialchars($form['allergies'], ENT_QUOTES, 'UTF-8'); ?></textarea>


                        </div>



                        <!-- MEDICAL CONDITIONS -->

                        <div class="profile-form-group full-width">


                            <label for="medicalConditions">
                                Medical Conditions / Notes
                            </label>


                            <textarea
                                id="medicalConditions"
                                name="medicalConditions"
                                rows="3"
                                disabled
                            ><?php echo htmlspecialchars($form['medicalConditions'], ENT_QUOTES, 'UTF-8'); ?></textarea>


                        </div>


                    </div>


                </div>



                <!-- FORM ACTIONS -->

                <div
                    class="profile-form-actions"
                    id="emergencyFormActions"
                >


                    <button
                        type="button"
                        class="profile-cancel-btn"
                        id="cancelEmergencyButton"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        name="save_emergency"
                        value="1"
                        class="profile-save-btn"
                    >

                        <i class="fa-solid fa-check"></i>

                        Save Changes

                    </button>


                </div>


            </form>


            <?php endif; ?>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<?php if ($linkedStudent): ?>
<!-- Emergency Contact JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const editButton =
            document.getElementById("editEmergencyButton");


        const cancelButton =
            document.getElementById("cancelEmergencyButton");


        const formActions =
            document.getElementById("emergencyFormActions");


        const emergencyForm =
            document.getElementById("emergencyForm");


        if (!editButton || !cancelButton || !formActions || !emergencyForm) {
            return;
        }


        const editableFields =
            document.querySelectorAll(
                "#emergencyForm input, #emergencyForm select, #emergencyForm textarea"
            );


        /* -------------------------
           EDIT INFORMATION
        ------------------------- */

        editButton.addEventListener(
            "click",
            function () {


                editableFields.forEach(
                    function (field) {

                        field.disabled = false;

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

                window.location.href = "emergency-contact.php";

            }
        );



        /* -------------------------
           SAVE CHANGES
        ------------------------- */

        emergencyForm.addEventListener(
            "submit",
            function () {

                editableFields.forEach(
                    function (field) {

                        field.disabled = false;

                    }
                );

            }
        );


    }
);


</script>
<?php endif; ?>


</body>

</html>
