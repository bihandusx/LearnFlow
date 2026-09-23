<?php

session_start();

require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = "";
$success = "";


/*
|--------------------------------------------------------------------------
| REGISTER USER
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["register"])
) {

    /*
    |--------------------------------------------------------------------------
    | GET FORM VALUES
    |--------------------------------------------------------------------------
    */

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");

    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $role = $_POST["role"] ?? "Student";


    /*
    |--------------------------------------------------------------------------
    | PUBLIC REGISTRATION ROLES
    |--------------------------------------------------------------------------
    |
    | Admin and Academic Coordinator must NOT be created by public users.
    |
    */

    $allowedRegistrationRoles = [
        "Student",
        "Teacher"
    ];


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($fullname === "") {

        $error = "Full name is required.";

    } elseif (strlen($fullname) > 150) {

        $error = "Full name is too long.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error = "Please enter a valid email address.";

    } elseif (strlen($email) > 190) {

        $error = "Email address is too long.";

    } elseif (
        !in_array(
            $role,
            $allowedRegistrationRoles,
            true
        )
    ) {

        $error =
            "This role cannot be created through public registration.";

    } elseif ($password === "") {

        $error = "Password is required.";

    } elseif (strlen($password) < 8) {

        $error =
            "Password must contain at least 8 characters.";

    } elseif ($password !== $confirmPassword) {

        $error = "Passwords do not match.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK WHETHER EMAIL ALREADY EXISTS
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT user_id
                FROM user_accounts
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "s",
                $email
            );

            $stmt->execute();

            $result = $stmt->get_result();


            if ($result->num_rows > 0) {

                $error =
                    "This email address is already registered.";

                $stmt->close();

            } else {

                $stmt->close();


                /*
                |--------------------------------------------------------------------------
                | HASH PASSWORD
                |--------------------------------------------------------------------------
                */

                $hashedPassword =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                /*
                |--------------------------------------------------------------------------
                | ACCOUNT STATUS
                |--------------------------------------------------------------------------
                |
                | Students become active immediately.
                | Teachers wait for administrator approval.
                |
                */

                $status =
                    $role === "Teacher"
                    ? "pending"
                    : "active";


                /*
                |--------------------------------------------------------------------------
                | INSERT USER ACCOUNT
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                | Insert into user_accounts, NOT users.
                |
                */

                $stmt = $conn->prepare("
                    INSERT INTO user_accounts
                    (
                        fullname,
                        email,
                        password,
                        status,
                        role
                    )
                    VALUES (?, ?, ?, ?, ?)
                ");


                $stmt->bind_param(
                    "sssss",
                    $fullname,
                    $email,
                    $hashedPassword,
                    $status,
                    $role
                );


                $stmt->execute();


                $newUserId =
                    $conn->insert_id;


                $stmt->close();


                /*
                |--------------------------------------------------------------------------
                | STUDENT / TEACHER SUBTYPE
                |--------------------------------------------------------------------------
                |
                | Do NOT manually insert into students here.
                |
                | Your database trigger:
                |
                | trg_user_accounts_create_subtype
                |
                | automatically creates:
                |
                | Student -> students
                | Teacher -> teachers
                |
                */


                /*
                |--------------------------------------------------------------------------
                | SUCCESS MESSAGE
                |--------------------------------------------------------------------------
                */

                if ($role === "Student") {

                    $success =
                        "Student account created successfully. "
                        . "You can now log in.";

                } else {

                    $success =
                        "Teacher account created successfully. "
                        . "Your account is waiting for administrator approval.";
                }


                /*
                |--------------------------------------------------------------------------
                | CLEAR FORM VALUES
                |--------------------------------------------------------------------------
                */

                $fullname = "";
                $email = "";
                $role = "Student";
            }

        } catch (mysqli_sql_exception $e) {

            /*
            |--------------------------------------------------------------------------
            | DATABASE ERROR
            |--------------------------------------------------------------------------
            */

            $error =
                "Registration failed. Please try again.";
        }
    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">


    <title>
        LearnFlow LMS - Register
    </title>


    <link
        rel="stylesheet"
        href="../css/login.css"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

</head>


<body>


