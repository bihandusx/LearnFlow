<?php

/*session_start();
include "../config/db.php";

error_reporting(E_ALL);
ini_set('display_errors',1);*/

session_start();

include "../config/db.php";

// Login Process

if(isset($_POST['login']))
{

    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];



    $query = "SELECT * FROM users 
              WHERE email='$email' 
              AND role='$role'";


    $result = mysqli_query($conn,$query);



    if(mysqli_num_rows($result) == 1)
    {

        $user = mysqli_fetch_assoc($result);


        // Password verification

        if(password_verify($password,$user['password']))
        {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];


            header("Location: dashboard.php");
            exit();

        }
        else
        {
            $error = "Incorrect password!";
        }


    }
    else
    {
        $error = "Invalid email or role selected!";
    }


}


?>



<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | LearnFlow</title>


<link rel="stylesheet" href="../css/login.css">


<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">


</head>



<body>


<div class="login-container">


<!-- LEFT SIDE -->


<div class="hero-section">


<div class="brand">

<i class="fa-solid fa-graduation-cap"></i>

<span>LEARNFLOW LMS</span>

</div>



<div class="hero-content">


<h1 class="hero-title">

Unlock Your Academic Potential.

</h1>


<p class="hero-description">

Access your personalized learning path, premium study materials, and recorded sessions from the island's best tutors.

</p>



<ul class="features-list">


<li class="feature-item">

<div class="feature-icon">

<i class="fa-solid fa-shield"></i>

</div>

<span>Secure Institutional Access</span>

</li>



<li class="feature-item">

<div class="feature-icon">

<i class="fa-solid fa-users"></i>

</div>

<span>15,000+ Active A/L Students</span>

</li>



<li class="feature-item">

<div class="feature-icon">

<i class="fa-solid fa-circle-check"></i>

</div>

<span>Curated 2024/25 Syllabus Content</span>

</li>


</ul>


</div>




<div class="hero-footer">

© 2026 Advanced Level Tuition LMS. Empowering future leaders through technology.

</div>



</div>





<!-- RIGHT SIDE LOGIN -->


<div class="form-section">


<div class="form-wrapper">



<div class="form-header">


<h2>Welcome Back</h2>


<p>
Please enter your credentials to access your dashboard.
</p>


</div>

<form method="POST" action="">



<!-- ROLE -->


<div class="role-selector">


<label class="role-btn active">

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





<!-- EMAIL -->


<div class="form-group">


<label>Email</label>


<div class="input-wrapper">


<i class="fa-solid fa-envelope input-icon"></i>


<input 
type="email"
name="email"
class="form-input"
placeholder="student@altuition.lk"
required>


</div>


</div>





<!-- PASSWORD -->


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





<div class="checkbox-group">


<input type="checkbox">


<label>
Keep me logged in on this device
</label>


</div>





<button 
type="submit"
name="login"
class="btn-submit">


Sign In to Dashboard

<i class="fa-solid fa-arrow-right"></i>


</button>



</form>






<div class="divider">

<span>NEW TO THE PLATFORM?</span>

</div>




<a href="registration.php" class="btn-secondary">

Create Student Account

</a>





<div class="terms-note">

By logging in, you agree to our Terms of Service and Privacy Policy.

</div>




</div>


</div>



</div>


</body>


</html>