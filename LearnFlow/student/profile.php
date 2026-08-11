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
                class="nav-link active"
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
                class="nav-link"
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
             PROFILE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PROFILE HEADER -->

            <div class="profile-header-card">


                <div class="profile-main-info">


                    <div class="large-profile-avatar">

                        AS

                    </div>


                    <div>


                        <h1>
                            Alex Silva
                        </h1>


                        <p>
                            Student ID: LF2026001
                        </p>


                        <span class="profile-status">

                            <i class="fa-solid fa-circle"></i>

                            Active Student

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



                <form id="profileForm">


                    <div class="profile-form-grid">


                        <!-- Full Name -->

                        <div class="profile-form-group">


                            <label for="fullName">
                                Full Name
                            </label>


                            <input
                                type="text"
                                id="fullName"
                                value="Alex Silva"
                                disabled
                            >


                        </div>



                        <!-- Registration Number -->

                        <div class="profile-form-group">


                            <label for="registrationNo">
                                Registration Number
                            </label>


                            <input
                                type="text"
                                id="registrationNo"
                                value="LF2026001"
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
                                value="alex.silva@example.com"
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
                                value="+94 77 123 4567"
                                disabled
                            >


                        </div>



                        <!-- Date of Birth -->

                        <div class="profile-form-group">


                            <label for="dateOfBirth">
                                Date of Birth
                            </label>


                            <input
                                type="date"
                                id="dateOfBirth"
                                value="2007-05-15"
                                disabled
                            >


                        </div>



                        <!-- Gender -->

                        <div class="profile-form-group">


                            <label for="gender">
                                Gender
                            </label>


                            <select
                                id="gender"
                                disabled
                            >

                                <option selected>
                                    Male
                                </option>

                                <option>
                                    Female
                                </option>

                                <option>
                                    Other
                                </option>

                            </select>


                        </div>



                        <!-- Address -->

                        <div class="profile-form-group full-width">


                            <label for="address">
                                Address
                            </label>


                            <textarea
                                id="address"
                                rows="3"
                                disabled
                            >123 Main Street, Colombo, Sri Lanka</textarea>


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
                            class="profile-save-btn"
                        >

                            <i class="fa-solid fa-check"></i>

                            Save Changes

                        </button>


                    </div>


                </form>


            </div>



            <!-- ACADEMIC INFORMATION -->

            <div class="profile-card">


                <div class="profile-card-header">


                    <div>


                        <h3>
                            Academic Information
                        </h3>


                        <p>
                            Your current academic enrollment details.
                        </p>


                    </div>


                </div>



                <div class="academic-info-grid">


                    <div class="academic-info-item">


                        <span class="academic-label">
                            Student ID
                        </span>


                        <strong>
                            LF2026001
                        </strong>


                    </div>



                    <div class="academic-info-item">


                        <span class="academic-label">
                            Program
                        </span>


                        <strong>
                            G.C.E. Advanced Level
                        </strong>


                    </div>



                    <div class="academic-info-item">


                        <span class="academic-label">
                            Stream
                        </span>


                        <strong>
                            Physical Science
                        </strong>


                    </div>



                    <div class="academic-info-item">


                        <span class="academic-label">
                            Enrollment Date
                        </span>


                        <strong>
                            January 15, 2026
                        </strong>


                    </div>


                </div>


            </div>



        </section>


    </main>


</div>



<!-- Student JavaScript -->

<script src="../js/student.js"></script>


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


        /* -------------------------
           EDIT PROFILE
        ------------------------- */

        editButton.addEventListener(
            "click",
            function () {


                editableFields.forEach(
                    function (field) {

                        /*
                         Registration number is not editable
                         because it identifies the student.
                        */

                        if (field.id !== "registrationNo") {

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


                editableFields.forEach(
                    function (field) {

                        field.disabled = true;

                    }
                );


                formActions.classList.remove("show");


                editButton.style.display = "inline-flex";


            }
        );



        /* -------------------------
           SAVE PROFILE
        ------------------------- */

        profileForm.addEventListener(
            "submit",
            function (event) {


                event.preventDefault();


                editableFields.forEach(
                    function (field) {

                        field.disabled = true;

                    }
                );


                formActions.classList.remove("show");


                editButton.style.display = "inline-flex";


                alert(
                    "Profile changes saved successfully! (Frontend demo)"
                );


            }
        );


    }
);


</script>


</body>

</html>