<div class="login-container">


    <!-- =====================================================
         LEFT SIDE
         ===================================================== -->

    <div class="hero-section">


        <div class="brand">

            <i class="fa-solid fa-graduation-cap"></i>

            <span>
                LearnFlow LMS
            </span>

        </div>


        <div class="hero-content">


            <h1 class="hero-title">

                Start Your Learning Journey.

            </h1>


            <p class="hero-description">

                Create your account and access premium
                learning materials, recorded sessions and
                academic resources.

            </p>


            <ul class="features-list">


                <li class="feature-item">

                    <div class="feature-icon">

                        <i class="fa-solid fa-shield"></i>

                    </div>

                    <span>
                        Secure Student Access
                    </span>

                </li>


                <li class="feature-item">

                    <div class="feature-icon">

                        <i class="fa-solid fa-book"></i>

                    </div>

                    <span>
                        Premium Learning Content
                    </span>

                </li>


                <li class="feature-item">

                    <div class="feature-icon">

                        <i class="fa-solid fa-graduation-cap"></i>

                    </div>

                    <span>
                        Complete LMS Experience
                    </span>

                </li>


            </ul>


        </div>


    </div>



    <!-- =====================================================
         REGISTRATION FORM
         ===================================================== -->

    <div class="form-section">


        <div class="form-wrapper">


            <div class="form-header">

                <h2>
                    Create Account
                </h2>

                <p>
                    Register to access LearnFlow LMS.
                </p>

            </div>



            <!-- ERROR MESSAGE -->

            <?php if ($error !== ""): ?>

                <p
                    style="
                        color:#b91c1c;
                        text-align:center;
                        margin-bottom:15px;
                    "
                >

                    <i class="fa-solid fa-circle-exclamation"></i>

                    <?php
                    echo htmlspecialchars($error);
                    ?>

                </p>

            <?php endif; ?>



            <!-- SUCCESS MESSAGE -->

            <?php if ($success !== ""): ?>

                <p
                    style="
                        color:#15803d;
                        text-align:center;
                        margin-bottom:15px;
                    "
                >

                    <i class="fa-solid fa-circle-check"></i>

                    <?php
                    echo htmlspecialchars($success);
                    ?>

                </p>

            <?php endif; ?>



            <form
                method="POST"
                action="registration.php"
            >


                <!-- FULL NAME -->

                <div class="form-group">


                    <label for="fullname">

                        Full Name

                    </label>


                    <div class="input-wrapper">


                        <i class="fa-solid fa-user input-icon"></i>


                        <input
                            type="text"
                            id="fullname"
                            name="fullname"
                            class="form-input"
                            placeholder="Enter your full name"
                            maxlength="150"
                            value="<?php
                            echo htmlspecialchars(
                                $fullname ?? ""
                            );
                            ?>"
                            required
                        >


                    </div>


                </div>



                <!-- EMAIL -->

                <div class="form-group">


                    <label for="email">

                        Email

                    </label>


                    <div class="input-wrapper">


                        <i class="fa-solid fa-envelope input-icon"></i>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="example@gmail.com"
                            maxlength="190"
                            value="<?php
                            echo htmlspecialchars(
                                $email ?? ""
                            );
                            ?>"
                            required
                        >


                    </div>


                </div>



                <!-- PASSWORD -->

                <div class="form-group">


                    <label for="password">

                        Password

                    </label>


                    <div class="input-wrapper">


                        <i class="fa-solid fa-lock input-icon"></i>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Minimum 8 characters"
                            minlength="8"
                            required
                        >


                    </div>


                </div>



                <!-- CONFIRM PASSWORD -->

                <div class="form-group">


                    <label for="confirm_password">

                        Confirm Password

                    </label>


                    <div class="input-wrapper">


                        <i class="fa-solid fa-lock input-icon"></i>


                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-input"
                            placeholder="Re-enter your password"
                            minlength="8"
                            required
                        >


                    </div>


                </div>



                <!-- ROLE -->

                <div class="role-selector">


                    <label class="role-btn">


                        <input
                            type="radio"
                            name="role"
                            value="Student"

                            <?php
                            echo (
                                ($role ?? "Student")
                                === "Student"
                            )
                            ? "checked"
                            : "";
                            ?>
                        >


                        STUDENT


                    </label>



                    <label class="role-btn">


                        <input
                            type="radio"
                            name="role"
                            value="Teacher"

                            <?php
                            echo (
                                ($role ?? "")
                                === "Teacher"
                            )
                            ? "checked"
                            : "";
                            ?>
                        >


                        TEACHER


                    </label>


                </div>



                <!-- REGISTER BUTTON -->

                <button
                    class="btn-submit"
                    type="submit"
                    name="register"
                >

                    Create Account

                    <i class="fa-solid fa-arrow-right"></i>

                </button>


            </form>



            <div class="divider">

                <span>
                    ALREADY HAVE AN ACCOUNT?
                </span>

            </div>



            <a
                href="login.php"
                class="btn-secondary"
            >

                Login

            </a>


        </div>


    </div>


</div>


</body>

</html>