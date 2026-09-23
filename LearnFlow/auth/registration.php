<?php

session_start();

include "../config/db.php";

$error = "";
$success = "";

if (isset($_POST['register']))
{
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Check passwords
    if ($password != $confirm_password)
    {
        $error = "Passwords do not match";
    }
    else
    {
        // Check existing email
        $check = "SELECT * FROM users WHERE Email='$email'";
        $result = mysqli_query($conn, $check);

        if (mysqli_num_rows($result) > 0)
        {
            $error = "Email already registered";
        }
        else
        {
            // Encrypt password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $query = "INSERT INTO users (Name, Email, Password, Role)
                      VALUES ('$fullname', '$email', '$hashed_password', '$role')";

            if (mysqli_query($conn, $query))
            {
                $success = "Registration successful! Please login.";
            }
            else
            {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}

?>



<!DOCTYPE html>

<html>

<head>

<title>LearnFlow LMS - Register</title>


<link rel="stylesheet" href="../css/login.css">


<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
rel="stylesheet">


</head>



<body>



<div class="login-container">



<!-- LEFT SIDE -->


<div class="hero-section">


<div class="brand">

<i class="fa-solid fa-graduation-cap"></i>

<span>LearnFlow LMS</span>

</div>



<div class="hero-content">


<h1 class="hero-title">

Start Your Learning Journey.

</h1>


<p class="hero-description">

Create your account and access premium learning materials, recorded sessions and academic resources.

</p>



<ul class="features-list">


<li class="feature-item">

<div class="feature-icon">

<i class="fa-solid fa-shield"></i>

</div>

<span>Secure Student Access</span>

</li>


<li class="feature-item">

<div class="feature-icon">

<i class="fa-solid fa-book"></i>

</div>

<span>Premium Learning Content</span>

</li>


<li class="feature-item">

<div class="feature-icon">

<i class="fa-solid fa-graduation-cap"></i>

</div>

<span>Complete LMS Experience</span>

</li>


</ul>


</div>


</div>





<!-- REGISTER FORM -->


<div class="form-section">


<div class="form-wrapper">



<div class="form-header">

<h2>Create Account</h2>

<p>
Register to access LearnFlow LMS.
</p>


</div>




<?php

if($error!="")
{
echo "

<p style='color:red;text-align:center;margin-bottom:15px;'>
$error
</p>

";
}


if($success!="")
{
echo "

<p style='color:green;text-align:center;margin-bottom:15px;'>
$success
</p>

";
}

?>




<form method="POST">



<div class="form-group">

<label>Full Name</label>

<div class="input-wrapper">

<i class="fa-solid fa-user input-icon"></i>


<input 
type="text"
name="fullname"
class="form-input"
placeholder="Enter your full name"
required>


</div>

</div>




<div class="form-group">

<label>Email</label>


<div class="input-wrapper">

<i class="fa-solid fa-envelope input-icon"></i>


<input 
type="email"
name="email"
class="form-input"
placeholder="example@gmail.com"
required>


</div>


</div>





<div class="form-group">

<label>Password</label>


<div class="input-wrapper">

<i class="fa-solid fa-lock input-icon"></i>


<input 
type="password"
name="password"
class="form-input"
placeholder="********"
required>


</div>


</div>





<div class="form-group">

<label>Confirm Password</label>


<div class="input-wrapper">

<i class="fa-solid fa-lock input-icon"></i>


<input 
type="password"
name="confirm_password"
class="form-input"
placeholder="********"
required>


</div>


</div>





<div class="role-selector">


<label class="role-btn">

<input type="radio" name="role" value="Student" checked>

STUDENT

</label>



<label class="role-btn">

<input type="radio" name="role" value="Teacher">

TEACHER

</label>



<label class="role-btn">

<input type="radio" name="role" value="Admin">

ADMIN

</label>


</div>





<button 
class="btn-submit"
type="submit"
name="register">

Create Account

<i class="fa-solid fa-arrow-right"></i>


</button>



</form>




<div class="divider">

<span>ALREADY HAVE AN ACCOUNT?</span>

</div>



<a href="login.php" class="btn-secondary">

Login

</a>




</div>

</div>


</div>



</body>

</html>