<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Contact Teachers/Admins | LEARNFLOW</title>


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
                class="nav-link"
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
                class="nav-link active"
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

                        <h2>Contact Teachers/Admins</h2>

                        <p>
                            Reach out to your child's teachers or the administration office.
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
             PAGE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="contact-page-header">


                <div>

                    <h1>
                        Contact Teachers & Admins
                    </h1>

                    <p>
                        Send a message to any teacher or the administration office.
                    </p>

                </div>


                <div class="contact-summary">


                    <div class="summary-item">

                        <strong>
                            5
                        </strong>

                        <span>
                            Contacts
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            3
                        </strong>

                        <span>
                            Messages Sent
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            2
                        </strong>

                        <span>
                            Replied
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 CONTENT GRID
            ================================================== -->

            <div class="dashboard-grid">


                <!-- LEFT COLUMN -->

                <div>


                    <!-- COMPOSE MESSAGE -->

                    <div
                        class="dashboard-card"
                        id="composeCard"
                    >


                        <div class="card-header">


                            <h3>
                                Compose Message
                            </h3>


                        </div>



                        <form id="contactForm">


                            <div class="profile-form-grid">


                                <!-- RECIPIENT -->

                                <div class="profile-form-group full-width">


                                    <label for="recipientSelect">
                                        To
                                    </label>


                                    <select id="recipientSelect">

                                        <option value="perera-maths">
                                            Mr. Perera - Combined Mathematics
                                        </option>

                                        <option value="fernando-chemistry">
                                            Dr. Fernando - Chemistry
                                        </option>

                                        <option value="silva-physics">
                                            Mr. Silva - Physics
                                        </option>

                                        <option value="perera-english">
                                            Ms. Perera - General English
                                        </option>

                                        <option value="admin-office">
                                            Admin Office
                                        </option>

                                    </select>


                                </div>



                                <!-- SUBJECT -->

                                <div class="profile-form-group full-width">


                                    <label for="messageSubject">
                                        Subject
                                    </label>


                                    <input
                                        type="text"
                                        id="messageSubject"
                                        placeholder="Enter a subject"
                                        required
                                    >


                                </div>



                                <!-- MESSAGE -->

                                <div class="profile-form-group full-width">


                                    <label for="messageBody">
                                        Message
                                    </label>


                                    <textarea
                                        id="messageBody"
                                        rows="5"
                                        placeholder="Type your message here..."
                                        required
                                    ></textarea>


                                </div>


                            </div>



                            <div class="compose-form-actions">


                                <button
                                    type="submit"
                                    class="profile-save-btn"
                                >

                                    <i class="fa-solid fa-paper-plane"></i>

                                    Send Message

                                </button>


                            </div>


                        </form>


                    </div>



                    <!-- MESSAGE HISTORY -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Message History
                            </h3>


                        </div>



                        <div class="message-history-item">


                            <div class="message-history-icon">

                                <i class="fa-solid fa-envelope"></i>

                            </div>


                            <div class="message-history-info">

                                <h4>
                                    Question about Assignment 02
                                </h4>

                                <p>
                                    To: Mr. Perera • 30 Jul 2026
                                </p>

                            </div>


                            <span class="status-badge status-completed">
                                Replied
                            </span>


                        </div>



                        <div class="message-history-item">


                            <div class="message-history-icon">

                                <i class="fa-solid fa-envelope"></i>

                            </div>


                            <div class="message-history-info">

                                <h4>
                                    Fee Payment Confirmation
                                </h4>

                                <p>
                                    To: Admin Office • 25 Jul 2026
                                </p>

                            </div>


                            <span class="status-badge status-submitted">
                                Sent
                            </span>


                        </div>



                        <div class="message-history-item">


                            <div class="message-history-icon">

                                <i class="fa-solid fa-envelope"></i>

                            </div>


                            <div class="message-history-info">

                                <h4>
                                    Lab Session Clarification
                                </h4>

                                <p>
                                    To: Dr. Fernando • 20 Jul 2026
                                </p>

                            </div>


                            <span class="status-badge status-completed">
                                Replied
                            </span>


                        </div>


                    </div>


                </div>



                <!-- RIGHT COLUMN -->

                <div>


                    <!-- CONTACT DIRECTORY -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Teachers & Admins
                            </h3>


                        </div>



                        <div class="contact-directory-list">


                            <div class="contact-list-item">


                                <div class="contact-avatar">
                                    MP
                                </div>


                                <div class="contact-info">

                                    <h4>
                                        Mr. Perera
                                    </h4>

                                    <p>
                                        Combined Mathematics
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    class="message-btn"
                                    data-recipient="perera-maths"
                                >

                                    Message

                                </button>


                            </div>



                            <div class="contact-list-item">


                                <div class="contact-avatar">
                                    DF
                                </div>


                                <div class="contact-info">

                                    <h4>
                                        Dr. Fernando
                                    </h4>

                                    <p>
                                        Chemistry
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    class="message-btn"
                                    data-recipient="fernando-chemistry"
                                >

                                    Message

                                </button>


                            </div>



                            <div class="contact-list-item">


                                <div class="contact-avatar">
                                    MS
                                </div>


                                <div class="contact-info">

                                    <h4>
                                        Mr. Silva
                                    </h4>

                                    <p>
                                        Physics
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    class="message-btn"
                                    data-recipient="silva-physics"
                                >

                                    Message

                                </button>


                            </div>



                            <div class="contact-list-item">


                                <div class="contact-avatar">
                                    MP
                                </div>


                                <div class="contact-info">

                                    <h4>
                                        Ms. Perera
                                    </h4>

                                    <p>
                                        General English
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    class="message-btn"
                                    data-recipient="perera-english"
                                >

                                    Message

                                </button>


                            </div>



                            <div class="contact-list-item">


                                <div class="contact-avatar admin-avatar">
                                    AO
                                </div>


                                <div class="contact-info">

                                    <h4>
                                        Admin Office
                                    </h4>

                                    <p>
                                        Administration
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    class="message-btn"
                                    data-recipient="admin-office"
                                >

                                    Message

                                </button>


                            </div>


                        </div>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Contact JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const recipientSelect =
            document.getElementById("recipientSelect");


        const composeCard =
            document.getElementById("composeCard");


        const contactForm =
            document.getElementById("contactForm");


        const messageButtons =
            document.querySelectorAll(".message-btn");



        /* -------------------------
           MESSAGE A CONTACT
        ------------------------- */

        messageButtons.forEach(
            function (button) {


                button.addEventListener(
                    "click",
                    function () {


                        const recipient =
                            button.getAttribute("data-recipient");


                        recipientSelect.value = recipient;


                        composeCard.scrollIntoView(
                            {
                                behavior: "smooth"
                            }
                        );


                        document
                            .getElementById("messageSubject")
                            .focus();


                    }
                );


            }
        );



        /* -------------------------
           SEND MESSAGE
        ------------------------- */

        contactForm.addEventListener(
            "submit",
            function (event) {


                event.preventDefault();


                alert(
                    "Your message has been queued to send! (Frontend demo - will be connected to the backend later.)"
                );


                contactForm.reset();


            }
        );


    }
);


</script>


</body>

</html>
