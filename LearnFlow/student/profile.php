<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';

$success = $_SESSION['profile_success'] ?? '';
unset($_SESSION['profile_success']);


/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
| Only logged-in students are allowed to access this page.
*/

if (
    !isset($_SESSION['user_id']) ||
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| FUNCTION: LOAD STUDENT PROFILE
|--------------------------------------------------------------------------
*/

function loadStudentProfile(mysqli $conn, int $userId): ?array
{
    $sql = "
        SELECT
            u.user_id,
            u.fullname,
            u.email,
            u.phone,
            u.address,
            u.status,

            s.registration_no,
            s.nic,
            s.date_of_birth,
            s.gender,

            (
                SELECT MIN(e.enrollment_date)
                FROM enrollments e
                WHERE e.student_id = s.student_id
            ) AS enrollment_date

        FROM user_accounts u

        INNER JOIN students s
            ON s.student_id = u.user_id

        WHERE
            u.user_id = ?
            AND u.role = 'Student'

        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "i",
        $userId
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $profile = $result->fetch_assoc();

    $stmt->close();

    return $profile ?: null;
}


/*
|--------------------------------------------------------------------------
| LOAD CURRENT STUDENT
|--------------------------------------------------------------------------
*/

$profile = loadStudentProfile(
    $conn,
    $userId
);


/*
|--------------------------------------------------------------------------
| STUDENT RECORD NOT FOUND
|--------------------------------------------------------------------------
*/

if (!$profile) {

    session_unset();
    session_destroy();

    header("Location: ../auth/login.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| UPDATE PROFILE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile'])
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $csrfToken
        )
    ) {

        $error =
            "Invalid request. Please refresh the page and try again.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | GET FORM VALUES
        |--------------------------------------------------------------------------
        */

        $fullname =
            trim($_POST['fullname'] ?? '');

        $email =
            trim($_POST['email'] ?? '');

        $phone =
            trim($_POST['phone'] ?? '');

        $address =
            trim($_POST['address'] ?? '');

        $nic =
            trim($_POST['nic'] ?? '');

        $dateOfBirth =
            trim($_POST['date_of_birth'] ?? '');

        $gender =
            trim($_POST['gender'] ?? '');


        /*
        |--------------------------------------------------------------------------
        | VALID GENDER VALUES
        |--------------------------------------------------------------------------
        */

        $allowedGenders = [
            '',
            'male',
            'female',
            'other',
            'prefer_not_to_say'
        ];


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if ($fullname === '') {

            $error =
                "Full name is required.";

        } elseif (strlen($fullname) > 150) {

            $error =
                "Full name is too long.";

        } elseif (
            !filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                "Please enter a valid email address.";

        } elseif (strlen($email) > 190) {

            $error =
                "Email address is too long.";

        } elseif (strlen($phone) > 30) {

            $error =
                "Phone number is too long.";

        } elseif (strlen($address) > 255) {

            $error =
                "Address is too long.";

        } elseif (strlen($nic) > 30) {

            $error =
                "NIC is too long.";

        } elseif (
            !in_array(
                $gender,
                $allowedGenders,
                true
            )
        ) {

            $error =
                "Please select a valid gender.";

        } elseif (
            $dateOfBirth !== '' &&
            strtotime($dateOfBirth) === false
        ) {

            $error =
                "Please enter a valid date of birth.";

        } elseif (
            $dateOfBirth !== '' &&
            $dateOfBirth > date('Y-m-d')
        ) {

            $error =
                "Date of birth cannot be in the future.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE EMAIL
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT user_id
                FROM user_accounts
                WHERE
                    email = ?
                    AND user_id <> ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "si",
                $email,
                $userId
            );

            $stmt->execute();

            $emailExists =
                $stmt
                ->get_result()
                ->num_rows > 0;

            $stmt->close();


            if ($emailExists) {

                $error =
                    "That email address is already used by another account.";

            } else {

                /*
                |--------------------------------------------------------------------------
                | CHECK DUPLICATE NIC
                |--------------------------------------------------------------------------
                */

                $nicExists = false;

                if ($nic !== '') {

                    $stmt = $conn->prepare("
                        SELECT student_id
                        FROM students
                        WHERE
                            nic = ?
                            AND student_id <> ?
                        LIMIT 1
                    ");

                    $stmt->bind_param(
                        "si",
                        $nic,
                        $userId
                    );

                    $stmt->execute();

                    $nicExists =
                        $stmt
                        ->get_result()
                        ->num_rows > 0;

                    $stmt->close();
                }


                if ($nicExists) {

                    $error =
                        "That NIC is already registered to another student.";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | CONVERT BLANK OPTIONAL VALUES TO NULL
                    |--------------------------------------------------------------------------
                    */

                    $phoneValue =
                        $phone !== ''
                        ? $phone
                        : null;

                    $addressValue =
                        $address !== ''
                        ? $address
                        : null;

                    $nicValue =
                        $nic !== ''
                        ? $nic
                        : null;

                    $dobValue =
                        $dateOfBirth !== ''
                        ? $dateOfBirth
                        : null;

                    $genderValue =
                        $gender !== ''
                        ? $gender
                        : null;


                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE TRANSACTION
                    |--------------------------------------------------------------------------
                    */

                    try {

                        $conn->begin_transaction();


                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE USER ACCOUNT
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $conn->prepare("
                            UPDATE user_accounts

                            SET
                                fullname = ?,
                                email = ?,
                                phone = ?,
                                address = ?

                            WHERE
                                user_id = ?
                                AND role = 'Student'
                        ");

                        $stmt->bind_param(
                            "ssssi",
                            $fullname,
                            $email,
                            $phoneValue,
                            $addressValue,
                            $userId
                        );

                        $stmt->execute();

                        $stmt->close();


                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE STUDENT DETAILS
                        |--------------------------------------------------------------------------
                        */

                        $stmt = $conn->prepare("
                            UPDATE students

                            SET
                                nic = ?,
                                date_of_birth = ?,
                                gender = ?

                            WHERE student_id = ?
                        ");

                        $stmt->bind_param(
                            "sssi",
                            $nicValue,
                            $dobValue,
                            $genderValue,
                            $userId
                        );

                        $stmt->execute();

                        $stmt->close();


                        /*
                        |--------------------------------------------------------------------------
                        | SAVE TRANSACTION
                        |--------------------------------------------------------------------------
                        */

                        $conn->commit();


                        /*
                        |--------------------------------------------------------------------------
                        | UPDATE SESSION NAME
                        |--------------------------------------------------------------------------
                        */

                        $_SESSION['name'] =
                            $fullname;


                        /*
                        |--------------------------------------------------------------------------
                        | SUCCESS MESSAGE
                        |--------------------------------------------------------------------------
                        */

                        $_SESSION['profile_success'] =
                            "Profile updated successfully.";


                        /*
                        |--------------------------------------------------------------------------
                        | REDIRECT
                        |--------------------------------------------------------------------------
                        */

                        header(
                            "Location: profile.php"
                        );

                        exit();

                    } catch (Throwable $e) {

                        $conn->rollback();

                        $error =
                            "Profile update failed. Please try again.";
                    }
                }
            }
        }


        /*
        |--------------------------------------------------------------------------
        | KEEP ENTERED VALUES WHEN VALIDATION FAILS
        |--------------------------------------------------------------------------
        */

        if ($error !== '') {

            $profile['fullname'] =
                $fullname;

            $profile['email'] =
                $email;

            $profile['phone'] =
                $phone;

            $profile['address'] =
                $address;

            $profile['nic'] =
                $nic;

            $profile['date_of_birth'] =
                $dateOfBirth;

            $profile['gender'] =
                $gender;
        }
    }
}


/*
|--------------------------------------------------------------------------
| PROFILE VARIABLES
|--------------------------------------------------------------------------
*/

$studentName =
    $profile['fullname'] ?? '';

$registrationNo =
    $profile['registration_no'] ?? '';

$email =
    $profile['email'] ?? '';

$phone =
    $profile['phone'] ?? '';

$address =
    $profile['address'] ?? '';

$nic =
    $profile['nic'] ?? '';

$dateOfBirth =
    $profile['date_of_birth'] ?? '';

$gender =
    $profile['gender'] ?? '';

$enrollmentDate =
    $profile['enrollment_date'] ?? '';

$accountStatus =
    ucfirst(
        $profile['status'] ?? 'active'
    );

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">


    <title>
        <?php
        echo htmlspecialchars($projectName);
        ?>
        | Student Profile
    </title>


    <link rel="stylesheet"
          href="../css/student.css">


    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous"
          referrerpolicy="no-referrer">

</head>


<body>


<div class="dashboard-shell">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">


        <div class="brand-panel">


            <div class="brand-icon">

                <i class="fas fa-graduation-cap"></i>

            </div>


            <div>

                <p class="brand-label">
                    LearnFlow
                </p>

                <p class="brand-subtitle">
                    Student Portal
                </p>

            </div>


        </div>



        <nav class="sidebar-menu">


            <a href="dashboard.php"
               class="menu-link">

                <i class="fas fa-tachometer-alt"></i>

                Dashboard

            </a>


            <a href="profile.php"
               class="menu-link active">

                <i class="fas fa-user"></i>

                Profile

            </a>


            <a href="courses.php"
               class="menu-link">

                <i class="fas fa-book-open"></i>

                Courses

            </a>


            <a href="materials.php"
               class="menu-link">

                <i class="fas fa-folder-open"></i>

                Materials

            </a>


            <a href="assignments.php"
               class="menu-link">

                <i class="fas fa-file-alt"></i>

                Assignments

            </a>


            <a href="quizzes.php"
               class="menu-link">

                <i class="fas fa-check-square"></i>

                Quizzes

            </a>


            <a href="exams.php"
               class="menu-link">

                <i class="fas fa-clipboard-list"></i>

                Exams

            </a>


            <a href="grades.php"
               class="menu-link">

                <i class="fas fa-chart-column"></i>

                Grades

            </a>


            <a href="progress.php"
               class="menu-link">

                <i class="fas fa-chart-line"></i>

                Progress

            </a>


            <a href="attendance.php"
               class="menu-link">

                <i class="fas fa-calendar-check"></i>

                Attendance

            </a>


            <a href="schedule.php"
               class="menu-link">

                <i class="fas fa-calendar-days"></i>

                Schedule

            </a>


            <a href="announcements.php"
               class="menu-link">

                <i class="fas fa-bullhorn"></i>

                Announcements

            </a>


            <a href="discussions.php"
               class="menu-link">

                <i class="fas fa-comments"></i>

                Discussions

            </a>


            <a href="tute_store.php"
               class="menu-link">

                <i class="fas fa-store"></i>

                Tute Store

            </a>


            <a href="payments.php"
               class="menu-link">

                <i class="fas fa-credit-card"></i>

                Payments

            </a>


            <a href="notifications.php"
               class="menu-link">

                <i class="fas fa-bell"></i>

                Notifications

            </a>


            <a href="../auth/logout.php"
               class="menu-link logout-link">

                <i class="fas fa-sign-out-alt"></i>

                Logout

            </a>


        </nav>


    </aside>



    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <div class="content-area">


        <!-- =================================================
             TOPBAR
             ================================================= -->

        <header class="topbar">


            <div class="topbar-left">


                <button class="mobile-menu-btn"
                        type="button">

                    <i class="fas fa-bars"></i>

                </button>


                <div class="dashboard-title">

                    <p class="small-label">
                        Profile
                    </p>

                    <h1>
                        My Profile
                    </h1>

                </div>


            </div>



            <div class="topbar-right">


                <div class="project-pill">
                    LearnFlow
                </div>


                <a href="notifications.php"
                   class="icon-btn">

                    <i class="fas fa-bell"></i>

                </a>


                <div class="profile-chip">


                    <div class="avatar-placeholder">

                        <i class="fas fa-user-circle"></i>

                    </div>


                    <div>

                        <span>
                            Hello,
                        </span>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $studentName
                            );
                            ?>

                        </strong>

                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             PROFILE CONTENT
             ================================================= -->

        <main class="dashboard-main">



            <!-- =================================================
                 PROFILE SUMMARY
                 ================================================= -->

            <section class="overview-cards">


                <article class="summary-card">


                    <div>


                        <p class="card-label">
                            Student Profile
                        </p>


                        <h2>

                            <?php
                            echo htmlspecialchars(
                                $studentName
                            );
                            ?>

                        </h2>


                    </div>



                    <div class="summary-icon">

                        <i class="fas fa-user-graduate"></i>

                    </div>


                </article>



                <article class="stat-card">


                    <div>


                        <p class="card-label">

                            Registration No.

                        </p>


                        <h3 class="profile-stat-value">

                            <?php

                            echo $registrationNo !== ''
                                ? htmlspecialchars(
                                    $registrationNo
                                )
                                : '-';

                            ?>

                        </h3>


                    </div>


                    <span class="stat-badge">

                        Student ID

                    </span>


                </article>



                <article class="stat-card">


                    <div>


                        <p class="card-label">

                            Account Status

                        </p>


                        <h3 class="profile-stat-value">

                            <?php
                            echo htmlspecialchars(
                                $accountStatus
                            );
                            ?>

                        </h3>


                    </div>


                    <span class="stat-badge">

                        Student Account

                    </span>


                </article>


            </section>



            <!-- =================================================
                 PROFILE FORM
                 ================================================= -->

            <section class="profile-section">


                <div class="profile-section-header">


                    <div>


                        <p class="card-label">

                            Personal Information

                        </p>


                        <h2>

                            Profile Details

                        </h2>


                        <p>

                            View and update your personal information.

                        </p>


                    </div>


                </div>



                <!-- SUCCESS MESSAGE -->

                <?php if ($success !== ''): ?>


                    <div style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#ecfdf5;
                        color:#166534;
                        border:1px solid #bbf7d0;
                    ">


                        <i class="fas fa-circle-check"></i>


                        <?php
                        echo htmlspecialchars(
                            $success
                        );
                        ?>


                    </div>


                <?php endif; ?>



                <!-- ERROR MESSAGE -->

                <?php if ($error !== ''): ?>


                    <div style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#fef2f2;
                        color:#991b1b;
                        border:1px solid #fecaca;
                    ">


                        <i class="fas fa-circle-exclamation"></i>


                        <?php
                        echo htmlspecialchars(
                            $error
                        );
                        ?>


                    </div>


                <?php endif; ?>



                <form method="POST"
                      action="profile.php"
                      class="profile-form">


                    <input type="hidden"
                           name="csrf_token"
                           value="<?php
                           echo htmlspecialchars(
                               $_SESSION['csrf_token']
                           );
                           ?>">



                    <div class="form-grid">



                        <!-- FULL NAME -->

                        <div class="form-group">


                            <label for="fullname">

                                Full Name

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-user"></i>


                                <input type="text"
                                       id="fullname"
                                       name="fullname"
                                       maxlength="150"
                                       value="<?php
                                       echo htmlspecialchars(
                                           $studentName
                                       );
                                       ?>"
                                       required>


                            </div>


                        </div>



                        <!-- REGISTRATION NUMBER -->

                        <div class="form-group">


                            <label for="registrationNo">

                                Registration Number

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-id-card"></i>


                                <input type="text"
                                       id="registrationNo"
                                       value="<?php
                                       echo htmlspecialchars(
                                           $registrationNo
                                       );
                                       ?>"
                                       readonly>


                            </div>


                        </div>



                        <!-- EMAIL -->

                        <div class="form-group">


                            <label for="email">

                                Email Address

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-envelope"></i>


                                <input type="email"
                                       id="email"
                                       name="email"
                                       maxlength="190"
                                       value="<?php
                                       echo htmlspecialchars(
                                           $email
                                       );
                                       ?>"
                                       placeholder="Enter email address"
                                       required>


                            </div>


                        </div>



                        <!-- PHONE -->

                        <div class="form-group">


                            <label for="phone">

                                Phone Number

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-phone"></i>


                                <input type="tel"
                                       id="phone"
                                       name="phone"
                                       maxlength="30"
                                       value="<?php
                                       echo htmlspecialchars(
                                           $phone
                                       );
                                       ?>"
                                       placeholder="Enter phone number">


                            </div>


                        </div>



                        <!-- NIC -->

                        <div class="form-group">


                            <label for="nic">

                                NIC

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-address-card"></i>


                                <input type="text"
                                       id="nic"
                                       name="nic"
                                       maxlength="30"
                                       value="<?php
                                       echo htmlspecialchars(
                                           $nic
                                       );
                                       ?>"
                                       placeholder="Enter NIC">


                            </div>


                        </div>



                        <!-- DATE OF BIRTH -->

                        <div class="form-group">


                            <label for="dateOfBirth">

                                Date of Birth

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-calendar"></i>


                                <input type="date"
                                       id="dateOfBirth"
                                       name="date_of_birth"
                                       max="<?php
                                       echo date('Y-m-d');
                                       ?>"
                                       value="<?php
                                       echo htmlspecialchars(
                                           $dateOfBirth
                                       );
                                       ?>">


                            </div>


                        </div>



                        <!-- GENDER -->

                        <div class="form-group">


                            <label for="gender">

                                Gender

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-venus-mars"></i>


                                <select
                                    id="gender"
                                    name="gender"

                                    style="
                                        width:100%;
                                        border:1px solid var(--border);
                                        border-radius:14px;
                                        background:#ffffff;
                                        color:var(--text-strong);
                                        padding:13px 14px 13px 44px;
                                        outline:none;
                                    "
                                >


                                    <option value=""
                                        <?php
                                        echo $gender === ''
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >

                                        Select gender

                                    </option>


                                    <option value="male"
                                        <?php
                                        echo $gender === 'male'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >

                                        Male

                                    </option>


                                    <option value="female"
                                        <?php
                                        echo $gender === 'female'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >

                                        Female

                                    </option>


                                    <option value="other"
                                        <?php
                                        echo $gender === 'other'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >

                                        Other

                                    </option>


                                    <option value="prefer_not_to_say"
                                        <?php
                                        echo $gender ===
                                            'prefer_not_to_say'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >

                                        Prefer not to say

                                    </option>


                                </select>


                            </div>


                        </div>



                        <!-- FIRST ENROLLMENT DATE -->

                        <div class="form-group">


                            <label for="enrollmentDate">

                                First Enrollment Date

                            </label>


                            <div class="input-wrapper">


                                <i class="fas fa-calendar-check"></i>


                                <input type="date"
                                       id="enrollmentDate"
                                       value="<?php
                                       echo htmlspecialchars(
                                           $enrollmentDate
                                       );
                                       ?>"
                                       readonly>


                            </div>


                        </div>



                        <!-- ADDRESS -->

                        <div class="form-group full-width">


                            <label for="address">

                                Address

                            </label>


                            <div class="input-wrapper textarea-wrapper">


                                <i class="fas fa-location-dot"></i>


                                <textarea
                                    id="address"
                                    name="address"
                                    rows="4"
                                    maxlength="255"
                                    placeholder="Enter your address"
                                ><?php
                                echo htmlspecialchars(
                                    $address
                                );
                                ?></textarea>


                            </div>


                        </div>


                    </div>



                    <!-- BUTTONS -->

                    <div class="form-actions">


                        <button
                            type="reset"
                            class="secondary-btn"
                        >


                            <i class="fas fa-rotate-left"></i>

                            Reset


                        </button>



                        <button
                            type="submit"
                            name="update_profile"
                            class="primary-btn"
                        >


                            <i class="fas fa-floppy-disk"></i>

                            Save Changes


                        </button>


                    </div>


                </form>


            </section>



            <!-- =================================================
                 ACCOUNT ACTIONS
                 ================================================= -->

            <section class="card-grid">



                <a href="change_password.php"
                    class="dashboard-card">


                    <div class="card-icon bg-blue">

                        <i class="fas fa-key"></i>

                    </div>


                    <h3>

                        Change Password

                    </h3>


                    <p>

                        Update your account password and keep your
                        account secure.

                    </p>


                </a>



                <a href="courses.php"
                   class="dashboard-card">


                    <div class="card-icon bg-green">

                        <i class="fas fa-book-open"></i>

                    </div>


                    <h3>

                        My Courses

                    </h3>


                    <p>

                        View the courses and batches you are
                        currently enrolled in.

                    </p>


                </a>



                <a href="payments.php"
                   class="dashboard-card">


                    <div class="card-icon bg-purple">

                        <i class="fas fa-credit-card"></i>

                    </div>


                    <h3>

                        Payment History

                    </h3>


                    <p>

                        View your payments and access
                        payment information.

                    </p>


                </a>



                <a href="notifications.php"
                   class="dashboard-card">


                    <div class="card-icon bg-teal">

                        <i class="fas fa-bell"></i>

                    </div>


                    <h3>

                        Notifications

                    </h3>


                    <p>

                        View your latest academic and
                        system notifications.

                    </p>


                </a>


            </section>


        </main>


    </div>


</div>


<script src="../js/student.js"></script>


</body>

</html>