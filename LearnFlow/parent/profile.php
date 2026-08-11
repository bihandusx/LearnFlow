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
                class="nav-link active"
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
                class="nav-link"
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

                        NF

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            Nimal Fernando
                        </span>

                        <span class="user-role">
                            Parent
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

                        NF

                    </div>


                    <div>


                        <h1>
                            Nimal Fernando
                        </h1>


                        <p>
                            Parent ID: LF2026P001
                        </p>


                        <span class="profile-status">

                            <i class="fa-solid fa-circle"></i>

                            Active Parent

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
                                value="Nimal Fernando"
                                disabled
                            >


                        </div>



                        <!-- Parent ID -->

                        <div class="profile-form-group">


                            <label for="parentId">
                                Parent ID
                            </label>


                            <input
                                type="text"
                                id="parentId"
                                value="LF2026P001"
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
                                value="nimal.fernando@example.com"
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
                                value="+94 77 987 6543"
                                disabled
                            >


                        </div>



                        <!-- Relationship to Student -->

                        <div class="profile-form-group">


                            <label for="relationship">
                                Relationship to Student
                            </label>


                            <select
                                id="relationship"
                                disabled
                            >

                                <option selected>
                                    Father
                                </option>

                                <option>
                                    Mother
                                </option>

                                <option>
                                    Guardian
                                </option>

                            </select>


                        </div>



                        <!-- NIC Number -->

                        <div class="profile-form-group">


                            <label for="nic">
                                NIC Number
                            </label>


                            <input
                                type="text"
                                id="nic"
                                value="782451234V"
                                disabled
                            >


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



            <!-- LINKED STUDENT INFORMATION -->

            <div class="profile-card">


                <div class="profile-card-header">


                    <div>


                        <h3>
                            Linked Student Information
                        </h3>


                        <p>
                            Details of the student linked to your parent account.
                        </p>


                    </div>


                </div>



                <div class="academic-info-grid">


                    <div class="academic-info-item">


                        <span class="academic-label">
                            Student Name
                        </span>


                        <strong>
                            Alex Silva
                        </strong>


                    </div>



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


                </div>


            </div>



        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


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
                         Parent ID is not editable
                         because it identifies the account.
                        */

                        if (field.id !== "parentId") {

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
