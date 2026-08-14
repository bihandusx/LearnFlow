<?php

session_start();

include "../config/db.php";

if (
    !isset($_SESSION['user_id'], $_SESSION['role'])
    || $_SESSION['role'] !== 'Parent'
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION['user_id'];

$ensureParent = $conn->prepare("INSERT IGNORE INTO parent (ParentID) VALUES (?)");
$ensureParent->bind_param("i", $parentId);
$ensureParent->execute();
$ensureParent->close();

$parentStmt = $conn->prepare(
    "SELECT u.UserID, u.Name
     FROM users u
     INNER JOIN parent p ON p.ParentID = u.UserID
     WHERE u.UserID = ? AND u.Role = 'Parent'
     LIMIT 1"
);
$parentStmt->bind_param("i", $parentId);
$parentStmt->execute();
$parentResult = $parentStmt->get_result();
$parent = $parentResult ? $parentResult->fetch_assoc() : null;
$parentStmt->close();

if (!$parent) {
    header("Location: ../auth/login.php");
    exit();
}

$studentStmt = $conn->prepare(
    "SELECT s.StudentID, su.Name AS StudentName
     FROM parent_student ps
     INNER JOIN student s ON s.StudentID = ps.StudentID
     INNER JOIN users su ON su.UserID = s.StudentID
     WHERE ps.ParentID = ?
     LIMIT 1"
);
$studentStmt->bind_param("i", $parentId);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$linkedStudent = $studentResult ? $studentResult->fetch_assoc() : null;
$studentStmt->close();

function parent_initials($name)
{
    $parts = preg_split('/\s+/', trim((string) $name));
    $initials = '';

    if (!empty($parts[0])) {
        $initials .= strtoupper(substr($parts[0], 0, 1));
    }

    if (count($parts) > 1 && !empty($parts[count($parts) - 1])) {
        $initials .= strtoupper(substr($parts[count($parts) - 1], 0, 1));
    }

    return $initials !== '' ? $initials : 'P';
}

function announcements_course_slug($courseName)
{
    $slug = strtolower(trim((string) $courseName));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'course';
}

function announcements_course_icon($courseName)
{
    $name = strtolower((string) $courseName);

    if ($name === '' || $name === 'general') {
        return 'fa-bullhorn';
    }
    if (strpos($name, 'math') !== false) {
        return 'fa-calculator';
    }
    if (strpos($name, 'physics') !== false) {
        return 'fa-atom';
    }
    if (strpos($name, 'chemistry') !== false) {
        return 'fa-flask';
    }
    if (strpos($name, 'english') !== false) {
        return 'fa-language';
    }
    if (strpos($name, 'web') !== false || strpos($name, 'develop') !== false) {
        return 'fa-code';
    }

    return 'fa-book';
}

function announcements_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$studentFirstName = 'Student';
$announcementCards = [];
$courseOptions = [];
$urgentCount = 0;

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = announcements_first_name($linkedStudent['StudentName']);

    $enrollStmt = $conn->prepare(
        "SELECT c.CourseID, c.CourseName
         FROM enrollment e
         INNER JOIN batch b ON b.BatchID = e.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         WHERE e.StudentID = ?
         ORDER BY c.CourseName ASC"
    );
    $enrollStmt->bind_param("i", $studentId);
    $enrollStmt->execute();
    $enrollResult = $enrollStmt->get_result();

    while ($row = $enrollResult->fetch_assoc()) {
        $slug = announcements_course_slug($row['CourseName']);
        $courseOptions[$slug] = $row['CourseName'];
    }
    $enrollStmt->close();

    $annStmt = $conn->prepare(
        "SELECT a.Title, a.Content, a.PublishDate, a.Priority, a.BatchID,
                c.CourseID, c.CourseName
         FROM announcement a
         LEFT JOIN batch b ON b.BatchID = a.BatchID
         LEFT JOIN course c ON c.CourseID = b.CourseID
         WHERE a.BatchID IS NULL
            OR a.BatchID IN (
                SELECT e.BatchID FROM enrollment e WHERE e.StudentID = ?
            )
         ORDER BY a.PublishDate DESC, a.AnnouncementID DESC"
    );
    $annStmt->bind_param("i", $studentId);
    $annStmt->execute();
    $annResult = $annStmt->get_result();

    while ($row = $annResult->fetch_assoc()) {
        $isGeneral = $row['BatchID'] === null || $row['CourseName'] === null;
        $courseName = $isGeneral ? 'General' : $row['CourseName'];
        $courseSlug = $isGeneral ? 'general' : announcements_course_slug($courseName);
        $priorityLabel = trim((string) ($row['Priority'] ?? ''));
        if ($priorityLabel === '' || $priorityLabel === '0') {
            $priorityLabel = 'Normal';
        }
        $prioritySlug = strtolower($priorityLabel);
        $isUrgent = strcasecmp($priorityLabel, 'Urgent') === 0;
        if ($isUrgent) {
            $urgentCount++;
        }

        $date = strtotime((string) $row['PublishDate']);

        $announcementCards[] = [
            'title' => $row['Title'],
            'content' => $row['Content'],
            'date_label' => $date ? date('j M Y', $date) : '',
            'course_name' => $courseName,
            'course_slug' => $courseSlug,
            'course_icon' => announcements_course_icon($courseName),
            'priority_label' => $priorityLabel,
            'priority_slug' => $prioritySlug,
            'is_urgent' => $isUrgent,
        ];
    }

    $annStmt->close();
}

