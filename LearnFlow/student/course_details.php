<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Course Details | LEARNFLOW</title>


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

                        <h2>Course Details</h2>

                        <p>
                            Continue your learning journey.
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


            <!-- BACK BUTTON -->

            <a
                href="courses.php"
                class="back-course-link"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back to My Courses

            </a>



            <!-- COURSE HERO -->

            <div class="course-detail-hero">


                <div class="course-detail-icon">

                    <i class="fa-solid fa-calculator"></i>

                </div>


                <div class="course-detail-info">


                    <span class="course-category">
                        PHYSICAL SCIENCE
                    </span>


                    <h1>
                        Combined Mathematics
                    </h1>


                    <p>

                        Master advanced mathematical concepts including calculus,
                        algebra, geometry and statistics through structured lessons
                        and practical activities.

                    </p>


                    <div class="course-detail-meta">


                        <span>

                            <i class="fa-solid fa-user-tie"></i>

                            Mr. Perera

                        </span>


                        <span>

                            <i class="fa-solid fa-layer-group"></i>

                            12 Modules

                        </span>


                        <span>

                            <i class="fa-solid fa-users"></i>

                            245 Students

                        </span>


                    </div>


                </div>


                <!-- COURSE PROGRESS -->

                <div class="course-detail-progress">


                    <div class="progress-circle-content">

                        <strong>
                            80%
                        </strong>

                        <span>
                            Completed
                        </span>

                    </div>


                </div>


            </div>



            <!-- COURSE TABS -->

            <div class="course-tabs">


                <button
                    class="course-tab active"
                    data-tab="overview"
                >

                    <i class="fa-solid fa-circle-info"></i>

                    Overview

                </button>


                <button
                    class="course-tab"
                    data-tab="modules"
                >

                    <i class="fa-solid fa-layer-group"></i>

                    Modules

                </button>


                <button
                    class="course-tab"
                    data-tab="resources"
                >

                    <i class="fa-solid fa-folder-open"></i>

                    Resources

                </button>


            </div>



            <!-- =================================================
                 OVERVIEW TAB
            ================================================== -->

            <div
                class="course-tab-content active"
                id="overview"
            >


                <div class="course-overview-grid">


                    <!-- LEFT -->

                    <div class="course-overview-main">


                        <div class="content-card">


                            <h2>
                                About This Course
                            </h2>


                            <p>

                                This course is designed to provide students with
                                a comprehensive understanding of Combined Mathematics.
                                Students will study theoretical concepts, solve
                                practical problems and prepare for examinations.

                            </p>


                            <p>

                                The course contains structured modules with
                                recorded lessons, lecture notes, tutorial papers
                                and reference materials.

                            </p>


                        </div>



                        <!-- LEARNING OBJECTIVES -->

                        <div class="content-card">


                            <h2>
                                Learning Objectives
                            </h2>


                            <div class="objective-list">


                                <div class="objective-item">

                                    <i class="fa-solid fa-circle-check"></i>

                                    <span>
                                        Understand fundamental mathematical concepts.
                                    </span>

                                </div>


                                <div class="objective-item">

                                    <i class="fa-solid fa-circle-check"></i>

                                    <span>
                                        Apply mathematical theories to practical problems.
                                    </span>

                                </div>


                                <div class="objective-item">

                                    <i class="fa-solid fa-circle-check"></i>

                                    <span>
                                        Develop advanced problem-solving skills.
                                    </span>

                                </div>


                                <div class="objective-item">

                                    <i class="fa-solid fa-circle-check"></i>

                                    <span>
                                        Prepare effectively for examinations.
                                    </span>

                                </div>


                            </div>


                        </div>


                    </div>



                    <!-- RIGHT -->

                    <div class="course-overview-sidebar">


                        <div class="content-card">


                            <h3>
                                Course Information
                            </h3>


                            <div class="course-info-row">


                                <span>
                                    Instructor
                                </span>


                                <strong>
                                    Mr. Perera
                                </strong>


                            </div>


                            <div class="course-info-row">


                                <span>
                                    Duration
                                </span>


                                <strong>
                                    6 Months
                                </strong>


                            </div>


                            <div class="course-info-row">


                                <span>
                                    Modules
                                </span>


                                <strong>
                                    12
                                </strong>


                            </div>


                            <div class="course-info-row">


                                <span>
                                    Enrollment
                                </span>


                                <strong>
                                    Active
                                </strong>


                            </div>


                        </div>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 MODULES TAB
            ================================================== -->

            <div
                class="course-tab-content"
                id="modules"
            >


                <div class="content-card">


                    <div class="module-header">


                        <div>

                            <h2>
                                Course Modules
                            </h2>

                            <p>
                                Complete each module to progress through the course.
                            </p>

                        </div>


                        <span class="module-count">
                            12 Modules
                        </span>


                    </div>



                    <!-- MODULE 1 -->

                    <div class="module-item">


                        <div class="module-number">
                            01
                        </div>


                        <div class="module-info">


                            <h3>
                                Functions and Graphs
                            </h3>


                            <p>
                                Introduction to functions, domain, range and graphs.
                            </p>


                            <div class="module-progress-bar">

                                <div
                                    style="width: 100%;"
                                ></div>

                            </div>


                            <span class="module-progress-text">
                                Completed
                            </span>


                        </div>


                        <div class="module-status completed-module">

                            <i class="fa-solid fa-circle-check"></i>

                        </div>


                        <button
                            class="module-view-btn"
                            type="button"
                        >

                            View

                        </button>


                    </div>



                    <!-- MODULE 2 -->

                    <div class="module-item">


                        <div class="module-number">
                            02
                        </div>


                        <div class="module-info">


                            <h3>
                                Differentiation
                            </h3>


                            <p>
                                Learn differentiation techniques and applications.
                            </p>


                            <div class="module-progress-bar">

                                <div
                                    style="width: 100%;"
                                ></div>

                            </div>


                            <span class="module-progress-text">
                                Completed
                            </span>


                        </div>


                        <div class="module-status completed-module">

                            <i class="fa-solid fa-circle-check"></i>

                        </div>


                        <button
                            class="module-view-btn"
                            type="button"
                        >

                            View

                        </button>


                    </div>



                    <!-- MODULE 3 -->

                    <div class="module-item">


                        <div class="module-number">
                            03
                        </div>


                        <div class="module-info">


                            <h3>
                                Integration
                            </h3>


                            <p>
                                Study integration methods and real-world applications.
                            </p>


                            <div class="module-progress-bar">

                                <div
                                    style="width: 60%;"
                                ></div>

                            </div>


                            <span class="module-progress-text">
                                60% Completed
                            </span>


                        </div>


                        <div class="module-status current-module">

                            <i class="fa-solid fa-play"></i>

                        </div>


                        <button
                            class="module-view-btn"
                            type="button"
                        >

                            Continue

                        </button>


                    </div>



                    <!-- MODULE 4 -->

                    <div class="module-item">


                        <div class="module-number">
                            04
                        </div>


                        <div class="module-info">


                            <h3>
                                Complex Numbers
                            </h3>


                            <p>
                                Introduction to complex numbers and their applications.
                            </p>


                            <div class="module-progress-bar">

                                <div
                                    style="width: 0%;"
                                ></div>

                            </div>


                            <span class="module-progress-text">
                                Not Started
                            </span>


                        </div>


                        <div class="module-status locked-module">

                            <i class="fa-solid fa-lock"></i>

                        </div>


                        <button
                            class="module-view-btn disabled-module"
                            type="button"
                        >

                            Locked

                        </button>


                    </div>


                </div>


            </div>



            <!-- =================================================
                 RESOURCES TAB
            ================================================== -->

            <div
                class="course-tab-content"
                id="resources"
            >


                <div class="content-card">


                    <div class="module-header">


                        <div>

                            <h2>
                                Learning Resources
                            </h2>

                            <p>
                                Access course notes, recordings, tutes and references.
                            </p>

                        </div>


                    </div>



                    <div class="resource-list">


                        <!-- RECORDING -->

                        <div class="resource-item">


                            <div class="resource-icon recording-icon">

                                <i class="fa-solid fa-video"></i>

                            </div>


                            <div class="resource-info">


                                <h3>
                                    Integration - Complete Lesson
                                </h3>


                                <p>
                                    Lesson Recording • 1 hour 25 minutes
                                </p>


                            </div>


                            <button
                                class="resource-action watch-btn"
                                type="button"
                            >

                                <i class="fa-solid fa-play"></i>

                                Watch

                            </button>


                        </div>



                        <!-- NOTE -->

                        <div class="resource-item">


                            <div class="resource-icon note-icon">

                                <i class="fa-solid fa-file-lines"></i>

                            </div>


                            <div class="resource-info">


                                <h3>
                                    Integration Lecture Notes
                                </h3>


                                <p>
                                    Lecture Note • PDF • 4.2 MB
                                </p>


                            </div>


                            <button
                                class="resource-action download-btn"
                                type="button"
                            >

                                <i class="fa-solid fa-download"></i>

                                Download

                            </button>


                        </div>



                        <!-- TUTE -->

                        <div class="resource-item">


                            <div class="resource-icon tute-icon">

                                <i class="fa-solid fa-book"></i>

                            </div>


                            <div class="resource-info">


                                <h3>
                                    Integration Tutorial Paper
                                </h3>


                                <p>
                                    Tutorial • PDF • 2.8 MB
                                </p>


                            </div>


                            <button
                                class="resource-action download-btn"
                                type="button"
                            >

                                <i class="fa-solid fa-download"></i>

                                Download

                            </button>


                        </div>



                        <!-- REFERENCE -->

                        <div class="resource-item">


                            <div class="resource-icon reference-icon">

                                <i class="fa-solid fa-link"></i>

                            </div>


                            <div class="resource-info">


                                <h3>
                                    Additional Reference Material
                                </h3>


                                <p>
                                    Reference • External Learning Resource
                                </p>


                            </div>


                            <button
                                class="resource-action view-resource-btn"
                                type="button"
                            >

                                <i class="fa-solid fa-arrow-up-right-from-square"></i>

                                Open

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


