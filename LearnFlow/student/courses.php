<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Courses | LEARNFLOW</title>


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


    <!-- Student CSS -->

    <link
        rel="stylesheet"
        href="../css/student.css"
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


            <!-- Learning -->

            <div class="nav-section-title">
                Learning
            </div>


            <a
                href="courses.php"
                class="nav-link active"
            >

                <i class="fa-solid fa-book-open"></i>

                <span>My Courses</span>

            </a>


            <a
                href="materials.php"
                class="nav-link"
            >

                <i class="fa-solid fa-file-lines"></i>

                <span>Learning Materials</span>

            </a>


            <a
                href="assignments.php"
                class="nav-link"
            >

                <i class="fa-solid fa-file-pen"></i>

                <span>Assignments</span>

            </a>


            <a
                href="quizzes.php"
                class="nav-link"
            >

                <i class="fa-solid fa-circle-question"></i>

                <span>Quizzes</span>

            </a>


            <a
                href="exams.php"
                class="nav-link"
            >

                <i class="fa-solid fa-clipboard-check"></i>

                <span>Examinations</span>

            </a>


            <!-- Progress & Communication -->

            <div class="nav-section-title">
                Progress & Communication
            </div>


            <a
                href="grades.php"
                class="nav-link"
            >

                <i class="fa-solid fa-chart-column"></i>

                <span>Grades & Results</span>

            </a>


            <a
                href="progress.php"
                class="nav-link"
            >

                <i class="fa-solid fa-chart-line"></i>

                <span>My Progress</span>

            </a>


            <a
                href="announcements.php"
                class="nav-link"
            >

                <i class="fa-solid fa-bullhorn"></i>

                <span>Announcements</span>

            </a>


            <a
                href="schedule.php"
                class="nav-link"
            >

                <i class="fa-solid fa-calendar-days"></i>

                <span>Class Schedule</span>

            </a>


            <a
                href="forum.php"
                class="nav-link"
            >

                <i class="fa-solid fa-comments"></i>

                <span>Discussion Forum</span>

            </a>


            <!-- Payments -->

            <div class="nav-section-title">
                Payments
            </div>


            <a
                href="tutes.php"
                class="nav-link"
            >

                <i class="fa-solid fa-store"></i>

                <span>Digital Tute Store</span>

            </a>


            <a
                href="payments.php"
                class="nav-link"
            >

                <i class="fa-solid fa-credit-card"></i>

                <span>Payment History</span>

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

                        <h2>My Courses</h2>

                        <p>
                            Manage your enrolled courses and continue learning.
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

                        AS

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            Alex Silva
                        </span>

                        <span class="user-role">
                            Student
                        </span>

                    </div>


                </div>


            </div>


        </header>



        <!-- =================================================
             COURSE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="courses-page-header">


                <div>

                    <h1>
                        My Learning
                    </h1>

                    <p>
                        Access your enrolled courses and explore new learning opportunities.
                    </p>

                </div>


                <button
                    type="button"
                    class="browse-courses-btn"
                    id="browseCoursesButton"
                >

                    <i class="fa-solid fa-compass"></i>

                    Browse Courses

                </button>


            </div>



            <!-- COURSE STATISTICS -->

            <div class="stats-grid">


                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-book-open"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            4
                        </span>

                        <span class="stat-label">
                            Enrolled Courses
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-spinner"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            3
                        </span>

                        <span class="stat-label">
                            In Progress
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-circle-check"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            1
                        </span>

                        <span class="stat-label">
                            Completed
                        </span>

                    </div>


                </div>



                <div class="stat-card">


                    <div class="stat-icon">

                        <i class="fa-solid fa-chart-line"></i>

                    </div>


                    <div class="stat-info">

                        <span class="stat-number">
                            72%
                        </span>

                        <span class="stat-label">
                            Average Progress
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 ENROLLED COURSES
            ================================================== -->

            <div
                class="courses-section"
                id="enrolledCourses"
            >


                <div class="section-heading">


                    <div>

                        <h2>
                            Enrolled Courses
                        </h2>

                        <p>
                            Courses you are currently studying.
                        </p>

                    </div>


                </div>



                <div class="course-grid">



                    <!-- COURSE 1 -->

                    <div class="course-card">


                        <div class="course-card-banner mathematics-banner">

                            <i class="fa-solid fa-calculator"></i>

                            <span>
                                PHYSICAL SCIENCE
                            </span>

                        </div>


                        <div class="course-card-body">


                            <div class="course-status">

                                <span class="course-active">
                                    Active
                                </span>

                            </div>


                            <h3>
                                Combined Mathematics
                            </h3>


                            <p class="course-description">

                                Master advanced mathematical concepts including calculus, algebra, geometry and statistics.

                            </p>


                            <div class="course-meta">


                                <span>

                                    <i class="fa-solid fa-user-tie"></i>

                                    Mr. Perera

                                </span>


                                <span>

                                    <i class="fa-solid fa-layer-group"></i>

                                    12 Modules

                                </span>


                            </div>


                            <div class="course-progress">


                                <div class="course-progress-label">

                                    <span>
                                        Course Progress
                                    </span>

                                    <strong>
                                        80%
                                    </strong>

                                </div>


                                <div class="course-progress-bar">

                                    <div
                                        class="course-progress-fill"
                                        style="width: 80%;"
                                    ></div>

                                </div>


                            </div>


                            <a
                                href="course_details.php"
                                class="continue-course-btn"
                            >

                                Continue Learning

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </div>


                    </div>



                    <!-- COURSE 2 -->

                    <div class="course-card">


                        <div class="course-card-banner chemistry-banner">

                            <i class="fa-solid fa-flask"></i>

                            <span>
                                PHYSICAL SCIENCE
                            </span>

                        </div>


                        <div class="course-card-body">


                            <div class="course-status">

                                <span class="course-active">
                                    Active
                                </span>

                            </div>


                            <h3>
                                Chemistry
                            </h3>


                            <p class="course-description">

                                Explore organic, inorganic and physical chemistry with comprehensive learning resources.

                            </p>


                            <div class="course-meta">


                                <span>

                                    <i class="fa-solid fa-user-tie"></i>

                                    Dr. Fernando

                                </span>


                                <span>

                                    <i class="fa-solid fa-layer-group"></i>

                                    10 Modules

                                </span>

                            </div>


                            <div class="course-progress">


                                <div class="course-progress-label">

                                    <span>
                                        Course Progress
                                    </span>

                                    <strong>
                                        65%
                                    </strong>

                                </div>


                                <div class="course-progress-bar">

                                    <div
                                        class="course-progress-fill"
                                        style="width: 65%;"
                                    ></div>

                                </div>


                            </div>


                            <a
                                href="course_details.php"
                                class="continue-course-btn"
                            >

                                Continue Learning

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </div>


                    </div>



                    <!-- COURSE 3 -->

                    <div class="course-card">


                        <div class="course-card-banner physics-banner">

                            <i class="fa-solid fa-atom"></i>

                            <span>
                                PHYSICAL SCIENCE
                            </span>

                        </div>


                        <div class="course-card-body">


                            <div class="course-status">

                                <span class="course-active">
                                    Active
                                </span>

                            </div>


                            <h3>
                                Physics
                            </h3>


                            <p class="course-description">

                                Understand mechanics, waves, electricity and modern physics through structured lessons.

                            </p>


                            <div class="course-meta">


                                <span>

                                    <i class="fa-solid fa-user-tie"></i>

                                    Mr. Silva

                                </span>


                                <span>

                                    <i class="fa-solid fa-layer-group"></i>

                                    14 Modules

                                </span>

                            </div>


                            <div class="course-progress">


                                <div class="course-progress-label">

                                    <span>
                                        Course Progress
                                    </span>

                                    <strong>
                                        72%
                                    </strong>

                                </div>


                                <div class="course-progress-bar">

                                    <div
                                        class="course-progress-fill"
                                        style="width: 72%;"
                                    ></div>

                                </div>


                            </div>


                            <a
                                href="course_details.php"
                                class="continue-course-btn"
                            >

                                Continue Learning

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </div>


                    </div>



                    <!-- COURSE 4 -->

                    <div class="course-card">


                        <div class="course-card-banner english-banner">

                            <i class="fa-solid fa-language"></i>

                            <span>
                                GENERAL
                            </span>

                        </div>


                        <div class="course-card-body">


                            <div class="course-status completed-status">

                                <span>
                                    Completed
                                </span>

                            </div>


                            <h3>
                                General English
                            </h3>


                            <p class="course-description">

                                Develop communication, grammar and academic writing skills for your academic journey.

                            </p>


                            <div class="course-meta">


                                <span>

                                    <i class="fa-solid fa-user-tie"></i>

                                    Ms. Perera

                                </span>


                                <span>

                                    <i class="fa-solid fa-layer-group"></i>

                                    8 Modules

                                </span>

                            </div>


                            <div class="course-progress">


                                <div class="course-progress-label">

                                    <span>
                                        Course Progress
                                    </span>

                                    <strong>
                                        100%
                                    </strong>

                                </div>


                                <div class="course-progress-bar">

                                    <div
                                        class="course-progress-fill completed-fill"
                                        style="width: 100%;"
                                    ></div>

                                </div>


                            </div>


                            <a
                                href="course_details.php"
                                class="continue-course-btn"
                            >

                                View Course

                                <i class="fa-solid fa-arrow-right"></i>

                            </a>


                        </div>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 AVAILABLE COURSES
            ================================================== -->

            <div
                class="courses-section available-courses"
                id="availableCourses"
            >


                <div class="section-heading">


                    <div>

                        <h2>
                            Explore Available Courses
                        </h2>

                        <p>
                            Discover new courses available for enrollment.
                        </p>

                    </div>


                    <a
                        href="#"
                        class="view-all"
                    >

                        View All

                    </a>


                </div>



                <div class="available-course-grid">



                    <!-- AVAILABLE COURSE 1 -->

                    <div class="available-course-card">


                        <div class="available-course-icon">

                            <i class="fa-solid fa-laptop-code"></i>

                        </div>


                        <div class="available-course-info">


                            <h3>
                                Information Technology
                            </h3>


                            <p>
                                Learn programming, databases and modern information systems.
                            </p>


                            <div class="available-course-footer">


                                <span>

                                    <i class="fa-solid fa-users"></i>

                                    120 Students

                                </span>


                                <strong>
                                    LKR 5,000
                                </strong>


                            </div>


                            <button
                                type="button"
                                class="enroll-btn"
                            >

                                View Course

                            </button>


                        </div>


                    </div>



                    <!-- AVAILABLE COURSE 2 -->

                    <div class="available-course-card">


                        <div class="available-course-icon">

                            <i class="fa-solid fa-dna"></i>

                        </div>


                        <div class="available-course-info">


                            <h3>
                                Biology
                            </h3>


                            <p>
                                Study life sciences, genetics, ecology and human biology.
                            </p>


                            <div class="available-course-footer">


                                <span>

                                    <i class="fa-solid fa-users"></i>

                                    95 Students

                                </span>


                                <strong>
                                    LKR 4,500
                                </strong>


                            </div>


                            <button
                                type="button"
                                class="enroll-btn"
                            >

                                View Course

                            </button>


                        </div>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>



<!-- Student JavaScript -->

<script src="../js/student.js"></script>


<!-- Courses JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* -------------------------
           BROWSE COURSES BUTTON
        ------------------------- */

        const browseButton =
            document.getElementById(
                "browseCoursesButton"
            );


        const availableCourses =
            document.getElementById(
                "availableCourses"
            );


        if (browseButton) {


            browseButton.addEventListener(
                "click",
                function () {


                    availableCourses.scrollIntoView(
                        {
                            behavior: "smooth"
                        }
                    );


                }
            );


        }


        /* -------------------------
           ENROLLMENT BUTTONS
        ------------------------- */

        const enrollButtons =
            document.querySelectorAll(
                ".enroll-btn"
            );


        enrollButtons.forEach(
            function (button) {


                button.addEventListener(
                    "click",
                    function () {


                        alert(
                            "Course details and enrollment functionality will be connected to the backend later."
                        );


                    }
                );


            }
        );


    }
);


</script>


</body>

</html>