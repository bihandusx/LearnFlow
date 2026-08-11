<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Request Meeting | LEARNFLOW</title>


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
                class="nav-link"
            >

                <i class="fa-solid fa-comments"></i>

                <span>Contact Teachers/Admins</span>

            </a>


            <a
                href="meetings.php"
                class="nav-link active"
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

                        <h2>Request Meeting</h2>

                        <p>
                            Schedule a meeting with your child's teachers.
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

            <div class="meeting-page-header">


                <div>

                    <h1>
                        Request a Meeting
                    </h1>

                    <p>
                        Arrange a discussion with a teacher or the administration office.
                    </p>

                </div>


                <div class="meeting-summary">


                    <div class="summary-item">

                        <strong>
                            4
                        </strong>

                        <span>
                            Total Requests
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            2
                        </strong>

                        <span>
                            Confirmed
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            1
                        </strong>

                        <span>
                            Pending
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


                    <!-- REQUEST MEETING FORM -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                New Meeting Request
                            </h3>


                        </div>



                        <form id="meetingForm">


                            <div class="profile-form-grid">


                                <!-- TEACHER -->

                                <div class="profile-form-group full-width">


                                    <label for="teacherSelect">
                                        Teacher / Department
                                    </label>


                                    <select id="teacherSelect">

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



                                <!-- PREFERRED DATE -->

                                <div class="profile-form-group">


                                    <label for="meetingDate">
                                        Preferred Date
                                    </label>


                                    <input
                                        type="date"
                                        id="meetingDate"
                                        required
                                    >


                                </div>



                                <!-- PREFERRED TIME -->

                                <div class="profile-form-group">


                                    <label for="meetingTime">
                                        Preferred Time
                                    </label>


                                    <input
                                        type="time"
                                        id="meetingTime"
                                        required
                                    >


                                </div>



                                <!-- MODE -->

                                <div class="profile-form-group full-width">


                                    <label for="meetingMode">
                                        Meeting Mode
                                    </label>


                                    <select id="meetingMode">

                                        <option value="online">
                                            Online (Video Call)
                                        </option>

                                        <option value="in-person">
                                            In-Person at the Institute
                                        </option>

                                    </select>


                                </div>



                                <!-- REASON -->

                                <div class="profile-form-group full-width">


                                    <label for="meetingReason">
                                        Reason for Meeting
                                    </label>


                                    <textarea
                                        id="meetingReason"
                                        rows="4"
                                        placeholder="Briefly describe what you'd like to discuss..."
                                        required
                                    ></textarea>


                                </div>


                            </div>



                            <div class="compose-form-actions">


                                <button
                                    type="submit"
                                    class="profile-save-btn"
                                >

                                    <i class="fa-solid fa-handshake"></i>

                                    Send Request

                                </button>


                            </div>


                        </form>


                    </div>



                    <!-- MEETING REQUEST HISTORY -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                My Meeting Requests
                            </h3>


                        </div>



                        <div class="meeting-history-item">


                            <div class="meeting-history-icon">

                                <i class="fa-solid fa-handshake"></i>

                            </div>


                            <div class="meeting-history-info">

                                <h4>
                                    Discuss Mid-Term Performance
                                </h4>

                                <p>
                                    Mr. Perera • Requested 28 Jul 2026
                                </p>

                            </div>


                            <span class="status-badge status-confirmed">
                                Confirmed
                            </span>


                        </div>



                        <div class="meeting-history-item">


                            <div class="meeting-history-icon">

                                <i class="fa-solid fa-handshake"></i>

                            </div>


                            <div class="meeting-history-info">

                                <h4>
                                    Payment Plan Discussion
                                </h4>

                                <p>
                                    Admin Office • Requested 24 Jul 2026
                                </p>

                            </div>


                            <span class="status-badge status-confirmed">
                                Confirmed
                            </span>


                        </div>



                        <div class="meeting-history-item">


                            <div class="meeting-history-icon">

                                <i class="fa-solid fa-handshake"></i>

                            </div>


                            <div class="meeting-history-info">

                                <h4>
                                    Chemistry Lab Concerns
                                </h4>

                                <p>
                                    Dr. Fernando • Requested 20 Jul 2026
                                </p>

                            </div>


                            <span class="status-badge status-pending">
                                Pending
                            </span>


                        </div>



                        <div class="meeting-history-item">


                            <div class="meeting-history-icon">

                                <i class="fa-solid fa-handshake"></i>

                            </div>


                            <div class="meeting-history-info">

                                <h4>
                                    Extra Support for Physics
                                </h4>

                                <p>
                                    Mr. Silva • Requested 10 Jul 2026
                                </p>

                            </div>


                            <span class="status-badge status-declined">
                                Declined
                            </span>


                        </div>


                    </div>


                </div>



                <!-- RIGHT COLUMN -->

                <div>


                    <!-- UPCOMING MEETINGS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Upcoming Meetings
                            </h3>


                        </div>



                        <div class="upcoming-meeting-item">


                            <div class="meeting-date-badge">

                                <strong>06</strong>

                                <span>Aug</span>

                            </div>


                            <div class="upcoming-meeting-info">

                                <h4>
                                    Mid-Term Performance Review
                                </h4>

                                <p>

                                    <i class="fa-solid fa-user-tie"></i>

                                    Mr. Perera

                                </p>

                                <p>

                                    <i class="fa-solid fa-video"></i>

                                    Online • 4:00 PM

                                </p>

                            </div>


                        </div>



                        <div class="upcoming-meeting-item">


                            <div class="meeting-date-badge">

                                <strong>09</strong>

                                <span>Aug</span>

                            </div>


                            <div class="upcoming-meeting-info">

                                <h4>
                                    Payment Plan Discussion
                                </h4>

                                <p>

                                    <i class="fa-solid fa-user-tie"></i>

                                    Admin Office

                                </p>

                                <p>

                                    <i class="fa-solid fa-building"></i>

                                    In-Person • 10:00 AM

                                </p>

                            </div>


                        </div>



                    </div>



                    <!-- QUICK ACTIONS -->

                    <div class="dashboard-card">


                        <div class="card-header">


                            <h3>
                                Quick Actions
                            </h3>


                        </div>



                        <div class="quick-actions">


                            <a
                                href="contact.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-comments"></i>

                                Message Teacher

                            </a>


                            <a
                                href="progress.php"
                                class="quick-action"
                            >

                                <i class="fa-solid fa-chart-line"></i>

                                View Progress

                            </a>


                        </div>


                    </div>


                </div>


            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Meeting Request JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const meetingForm =
            document.getElementById("meetingForm");


        meetingForm.addEventListener(
            "submit",
            function (event) {


                event.preventDefault();


                alert(
                    "Your meeting request has been sent! The teacher will confirm the schedule shortly. (Frontend demo)"
                );


                meetingForm.reset();


            }
        );


    }
);


</script>


</body>

</html>
