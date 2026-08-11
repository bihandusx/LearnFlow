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

function attendance_course_slug($courseName)
{
    $slug = strtolower(trim((string) $courseName));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'course';
}

function attendance_course_icon($courseName)
{
    $name = strtolower((string) $courseName);

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

function attendance_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$attendanceRows = [];
$courseOptions = [];
$presentCount = 0;
$absentCount = 0;
$attendanceRate = 0;
$studentFirstName = 'Student';

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = attendance_first_name($linkedStudent['StudentName']);

    $attendanceStmt = $conn->prepare(
        "SELECT a.AttendanceDate, a.Status, c.CourseID, c.CourseName,
                cs.StartTime, cs.EndTime
         FROM attendance a
         INNER JOIN course c ON c.CourseID = a.CourseID
         INNER JOIN course_session cs ON cs.SessionID = a.SessionID
         WHERE a.StudentID = ?
         ORDER BY a.AttendanceDate DESC, cs.StartTime DESC"
    );
    $attendanceStmt->bind_param("i", $studentId);
    $attendanceStmt->execute();
    $attendanceResult = $attendanceStmt->get_result();

    while ($row = $attendanceResult->fetch_assoc()) {
        $status = $row['Status'] === 'Absent' ? 'Absent' : 'Present';
        if ($status === 'Present') {
            $presentCount++;
        } else {
            $absentCount++;
        }

        $courseId = (int) $row['CourseID'];
        $courseOptions[$courseId] = $row['CourseName'];

        $start = strtotime($row['StartTime']);
        $end = strtotime($row['EndTime']);
        $date = strtotime($row['AttendanceDate']);

        $attendanceRows[] = [
            'date_label' => $date ? date('d M Y', $date) : '',
            'course_name' => $row['CourseName'],
            'course_slug' => attendance_course_slug($row['CourseName']),
            'course_icon' => attendance_course_icon($row['CourseName']),
            'time_label' => ($start && $end)
                ? date('g:i A', $start) . ' - ' . date('g:i A', $end)
                : '',
            'status' => $status,
            'status_slug' => strtolower($status),
        ];
    }

    $attendanceStmt->close();

    $total = $presentCount + $absentCount;
    $attendanceRate = $total > 0 ? (int) round(($presentCount / $total) * 100) : 0;
}

$hasRecords = count($attendanceRows) > 0;
$pageTitle = htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8') . "'s Attendance";

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Student Attendance | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Attendance</h2>

                        <p>
                            Track your child's attendance across all enrolled courses.
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

            <div class="attendance-page-header">


                <div>

                    <h1>
                        <?php echo $pageTitle; ?>
                    </h1>

                    <p>
                        Overview of class attendance for all enrolled courses this term.
                    </p>

                </div>


                <div class="attendance-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo $hasRecords ? $attendanceRate . '%' : '0%'; ?>
                        </strong>

                        <span>
                            Attendance Rate
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $presentCount; ?>
                        </strong>

                        <span>
                            Present
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $absentCount; ?>
                        </strong>

                        <span>
                            Absent
                        </span>

                    </div>


                </div>


            </div>



            <!-- SEARCH AND FILTERS -->

            <div class="attendance-filter-card">


                <div class="attendance-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="attendanceSearch"
                        placeholder="Search by course..."
                    >


                </div>



                <select
                    id="courseFilter"
                    class="attendance-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <?php foreach ($courseOptions as $courseName): ?>
                        <option value="<?php echo htmlspecialchars(attendance_course_slug($courseName), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($courseName, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>

                </select>



                <select
                    id="statusFilter"
                    class="attendance-filter"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="present">
                        Present
                    </option>

                    <option value="absent">
                        Absent
                    </option>

                </select>


            </div>



            <!-- ATTENDANCE TABLE -->

            <div
                class="attendance-table-card"
                <?php echo $hasRecords ? '' : 'style="display: none;"'; ?>
            >


                <table
                    class="attendance-table"
                    id="attendanceTable"
                >


                    <thead>

                        <tr>

                            <th>Date</th>

                            <th>Course</th>

                            <th>Time</th>

                            <th>Status</th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php foreach ($attendanceRows as $row): ?>
                            <tr
                                class="attendance-row"
                                data-course="<?php echo htmlspecialchars($row['course_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                                data-status="<?php echo htmlspecialchars($row['status_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                            >

                                <td><?php echo htmlspecialchars($row['date_label'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td>

                                    <span class="attendance-course">

                                        <i class="fa-solid <?php echo htmlspecialchars($row['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                        <?php echo htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8'); ?>

                                    </span>

                                </td>

                                <td><?php echo htmlspecialchars($row['time_label'], ENT_QUOTES, 'UTF-8'); ?></td>

                                <td>

                                    <span class="status-badge status-<?php echo htmlspecialchars($row['status_slug'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>

                                </td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>


                </table>


            </div>



            <!-- NO RESULTS -->

            <div
                id="noAttendance"
                class="no-attendance"
                <?php echo $hasRecords ? '' : 'style="display: flex;"'; ?>
            >

                <i class="fa-solid fa-calendar-xmark"></i>

                <h3>
                    No Attendance Records Found
                </h3>

                <p>
                    <?php echo $hasRecords
                        ? 'Try changing your search or filter options.'
                        : 'There are no attendance records to show yet.'; ?>
                </p>

            </div>


        </section>


    </main>


</div>



<!-- Parent JavaScript -->

<script src="../js/parent.js"></script>


<!-- Attendance JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById("attendanceSearch");


        const courseFilter =
            document.getElementById("courseFilter");


        const statusFilter =
            document.getElementById("statusFilter");


        const attendanceRows =
            document.querySelectorAll(".attendance-row");


        const attendanceTableCard =
            document.querySelector(".attendance-table-card");


        const noAttendance =
            document.getElementById("noAttendance");


        const emptyMessage =
            noAttendance.querySelector("p");


        const hasServerRecords =
            attendanceRows.length > 0;



        function filterAttendance() {


            const searchValue =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const selectedCourse =
                courseFilter.value;


            const selectedStatus =
                statusFilter.value;


            let visibleCount = 0;



            attendanceRows.forEach(
                function (row) {


                    const course =
                        row.getAttribute("data-course") || "";


                    const status =
                        row.getAttribute("data-status") || "";


                    const courseText =
                        row
                            .querySelector(".attendance-course")
                            .textContent
                            .toLowerCase()
                            .trim();


                    const matchesSearch =
                        searchValue === "" ||
                        courseText.includes(searchValue);


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesStatus =
                        selectedStatus === "all" ||
                        status === selectedStatus;


                    const isVisible =
                        matchesSearch &&
                        matchesCourse &&
                        matchesStatus;


                    row.style.display =
                        isVisible ? "" : "none";


                    if (isVisible) {
                        visibleCount++;
                    }

                }
            );


            if (!hasServerRecords || visibleCount === 0) {
                attendanceTableCard.style.display = "none";
                noAttendance.style.display = "flex";
                emptyMessage.textContent = hasServerRecords
                    ? "Try changing your search or filter options."
                    : "There are no attendance records to show yet.";
            } else {
                attendanceTableCard.style.display = "";
                noAttendance.style.display = "none";
            }

        }


        searchInput.addEventListener("input", filterAttendance);


        courseFilter.addEventListener("change", filterAttendance);


        statusFilter.addEventListener("change", filterAttendance);


    }
);


</script>


</body>

</html>
