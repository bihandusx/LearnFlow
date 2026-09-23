<?php

session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';
$success = '';


/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
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
| LOAD STUDENT DETAILS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        fullname,
        email,
        password
    FROM user_accounts
    WHERE
        user_id = ?
        AND role = 'Student'
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$user = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


if (!$user) {

    session_unset();
    session_destroy();

    header("Location: ../auth/login.php");
    exit();
}


$studentName = $user['fullname'];


/*
|--------------------------------------------------------------------------
| CHANGE PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['change_password'])
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $csrfToken
        )
    ) {

        $error = "Invalid request. Please refresh the page and try again.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | GET FORM VALUES
        |--------------------------------------------------------------------------
        */

        $currentPassword =
            $_POST['current_password'] ?? '';

        $newPassword =
            $_POST['new_password'] ?? '';

        $confirmPassword =
            $_POST['confirm_password'] ?? '';


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        if (
            $currentPassword === '' ||
            $newPassword === '' ||
            $confirmPassword === ''
        ) {

            $error = "Please complete all password fields.";

        } elseif (
            !password_verify(
                $currentPassword,
                $user['password']
            )
        ) {

            $error = "Your current password is incorrect.";

        } elseif (strlen($newPassword) < 8) {

            $error =
                "New password must contain at least 8 characters.";

        } elseif ($newPassword !== $confirmPassword) {

            $error =
                "New password and confirm password do not match.";

        } elseif (
            password_verify(
                $newPassword,
                $user['password']
            )
        ) {

            $error =
                "Your new password must be different from your current password.";

        } else {

            /*
            |--------------------------------------------------------------------------
            | HASH NEW PASSWORD
            |--------------------------------------------------------------------------
            */

            $hashedPassword =
                password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );


            /*
            |--------------------------------------------------------------------------
            | UPDATE DATABASE
            |--------------------------------------------------------------------------
            */

            try {

                $stmt = $conn->prepare("
                    UPDATE user_accounts
                    SET password = ?
                    WHERE
                        user_id = ?
                        AND role = 'Student'
                ");

                $stmt->bind_param(
                    "si",
                    $hashedPassword,
                    $userId
                );

                $stmt->execute();
                $stmt->close();


                /*
                |--------------------------------------------------------------------------
                | REGENERATE SESSION ID
                |--------------------------------------------------------------------------
                */

                session_regenerate_id(true);


                /*
                |--------------------------------------------------------------------------
                | SUCCESS MESSAGE
                |--------------------------------------------------------------------------
                */

                $success =
                    "Your password has been changed successfully.";


                /*
                |--------------------------------------------------------------------------
                | REFRESH CSRF TOKEN
                |--------------------------------------------------------------------------
                */

                $_SESSION['csrf_token'] =
                    bin2hex(random_bytes(32));


                /*
                |--------------------------------------------------------------------------
                | UPDATE LOCAL PASSWORD HASH
                |--------------------------------------------------------------------------
                */

                $user['password'] =
                    $hashedPassword;

            } catch (Throwable $e) {

                $error =
                    "Password update failed. Please try again.";
            }
        }
    }
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

    <title>
        <?php echo htmlspecialchars($projectName); ?>
        | Change Password
    </title>

    <link
        rel="stylesheet"
        href="../css/student.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
    >

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


            <a
                href="dashboard.php"
                class="menu-link"
            >

                <i class="fas fa-tachometer-alt"></i>

                Dashboard

            </a>


            <a
                href="profile.php"
                class="menu-link active"
            >

                <i class="fas fa-user"></i>

                Profile

            </a>


            <a
                href="courses.php"
                class="menu-link"
            >

                <i class="fas fa-book-open"></i>

                Courses

            </a>


            <a
                href="materials.php"
                class="menu-link"
            >

                <i class="fas fa-folder-open"></i>

                Materials

            </a>


            <a
                href="assignments.php"
                class="menu-link"
            >

                <i class="fas fa-file-alt"></i>

                Assignments

            </a>


            <a
                href="quizzes.php"
                class="menu-link"
            >

                <i class="fas fa-check-square"></i>

                Quizzes

            </a>


            <a
                href="exams.php"
                class="menu-link"
            >

                <i class="fas fa-clipboard-list"></i>

                Exams

            </a>


            <a
                href="grades.php"
                class="menu-link"
            >

                <i class="fas fa-chart-column"></i>

                Grades

            </a>


            <a
                href="progress.php"
                class="menu-link"
            >

                <i class="fas fa-chart-line"></i>

                Progress

            </a>


            <a
                href="attendance.php"
                class="menu-link"
            >

                <i class="fas fa-calendar-check"></i>

                Attendance

            </a>


            <a
                href="schedule.php"
                class="menu-link"
            >

                <i class="fas fa-calendar-days"></i>

                Schedule

            </a>


            <a
                href="announcements.php"
                class="menu-link"
            >

                <i class="fas fa-bullhorn"></i>

                Announcements

            </a>


            <a
                href="discussions.php"
                class="menu-link"
            >

                <i class="fas fa-comments"></i>

                Discussions

            </a>


            <a
                href="tute_store.php"
                class="menu-link"
            >

                <i class="fas fa-store"></i>

                Tute Store

            </a>


            <a
                href="payments.php"
                class="menu-link"
            >

                <i class="fas fa-credit-card"></i>

                Payments

            </a>


            <a
                href="notifications.php"
                class="menu-link"
            >

                <i class="fas fa-bell"></i>

                Notifications

            </a>


            <a
                href="../auth/logout.php"
                class="menu-link logout-link"
            >

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


                <button
                    class="mobile-menu-btn"
                    type="button"
                >

                    <i class="fas fa-bars"></i>

                </button>


                <div class="dashboard-title">

                    <p class="small-label">
                        Profile
                    </p>

                    <h1>
                        Change Password
                    </h1>

                </div>


            </div>



            <div class="topbar-right">


                <div class="project-pill">
                    LearnFlow
                </div>


                <a
                    href="notifications.php"
                    class="icon-btn"
                >

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
             CHANGE PASSWORD CONTENT
             ================================================= -->

        <main class="dashboard-main">


            <section class="profile-section">


                <div class="profile-section-header">


                    <div>

                        <p class="card-label">
                            Account Security
                        </p>

                        <h2>
                            Change Password
                        </h2>

                        <p>
                            Update your account password to keep
                            your LearnFlow account secure.
                        </p>

                    </div>


                    <a
                        href="profile.php"
                        class="secondary-btn"
                    >

                        <i class="fas fa-arrow-left"></i>

                        Back to Profile

                    </a>


                </div>



                <!-- =================================================
                     SUCCESS MESSAGE
                     ================================================= -->

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



                <!-- =================================================
                     ERROR MESSAGE
                     ================================================= -->

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



                <!-- =================================================
                     PASSWORD FORM
                     ================================================= -->

                <form
                    method="POST"
                    action="change_password.php"
                    class="profile-form"
                >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php
                            echo htmlspecialchars(
                                $_SESSION['csrf_token']
                            );
                        ?>"
                    >



                    <div class="form-grid">


                        <!-- CURRENT PASSWORD -->

                        <div class="form-group full-width">


                            <label for="currentPassword">

                                Current Password

                            </label>


                            <div class="input-wrapper">

                                <i class="fas fa-lock"></i>

                                <input
                                    type="password"
                                    id="currentPassword"
                                    name="current_password"
                                    placeholder="Enter your current password"
                                    autocomplete="current-password"
                                    required
                                >

                            </div>


                        </div>



                        <!-- NEW PASSWORD -->

                        <div class="form-group">


                            <label for="newPassword">

                                New Password

                            </label>


                            <div class="input-wrapper">

                                <i class="fas fa-key"></i>

                                <input
                                    type="password"
                                    id="newPassword"
                                    name="new_password"
                                    minlength="8"
                                    placeholder="Enter new password"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>


                            <small
                                style="
                                    display:block;
                                    margin-top:7px;
                                    color:#64748b;
                                "
                            >

                                Password must contain at least
                                8 characters.

                            </small>


                        </div>



                        <!-- CONFIRM PASSWORD -->

                        <div class="form-group">


                            <label for="confirmPassword">

                                Confirm New Password

                            </label>


                            <div class="input-wrapper">

                                <i class="fas fa-key"></i>

                                <input
                                    type="password"
                                    id="confirmPassword"
                                    name="confirm_password"
                                    minlength="8"
                                    placeholder="Confirm new password"
                                    autocomplete="new-password"
                                    required
                                >

                            </div>


                        </div>


                    </div>



                    <!-- =================================================
                         BUTTONS
                         ================================================= -->

                    <div class="form-actions">


                        <a
                            href="profile.php"
                            class="secondary-btn"
                        >

                            <i class="fas fa-xmark"></i>

                            Cancel

                        </a>


                        <button
                            type="submit"
                            name="change_password"
                            class="primary-btn"
                        >

                            <i class="fas fa-floppy-disk"></i>

                            Update Password

                        </button>


                    </div>


                </form>


            </section>


        </main>


    </div>


</div>


<script src="../js/student.js"></script>


</body>

</html>