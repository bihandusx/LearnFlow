<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Learning Materials | LEARNFLOW</title>


    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >


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


        <div class="sidebar-brand">

            <i class="fa-solid fa-graduation-cap"></i>

            <span>LEARNFLOW</span>

        </div>


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
                class="nav-link"
            >

                <i class="fa-solid fa-book-open"></i>

                <span>My Courses</span>

            </a>


            <a
                href="materials.php"
                class="nav-link active"
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

                        <h2>Learning Materials</h2>

                        <p>
                            Access your course resources and study materials.
                        </p>

                    </div>


                </div>


            </div>



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
             PAGE CONTENT
        ================================================== -->

        <section class="dashboard-content">


            <!-- PAGE HEADER -->

            <div class="materials-page-header">


                <div>

                    <h1>
                        My Learning Materials
                    </h1>

                    <p>
                        Find notes, tutes, recordings and reference materials
                        for your enrolled courses.
                    </p>

                </div>


                <div class="materials-summary">


                    <div class="summary-item">

                        <strong>
                            24
                        </strong>

                        <span>
                            Total Resources
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            8
                        </strong>

                        <span>
                            Recordings
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            10
                        </strong>

                        <span>
                            Notes
                        </span>

                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="materials-filter-card">


                <div class="material-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="materialSearch"
                        placeholder="Search learning materials..."
                    >


                </div>



                <select
                    id="courseFilter"
                    class="material-filter"
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

                </select>



                <select
                    id="typeFilter"
                    class="material-filter"
                >

                    <option value="all">
                        All Types
                    </option>

                    <option value="recording">
                        Recordings
                    </option>

                    <option value="note">
                        Notes
                    </option>

                    <option value="tute">
                        Tutes
                    </option>

                    <option value="reference">
                        References
                    </option>

                </select>


            </div>



            <!-- MATERIAL CARDS -->

            <div
                class="materials-grid"
                id="materialsGrid"
            >


                <!-- RECORDING -->

                <div
                    class="material-card"
                    data-course="mathematics"
                    data-type="recording"
                    data-title="Integration Complete Lesson"
                >


                    <div class="material-card-top">


                        <div class="material-type-icon recording-material">

                            <i class="fa-solid fa-video"></i>

                        </div>


                        <span class="material-type-badge recording-badge">

                            Recording

                        </span>


                    </div>


                    <div class="material-card-body">


                        <h3>
                            Integration - Complete Lesson
                        </h3>


                        <p class="material-course">

                            <i class="fa-solid fa-book"></i>

                            Combined Mathematics

                        </p>


                        <p class="material-description">

                            Complete lesson covering integration methods
                            and practical applications.

                        </p>


                        <div class="material-meta">

                            <span>

                                <i class="fa-regular fa-clock"></i>

                                1h 25m

                            </span>


                            <span>

                                <i class="fa-regular fa-calendar"></i>

                                28 Jul 2026

                            </span>

                        </div>


                    </div>


                    <div class="material-card-footer">


                        <button
                            class="material-primary-btn"
                            onclick="watchMaterial('Integration - Complete Lesson')"
                        >

                            <i class="fa-solid fa-play"></i>

                            Watch Recording

                        </button>


                    </div>


                </div>



                <!-- NOTE -->

                <div
                    class="material-card"
                    data-course="mathematics"
                    data-type="note"
                    data-title="Integration Lecture Notes"
                >


                    <div class="material-card-top">


                        <div class="material-type-icon note-material">

                            <i class="fa-solid fa-file-lines"></i>

                        </div>


                        <span class="material-type-badge note-badge">

                            Note

                        </span>


                    </div>


                    <div class="material-card-body">


                        <h3>
                            Integration Lecture Notes
                        </h3>


                        <p class="material-course">

                            <i class="fa-solid fa-book"></i>

                            Combined Mathematics

                        </p>


                        <p class="material-description">

                            Detailed lecture notes covering integration
                            concepts and formulas.

                        </p>


                        <div class="material-meta">

                            <span>

                                <i class="fa-solid fa-file-pdf"></i>

                                PDF

                            </span>


                            <span>

                                4.2 MB

                            </span>

                        </div>


                    </div>


                    <div class="material-card-footer">


                        <button
                            class="material-secondary-btn"
                            onclick="downloadMaterial('Integration Lecture Notes')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download

                        </button>


                    </div>


                </div>



                <!-- TUTE -->

                <div
                    class="material-card"
                    data-course="mathematics"
                    data-type="tute"
                    data-title="Integration Tutorial Paper"
                >


                    <div class="material-card-top">


                        <div class="material-type-icon tute-material">

                            <i class="fa-solid fa-book"></i>

                        </div>


                        <span class="material-type-badge tute-badge">

                            Tute

                        </span>


                    </div>


                    <div class="material-card-body">


                        <h3>
                            Integration Tutorial Paper
                        </h3>


                        <p class="material-course">

                            <i class="fa-solid fa-book"></i>

                            Combined Mathematics

                        </p>


                        <p class="material-description">

                            Practice questions designed to strengthen
                            integration problem-solving skills.

                        </p>


                        <div class="material-meta">

                            <span>

                                <i class="fa-solid fa-file-pdf"></i>

                                PDF

                            </span>


                            <span>

                                2.8 MB

                            </span>

                        </div>


                    </div>


                    <div class="material-card-footer">


                        <button
                            class="material-secondary-btn"
                            onclick="downloadMaterial('Integration Tutorial Paper')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download

                        </button>


                    </div>


                </div>



                <!-- REFERENCE -->

                <div
                    class="material-card"
                    data-course="physics"
                    data-type="reference"
                    data-title="Mechanics Reference Material"
                >


                    <div class="material-card-top">


                        <div class="material-type-icon reference-material">

                            <i class="fa-solid fa-link"></i>

                        </div>


                        <span class="material-type-badge reference-badge">

                            Reference

                        </span>


                    </div>


                    <div class="material-card-body">


                        <h3>
                            Mechanics Reference Material
                        </h3>


                        <p class="material-course">

                            <i class="fa-solid fa-book"></i>

                            Physics

                        </p>


                        <p class="material-description">

                            Additional external resources for understanding
                            mechanics concepts.

                        </p>


                        <div class="material-meta">

                            <span>

                                <i class="fa-solid fa-globe"></i>

                                External Resource

                            </span>

                        </div>


                    </div>


                    <div class="material-card-footer">


                        <button
                            class="material-secondary-btn"
                            onclick="openMaterial('Mechanics Reference Material')"
                        >

                            <i class="fa-solid fa-arrow-up-right-from-square"></i>

                            Open Resource

                        </button>


                    </div>


                </div>



                <!-- RECORDING -->

                <div
                    class="material-card"
                    data-course="chemistry"
                    data-type="recording"
                    data-title="Organic Chemistry Introduction"
                >


                    <div class="material-card-top">


                        <div class="material-type-icon recording-material">

                            <i class="fa-solid fa-video"></i>

                        </div>


                        <span class="material-type-badge recording-badge">

                            Recording

                        </span>


                    </div>


                    <div class="material-card-body">


                        <h3>
                            Organic Chemistry Introduction
                        </h3>


                        <p class="material-course">

                            <i class="fa-solid fa-book"></i>

                            Chemistry

                        </p>


                        <p class="material-description">

                            Introduction to organic compounds and
                            basic chemical reactions.

                        </p>


                        <div class="material-meta">

                            <span>

                                <i class="fa-regular fa-clock"></i>

                                55m

                            </span>


                            <span>

                                <i class="fa-regular fa-calendar"></i>

                                25 Jul 2026

                            </span>

                        </div>


                    </div>


                    <div class="material-card-footer">


                        <button
                            class="material-primary-btn"
                            onclick="watchMaterial('Organic Chemistry Introduction')"
                        >

                            <i class="fa-solid fa-play"></i>

                            Watch Recording

                        </button>


                    </div>


                </div>



                <!-- NOTE -->

                <div
                    class="material-card"
                    data-course="physics"
                    data-type="note"
                    data-title="Waves and Oscillations Notes"
                >


                    <div class="material-card-top">


                        <div class="material-type-icon note-material">

                            <i class="fa-solid fa-file-lines"></i>

                        </div>


                        <span class="material-type-badge note-badge">

                            Note

                        </span>


                    </div>


                    <div class="material-card-body">


                        <h3>
                            Waves and Oscillations Notes
                        </h3>


                        <p class="material-course">

                            <i class="fa-solid fa-book"></i>

                            Physics

                        </p>


                        <p class="material-description">

                            Comprehensive notes on wave motion,
                            oscillations and related concepts.

                        </p>


                        <div class="material-meta">

                            <span>

                                <i class="fa-solid fa-file-pdf"></i>

                                PDF

                            </span>


                            <span>

                                3.5 MB

                            </span>

                        </div>


                    </div>


                    <div class="material-card-footer">


                        <button
                            class="material-secondary-btn"
                            onclick="downloadMaterial('Waves and Oscillations Notes')"
                        >

                            <i class="fa-solid fa-download"></i>

                            Download

                        </button>


                    </div>


                </div>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noMaterials"
                class="no-materials"
            >

                <i class="fa-solid fa-folder-open"></i>

                <h3>
                    No Materials Found
                </h3>

                <p>
                    Try changing your search or filter options.
                </p>

            </div>


        </section>


    </main>