$hasRecords = count($announcementCards) > 0;
$totalCount = count($announcementCards);
$studentFirstNameSafe = htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8');

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Announcements | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Announcements</h2>

                        <p>
                            Stay updated with the latest teacher announcements.
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

                        <?php echo htmlspecialchars($parentInitials, ENT_QUOTES, 'UTF-8'); ?>

                    </div>


                    <div class="user-info">

                        <span class="user-name">
                            <?php echo htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8'); ?>
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

            <div class="announcements-page-header">


                <div>

                    <h1>
                        Teacher Announcements
                    </h1>

                    <p>
                        Updates and notices shared by <?php echo $studentFirstNameSafe; ?>'s teachers and the institute.
                    </p>

                </div>


                <div class="announcements-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $totalCount; ?>
                        </strong>

                        <span>
                            Total
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $urgentCount; ?>
                        </strong>

                        <span>
                            Urgent
                        </span>

                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="announcements-filter-card">


                <div class="announcements-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="announcementSearch"
                        placeholder="Search announcements..."
                    >


                </div>


                <select
                    id="courseFilter"
                    class="announcements-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <option value="general">
                        General
                    </option>

                    <?php foreach ($courseOptions as $slug => $courseName): ?>

                    <option value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?>
                    </option>

                    <?php endforeach; ?>

                </select>



                <select
                    id="priorityFilter"
                    class="announcements-filter"
                >

                    <option value="all">
                        All Priorities
                    </option>

                    <option value="urgent">
                        Urgent
                    </option>

                    <option value="normal">
                        Normal
                    </option>

                </select>


            </div>



            <!-- ANNOUNCEMENTS LIST -->

            <div
                class="announcements-list"
                id="announcementsList"
                <?php echo $hasRecords ? '' : 'style="display: none;"'; ?>
            >

                <?php foreach ($announcementCards as $card): ?>

                <div
                    class="announcement-card"
                    data-course="<?php echo htmlspecialchars($card['course_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-priority="<?php echo htmlspecialchars($card['priority_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-title="<?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?>"
                    data-search="<?php echo htmlspecialchars($card['title'] . ' ' . $card['content'], ENT_QUOTES, 'UTF-8'); ?>"
                >


                    <div class="announcement-icon<?php echo $card['is_urgent'] ? ' urgent-icon' : ''; ?>">

                        <i class="fa-solid <?php echo htmlspecialchars($card['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                    </div>


                    <div class="announcement-body">


                        <div class="announcement-top">

                            <div class="announcement-title-row">

                                <h3>
                                    <?php echo htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8'); ?>
                                </h3>

                            </div>

                            <span class="announcement-date">
                                <?php echo htmlspecialchars($card['date_label'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>

                        </div>


                        <span class="announcement-course-tag">

                            <i class="fa-solid <?php echo htmlspecialchars($card['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                            <?php echo htmlspecialchars($card['course_name'], ENT_QUOTES, 'UTF-8'); ?>

                        </span>


                        <p class="announcement-text">

                            <?php echo htmlspecialchars($card['content'], ENT_QUOTES, 'UTF-8'); ?>

                        </p>


                        <span class="priority-badge <?php echo $card['is_urgent'] ? 'priority-urgent' : 'priority-normal'; ?>">
                            <?php echo htmlspecialchars($card['priority_label'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>


                    </div>


                </div>

                <?php endforeach; ?>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noAnnouncements"
                class="no-announcements"
                <?php echo $hasRecords ? '' : 'style="display: flex;"'; ?>
            >

                <i class="fa-solid fa-bullhorn"></i>

                <h3>
                    No Announcements Found
                </h3>

                <p>
                    <?php echo $hasRecords
                        ? 'Try changing your search or filter options.'
                        : ($linkedStudent
                            ? 'There are no announcements to show yet.'
                            : 'No student is linked to this account.'); ?>
                </p>

            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Announcements JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById("announcementSearch");


        const courseFilter =
            document.getElementById("courseFilter");


        const priorityFilter =
            document.getElementById("priorityFilter");


        const announcementCards =
            document.querySelectorAll(".announcement-card");


        const announcementsList =
            document.getElementById("announcementsList");


        const noAnnouncements =
            document.getElementById("noAnnouncements");


        const emptyMessage =
            noAnnouncements.querySelector("p");


        const hasServerRecords =
            announcementCards.length > 0;



        function filterAnnouncements() {


            const searchValue =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const selectedCourse =
                courseFilter.value;


            const selectedPriority =
                priorityFilter.value;


            let visibleCount = 0;



            announcementCards.forEach(
                function (card) {


                    const title =
                        (
                            card.getAttribute("data-search") ||
                            card.getAttribute("data-title") ||
                            ""
                        ).toLowerCase();


                    const course =
                        card.getAttribute("data-course");


                    const priority =
                        card.getAttribute("data-priority");


                    const matchesSearch =
                        searchValue === "" ||
                        title.includes(searchValue);


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesPriority =
                        selectedPriority === "all" ||
                        priority === selectedPriority;



                    if (
                        matchesSearch &&
                        matchesCourse &&
                        matchesPriority
                    ) {

                        card.style.display = "flex";

                        visibleCount++;

                    }

                    else {

                        card.style.display = "none";

                    }


                }
            );



            if (!hasServerRecords || visibleCount === 0) {

                announcementsList.style.display = "none";

                noAnnouncements.style.display = "flex";

                emptyMessage.textContent = hasServerRecords
                    ? "Try changing your search or filter options."
                    : emptyMessage.textContent;

            }

            else {

                announcementsList.style.display = "";

                noAnnouncements.style.display = "none";

            }


        }



        searchInput.addEventListener("input", filterAnnouncements);


        courseFilter.addEventListener("change", filterAnnouncements);


        priorityFilter.addEventListener("change", filterAnnouncements);


    }
);


</script>


</body>

</html>