<!-- Course Details JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* ==========================================
           COURSE TABS
        ========================================== */


        const tabs =
            document.querySelectorAll(
                ".course-tab"
            );


        const tabContents =
            document.querySelectorAll(
                ".course-tab-content"
            );


        tabs.forEach(
            function (tab) {


                tab.addEventListener(
                    "click",
                    function () {


                        const target =
                            this.getAttribute(
                                "data-tab"
                            );


                        tabs.forEach(
                            function (item) {

                                item.classList.remove(
                                    "active"
                                );

                            }
                        );


                        tabContents.forEach(
                            function (content) {

                                content.classList.remove(
                                    "active"
                                );

                            }
                        );


                        this.classList.add(
                            "active"
                        );


                        document
                            .getElementById(target)
                            .classList.add(
                                "active"
                            );


                    }
                );


            }
        );



        /* ==========================================
           RESOURCE BUTTONS
        ========================================== */


        const resourceButtons =
            document.querySelectorAll(
                ".resource-action"
            );


        resourceButtons.forEach(
            function (button) {


                button.addEventListener(
                    "click",
                    function () {


                        if (
                            this.classList.contains(
                                "watch-btn"
                            )
                        ) {

                            alert(
                                "The lesson recording player will be connected to the backend later."
                            );

                        }


                        else if (
                            this.classList.contains(
                                "download-btn"
                            )
                        ) {

                            alert(
                                "The resource download will be connected to the backend later."
                            );

                        }


                        else {

                            alert(
                                "The external resource link will be connected later."
                            );

                        }


                    }
                );


            }
        );



        /* ==========================================
           MODULE BUTTONS
        ========================================== */


        const moduleButtons =
            document.querySelectorAll(
                ".module-view-btn:not(.disabled-module)"
            );


        moduleButtons.forEach(
            function (button) {


                button.addEventListener(
                    "click",
                    function () {


                        alert(
                            "Module content will be loaded from the database later."
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