</div>



<script src="../js/student.js"></script>



<script>


/* =========================================================
   MATERIAL SEARCH AND FILTER
========================================================= */


const searchInput =
    document.getElementById(
        "materialSearch"
    );


const courseFilter =
    document.getElementById(
        "courseFilter"
    );


const typeFilter =
    document.getElementById(
        "typeFilter"
    );


const materialCards =
    document.querySelectorAll(
        ".material-card"
    );


const noMaterials =
    document.getElementById(
        "noMaterials"
    );



function filterMaterials() {


    const searchValue =
        searchInput.value
            .toLowerCase()
            .trim();


    const selectedCourse =
        courseFilter.value;


    const selectedType =
        typeFilter.value;


    let visibleCount = 0;



    materialCards.forEach(
        function (card) {


            const title =
                card
                    .getAttribute(
                        "data-title"
                    )
                    .toLowerCase();


            const course =
                card
                    .getAttribute(
                        "data-course"
                    );


            const type =
                card
                    .getAttribute(
                        "data-type"
                    );


            const matchesSearch =
                title.includes(
                    searchValue
                );


            const matchesCourse =
                selectedCourse === "all" ||
                course === selectedCourse;


            const matchesType =
                selectedType === "all" ||
                type === selectedType;



            if (
                matchesSearch &&
                matchesCourse &&
                matchesType
            ) {

                card.style.display =
                    "flex";

                visibleCount++;

            }

            else {

                card.style.display =
                    "none";

            }


        }
    );



    if (visibleCount === 0) {

        noMaterials.style.display =
            "flex";

    }

    else {

        noMaterials.style.display =
            "none";

    }


}



searchInput.addEventListener(
    "input",
    filterMaterials
);


courseFilter.addEventListener(
    "change",
    filterMaterials
);


typeFilter.addEventListener(
    "change",
    filterMaterials
);



/* =========================================================
   FRONTEND PLACEHOLDER ACTIONS
========================================================= */


function watchMaterial(
    materialName
) {

    alert(
        "The video player for \"" +
        materialName +
        "\" will be connected to the backend later."
    );

}



function downloadMaterial(
    materialName
) {

    alert(
        "The download for \"" +
        materialName +
        "\" will be connected to the backend later."
    );

}



function openMaterial(
    materialName
) {

    alert(
        "The external resource \"" +
        materialName +
        "\" will be connected later."
    );

}


</script>


</body>

</html>