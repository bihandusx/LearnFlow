<?php

session_start();

require_once "../config/db.php";

$error = "";


/*
|--------------------------------------------------------------------------
| AUTOMATIC PROJECT BASE URL
|--------------------------------------------------------------------------
|
| Example:
|
| /LearnFlow/auth/login.php
|
| becomes:
|
| /LearnFlow
|
| This also works if the project folder is renamed or nested.
|
*/

$baseUrl = rtrim(
    dirname(dirname($_SERVER['SCRIPT_NAME'])),
    '/\\'
);


/*
|--------------------------------------------------------------------------
| PROCESS LOGIN
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["login"])) {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";


    /*
    |--------------------------------------------------------------------------
    | VALID ROLES
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [
        "Student",
        "Teacher",
        "Parent",
        "Admin",
        "AcademicCoordinator"
    ];


    if ($email === "") {

        $error = "Email address is required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $error = "Please enter a valid email address.";

    } elseif ($password === "") {

        $error = "Password is required.";

    } elseif (!in_array($role, $allowedRoles, true)) {

        $error = "Please select a valid role.";

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | FIND USER
            |--------------------------------------------------------------------------
            */

            $stmt = $conn->prepare("
                SELECT
                    user_id,
                    fullname,
                    email,
                    password,
                    status,
                    role
                FROM user_accounts
                WHERE email = ?
                  AND role = ?
                LIMIT 1
            ");

            $stmt->bind_param(
                "ss",
                $email,
                $role
            );

            $stmt->execute();

            $result = $stmt->get_result();


            /*
            |--------------------------------------------------------------------------
            | USER FOUND
            |--------------------------------------------------------------------------
            */

            if ($result->num_rows === 1) {

                $user = $result->fetch_assoc();


                /*
                |--------------------------------------------------------------------------
                | ACCOUNT STATUS CHECK
                |--------------------------------------------------------------------------
                */

                if ($user["status"] !== "active") {

                    if ($user["status"] === "pending") {

                        $error =
                            "Your account is pending approval.";

                    } elseif ($user["status"] === "suspended") {

                        $error =
                            "Your account has been suspended.";

                    } else {

                        $error =
                            "Your account is not active.";
                    }

                }

                /*
                |--------------------------------------------------------------------------
                | PASSWORD CHECK
                |--------------------------------------------------------------------------
                */

                elseif (
                    password_verify(
                        $password,
                        $user["password"]
                    )
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | LOGIN SUCCESSFUL
                    |--------------------------------------------------------------------------
                    */

                    session_regenerate_id(true);

                    $_SESSION["user_id"] =
                        (int) $user["user_id"];

                    $_SESSION["name"] =
                        $user["fullname"];

                    $_SESSION["email"] =
                        $user["email"];

                    $_SESSION["role"] =
                        $user["role"];


                    /*
                    |--------------------------------------------------------------------------
                    | ROLE DASHBOARDS
                    |--------------------------------------------------------------------------
                    */

                    $destinations = [

                        "Student" =>
                            $baseUrl .
                            "/student/dashboard.php",

                        "Teacher" =>
                            $baseUrl .
                            "/teacher/dashboard.php",

                        "Parent" =>
                            $baseUrl .
                            "/parent/dashboard.php",

                        "Admin" =>
                            $baseUrl .
                            "/admin/dashboard.php",

                        "AcademicCoordinator" =>
                            $baseUrl .
                            "/coordinator/dashboard.php"
                    ];


                    /*
                    |--------------------------------------------------------------------------
                    | REDIRECT
                    |--------------------------------------------------------------------------
                    */

                    header(
                        "Location: " .
                        $destinations[$user["role"]]
                    );

                    exit();

                } else {

                    $error =
                        "Incorrect email, password, or role.";
                }

            } else {

                $error =
                    "Incorrect email, password, or role.";
            }


            $stmt->close();

        } catch (mysqli_sql_exception $e) {

            $error =
                "Login failed because of a database error.";
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
        Login | LearnFlow
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
                LEARNFLOW LMS
            </span>

        </div>


        <div class="hero-content">


            <h1 class="hero-title">

                Unlock Your Academic Potential.

            </h1>


            <p class="hero-description">

                Access your personalized learning path,
                study materials and academic resources.

            </p>


            <ul class="features-list">


                <li class="feature-item">

                    <div class="feature-icon">

                        <i class="fa-solid fa-shield"></i>

                    </div>

                    <span>
                        Secure Institutional Access
                    </span>

                </li>


                <li class="feature-item">

                    <div class="feature-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>

                    <span>
                        Learning Resources
                    </span>

                </li>


                <li class="feature-item">

                    <div class="feature-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>

                    <span>
                        Complete LMS Experience
                    </span>

                </li>


            </ul>


        </div>


    </div>



    <!-- =====================================================
         LOGIN FORM
         ===================================================== -->

    <div class="form-section">


        <div class="form-wrapper">


            <div class="form-header">

                <h2>
                    Welcome Back
                </h2>

                <p>
                    Please enter your credentials
                    to access your dashboard.
                </p>

            </div>



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



            <form
                method="POST"
                action="login.php"
            >


                <!-- ROLE -->

                <div class="role-selector">


                    <label class="role-btn">

                        <input
                            type="radio"
                            name="role"
                            value="Student"
                            checked
                        >

                        STUDENT

                    </label>


                    <label class="role-btn">

                        <input
                            type="radio"
                            name="role"
                            value="Teacher"
                        >

                        TEACHER

                    </label>


                    <label class="role-btn">

                        <input
                            type="radio"
                            name="role"
                            value="Parent"
                        >

                        PARENT

                    </label>


                    <label class="role-btn">

                        <input
                            type="radio"
                            name="role"
                            value="Admin"
                        >

                        ADMIN

                    </label>


                    <label class="role-btn">

                        <input
                            type="radio"
                            name="role"
                            value="AcademicCoordinator"
                        >

                        COORDINATOR

                    </label>


                </div>



                <!-- EMAIL -->

                <div class="form-group">


                    <label for="email">

                        Email Address

                    </label>


                    <div class="input-wrapper">


                        <i class="fa-solid fa-envelope input-icon"></i>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="Enter your email"
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
                            placeholder="Enter your password"
                            required
                        >


                    </div>


                </div>



                <!-- LOGIN BUTTON -->

                <button
                    class="btn-submit"
                    type="submit"
                    name="login"
                >

                    Login

                    <i class="fa-solid fa-arrow-right"></i>

                </button>


            </form>



            <div class="divider">

                <span>
                    DON'T HAVE AN ACCOUNT?
                </span>

            </div>


            <a
                href="registration.php"
                class="btn-secondary"
            >

                Create Account

            </a>


        </div>


    </div>


</div>


</body>

</html>