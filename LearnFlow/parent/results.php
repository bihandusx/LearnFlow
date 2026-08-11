<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Examination Results | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Examination Results</h2>

                        <p>
                            View your child's examination performance across all subjects.
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

            <div class="results-page-header">


                <div>

                    <h1>
                        Alex's Examination Results
                    </h1>

                    <p>
                        Results from all term tests and examinations sat so far.
                    </p>

                </div>


                <div class="results-summary">


                    <div class="summary-item">

                        <strong>
                            79%
                        </strong>

                        <span>
                            Overall Average
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            English
                        </strong>

                        <span>
                            Best Subject
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            8
                        </strong>

                        <span>
                            Exams Sat
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            5 / 32
                        </strong>

                        <span>
                            Class Rank
                        </span>

                    </div>


                </div>


            </div>



            <!-- =================================================
                 SUBJECT GRADE OVERVIEW
            ================================================== -->

            <div class="progress-section">


                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Subject Grade Overview
                        </h3>


                    </div>



                    <div class="grade-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-calculator"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                Combined Mathematics
                            </h4>

                            <p>
                                Mid-Term Average: 76%
                            </p>

                        </div>


                        <div class="grade-pill grade-b">
                            B+
                        </div>


                    </div>



                    <div class="grade-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-flask"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                Chemistry
                            </h4>

                            <p>
                                Mid-Term Average: 67%
                            </p>

                        </div>


                        <div class="grade-pill grade-c">
                            B
                        </div>


                    </div>



                    <div class="grade-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-atom"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                Physics
                            </h4>

                            <p>
                                Mid-Term Average: 70%
                            </p>

                        </div>


                        <div class="grade-pill grade-b">
                            B
                        </div>


                    </div>



                    <div class="grade-item">


                        <div class="course-icon">

                            <i class="fa-solid fa-language"></i>

                        </div>


                        <div class="course-info">

                            <h4>
                                General English
                            </h4>

                            <p>
                                Mid-Term Average: 90%
                            </p>

                        </div>


                        <div class="grade-pill grade-a">
                            A
                        </div>


                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="results-filter-card">


                <select
                    id="courseFilter"
                    class="results-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <option value="mathematics">
                        Combined Mathematics
                    </option>

                    <option value="physics">
                        Physics
                    </option>

                    <option value="chemistry">
                        Chemistry
                    </option>

                    <option value="english">
                        General English
                    </option>

                </select>



                <select
                    id="examFilter"
                    class="results-filter"
                >

                    <option value="all">
                        All Exams
                    </option>

                    <option value="first-term">
                        First Term Test
                    </option>

                    <option value="mid-term">
                        Mid-Term Examination
                    </option>

                </select>


            </div>



            <!-- RESULTS TABLE -->

            <div class="results-table-card">


                <table
                    class="results-table"
                    id="resultsTable"
                >


                    <thead>

                        <tr>

                            <th>Exam</th>

                            <th>Course</th>

                            <th>Marks</th>

                            <th>Grade</th>

                            <th>Class Average</th>

                            <th>Result</th>

                        </tr>

                    </thead>


                    <tbody>


                        <tr
                            class="results-row"
                            data-course="mathematics"
                            data-exam="first-term"
                        >

                            <td>First Term Test</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-calculator"></i>

                                    Combined Mathematics

                                </span>

                            </td>

                            <td>72%</td>

                            <td>B</td>

                            <td>65%</td>

                            <td>

                                <span class="status-badge status-pass">
                                    Pass
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="results-row"
                            data-course="mathematics"
                            data-exam="mid-term"
                        >

                            <td>Mid-Term Examination</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-calculator"></i>

                                    Combined Mathematics

                                </span>

                            </td>

                            <td>80%</td>

                            <td>A</td>

                            <td>68%</td>

                            <td>

                                <span class="status-badge status-distinction">
                                    Distinction
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="results-row"
                            data-course="chemistry"
                            data-exam="first-term"
                        >

                            <td>First Term Test</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-flask"></i>

                                    Chemistry

                                </span>

                            </td>

                            <td>70%</td>

                            <td>B+</td>

                            <td>60%</td>

                            <td>

                                <span class="status-badge status-pass">
                                    Pass
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="results-row"
                            data-course="chemistry"
                            data-exam="mid-term"
                        >

                            <td>Mid-Term Examination</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-flask"></i>

                                    Chemistry

                                </span>

                            </td>

                            <td>65%</td>

                            <td>B</td>

                            <td>62%</td>

                            <td>

                                <span class="status-badge status-pass">
                                    Pass
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="results-row"
                            data-course="physics"
                            data-exam="first-term"
                        >

                            <td>First Term Test</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-atom"></i>

                                    Physics

                                </span>

                            </td>

                            <td>68%</td>

                            <td>B</td>

                            <td>63%</td>

                            <td>

                                <span class="status-badge status-pass">
                                    Pass
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="results-row"
                            data-course="physics"
                            data-exam="mid-term"
                        >

                            <td>Mid-Term Examination</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-atom"></i>

                                    Physics

                                </span>

                            </td>

                            <td>72%</td>

                            <td>B+</td>

                            <td>64%</td>

                            <td>

                                <span class="status-badge status-pass">
                                    Pass
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="results-row"
                            data-course="english"
                            data-exam="first-term"
                        >

                            <td>First Term Test</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-language"></i>

                                    General English

                                </span>

                            </td>

                            <td>88%</td>

                            <td>A</td>

                            <td>70%</td>

                            <td>

                                <span class="status-badge status-distinction">
                                    Distinction
                                </span>

                            </td>

                        </tr>


                        <tr
                            class="results-row"
                            data-course="english"
                            data-exam="mid-term"
                        >

                            <td>Mid-Term Examination</td>

                            <td>

                                <span class="results-course">

                                    <i class="fa-solid fa-language"></i>

                                    General English

                                </span>

                            </td>

                            <td>91%</td>

                            <td>A</td>

                            <td>72%</td>

                            <td>

                                <span class="status-badge status-distinction">
                                    Distinction
                                </span>

                            </td>

                        </tr>


                    </tbody>


                </table>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noResults"
                class="no-results"
            >

                <i class="fa-solid fa-award"></i>

                <h3>
                    No Results Found
                </h3>

                <p>
                    Try changing your filter options.
                </p>

            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Results JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const courseFilter =
            document.getElementById("courseFilter");


        const examFilter =
            document.getElementById("examFilter");


        const resultRows =
            document.querySelectorAll(".results-row");


        const resultsTableCard =
            document.querySelector(".results-table-card");


        const noResults =
            document.getElementById("noResults");



        function filterResults() {


            const selectedCourse =
                courseFilter.value;


            const selectedExam =
                examFilter.value;


            let visibleCount = 0;



            resultRows.forEach(
                function (row) {


                    const course =
                        row.getAttribute("data-course");


                    const exam =
                        row.getAttribute("data-exam");


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesExam =
                        selectedExam === "all" ||
                        exam === selectedExam;



                    if (matchesCourse && matchesExam) {

                        row.style.display = "";

                        visibleCount++;

                    }

                    else {

                        row.style.display = "none";

                    }


                }
            );



            if (visibleCount === 0) {

                resultsTableCard.style.display = "none";

                noResults.style.display = "flex";

            }

            else {

                resultsTableCard.style.display = "block";

                noResults.style.display = "none";

            }


        }



        courseFilter.addEventListener("change", filterResults);


        examFilter.addEventListener("change", filterResults);


    }
);


</script>


</body>

</html>
