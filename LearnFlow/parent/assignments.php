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

function assignment_course_slug($courseName)
{
    $slug = strtolower(trim((string) $courseName));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-') ?: 'course';
}

function assignment_course_icon($courseName)
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

function assignment_first_name($fullName)
{
    $parts = preg_split('/\s+/', trim((string) $fullName));
    return !empty($parts[0]) ? $parts[0] : 'Student';
}

function assignment_format_date($date)
{
    if ($date === null || $date === '') {
        return '—';
    }
    $ts = strtotime((string) $date);
    return $ts ? date('d M Y', $ts) : '—';
}

/**
 * Compute status + late flag from submission and due date.
 *
 * @return array{status:string,label:string,is_late:bool}
 */
function assignment_derive_status($dueDate, $submittedDate, $marks)
{
    $today = date('Y-m-d');
    $hasSubmission = $submittedDate !== null && $submittedDate !== '';
    $isLate = false;

    if ($hasSubmission && $dueDate !== null && $dueDate !== '' && $submittedDate > $dueDate) {
        $isLate = true;
    }

    if ($hasSubmission) {
        if ($marks === null || $marks === '') {
            return ['status' => 'submitted', 'label' => 'Submitted', 'is_late' => $isLate];
        }
        return ['status' => 'graded', 'label' => 'Graded', 'is_late' => $isLate];
    }

    if ($dueDate !== null && $dueDate !== '' && $dueDate < $today) {
        return ['status' => 'overdue', 'label' => 'Overdue', 'is_late' => false];
    }

    return ['status' => 'pending', 'label' => 'Pending', 'is_late' => false];
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$studentFirstName = 'Student';
$assignments = [];
$courseOptions = [];
$countTotal = 0;
$countPending = 0;
$countOverdue = 0;
$countGraded = 0;

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];
    $studentFirstName = assignment_first_name($linkedStudent['StudentName']);

    $assignStmt = $conn->prepare(
        "SELECT t.TestID, t.Title, t.OpenDate, t.TotalMarks,
                a.DueDate, a.AllowLateSubmission,
                c.CourseID, c.CourseName,
                s.SubmittedDate, s.Marks
         FROM assignment a
         INNER JOIN test t ON t.TestID = a.TestID
         INNER JOIN batch b ON b.BatchID = t.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         INNER JOIN enrollment e ON e.BatchID = b.BatchID AND e.StudentID = ?
         LEFT JOIN assignment_submission s
           ON s.TestID = a.TestID AND s.StudentID = ?
         ORDER BY a.DueDate ASC, t.Title ASC"
    );
    $assignStmt->bind_param("ii", $studentId, $studentId);
    $assignStmt->execute();
    $assignResult = $assignStmt->get_result();

    while ($row = $assignResult->fetch_assoc()) {
        $derived = assignment_derive_status(
            $row['DueDate'],
            $row['SubmittedDate'],
            $row['Marks']
        );

        $totalMarks = (int) ($row['TotalMarks'] ?? 0);
        $scorePct = null;
        if ($derived['status'] === 'graded' && $totalMarks > 0 && $row['Marks'] !== null) {
            $scorePct = (int) round(((int) $row['Marks'] / $totalMarks) * 100);
        }

        $courseSlug = assignment_course_slug($row['CourseName']);
        $courseOptions[$courseSlug] = $row['CourseName'];

        $assignments[] = [
            'title' => $row['Title'],
            'course_name' => $row['CourseName'],
            'course_slug' => $courseSlug,
            'course_icon' => assignment_course_icon($row['CourseName']),
            'open_date' => $row['OpenDate'],
            'due_date' => $row['DueDate'],
            'submitted_date' => $row['SubmittedDate'],
            'allow_late' => (int) $row['AllowLateSubmission'] === 1,
            'status' => $derived['status'],
            'status_label' => $derived['label'],
            'is_late' => $derived['is_late'],
            'score_pct' => $scorePct,
        ];

        $countTotal++;
        if ($derived['status'] === 'pending') {
            $countPending++;
        } elseif ($derived['status'] === 'overdue') {
            $countOverdue++;
        } elseif ($derived['status'] === 'graded') {
            $countGraded++;
        }
    }

    $assignStmt->close();
    asort($courseOptions);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Assignments | LEARNFLOW</title>


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
                class="nav-link active"
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

                        <h2>Assignments</h2>

                        <p>
                            Track your child's assignments, submissions and grades.
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

            <div class="assignments-page-header">


                <div>

                    <h1>
                        <?php echo htmlspecialchars($studentFirstName, ENT_QUOTES, 'UTF-8'); ?>'s Assignments
                    </h1>

                    <p>
                        Overview of assignments across all enrolled courses.
                    </p>

                </div>


                <div class="assignments-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $countTotal; ?>
                        </strong>

                        <span>
                            Total
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $countPending; ?>
                        </strong>

                        <span>
                            Pending
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $countOverdue; ?>
                        </strong>

                        <span>
                            Overdue
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $countGraded; ?>
                        </strong>

                        <span>
                            Graded
                        </span>

                    </div>


                </div>


            </div>



            <?php if (!$linkedStudent): ?>

                <div
                    id="noAssignments"
                    class="no-assignments"
                    style="display: flex;"
                >

                    <i class="fa-solid fa-folder-open"></i>

                    <h3>
                        No Student Linked
                    </h3>

                    <p>
                        Link a student to your parent account to view assignments.
                    </p>

                </div>

            <?php else: ?>


            <!-- SEARCH AND FILTERS -->

            <div class="assignments-filter-card">


                <div class="assignments-search">


                    <i class="fa-solid fa-magnifying-glass"></i>


                    <input
                        type="text"
                        id="assignmentSearch"
                        placeholder="Search assignments..."
                    >


                </div>



                <select
                    id="courseFilter"
                    class="assignments-filter"
                >

                    <option value="all">
                        All Courses
                    </option>

                    <?php foreach ($courseOptions as $slug => $name): ?>
                        <option value="<?php echo htmlspecialchars($slug, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>

                </select>



                <select
                    id="statusFilter"
                    class="assignments-filter"
                >

                    <option value="all">
                        All Status
                    </option>

                    <option value="pending">
                        Pending
                    </option>

                    <option value="submitted">
                        Submitted
                    </option>

                    <option value="graded">
                        Graded
                    </option>

                    <option value="overdue">
                        Overdue
                    </option>

                </select>


            </div>



            <!-- ASSIGNMENT CARDS -->

            <div
                class="assignments-grid"
                id="assignmentsGrid"
            >

                <?php foreach ($assignments as $item): ?>

                    <div
                        class="assignment-card"
                        data-course="<?php echo htmlspecialchars($item['course_slug'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-status="<?php echo htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>"
                    >


                        <div class="assignment-card-top">

                            <div class="assignment-type-icon">

                                <i class="fa-solid fa-file-pen"></i>

                            </div>

                            <div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;">

                                <span class="status-badge status-<?php echo htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($item['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>

                                <?php if ($item['is_late']): ?>
                                    <span class="status-badge status-late">
                                        Late
                                    </span>
                                <?php endif; ?>

                            </div>

                        </div>


                        <div class="assignment-card-body">

                            <h3>
                                <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                            </h3>

                            <p class="assignment-course">

                                <i class="fa-solid <?php echo htmlspecialchars($item['course_icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>

                                <?php echo htmlspecialchars($item['course_name'], ENT_QUOTES, 'UTF-8'); ?>

                            </p>

                            <p class="assignment-due">

                                <i class="fa-regular fa-calendar"></i>

                                <?php if ($item['submitted_date']): ?>
                                    Submitted: <?php echo htmlspecialchars(assignment_format_date($item['submitted_date']), ENT_QUOTES, 'UTF-8'); ?>
                                <?php else: ?>
                                    Due: <?php echo htmlspecialchars(assignment_format_date($item['due_date']), ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>

                            </p>

                            <p class="assignment-due">

                                <i class="fa-regular fa-clock"></i>

                                Opened: <?php echo htmlspecialchars(assignment_format_date($item['open_date']), ENT_QUOTES, 'UTF-8'); ?>

                            </p>

                            <p class="assignment-due">

                                <i class="fa-solid fa-hourglass-half"></i>

                                Late submissions: <?php echo $item['allow_late'] ? 'Allowed' : 'Not allowed'; ?>

                            </p>

                        </div>


                        <div class="assignment-card-footer">

                            <span class="assignment-score">
                                <?php if ($item['status'] === 'graded' && $item['score_pct'] !== null): ?>
                                    <?php echo (int) $item['score_pct']; ?>%
                                    <span>Score</span>
                                <?php elseif ($item['status'] === 'submitted'): ?>
                                    <span>Submitted — awaiting grade</span>
                                <?php elseif ($item['status'] === 'overdue'): ?>
                                    <span>Not Submitted</span>
                                <?php else: ?>
                                    <span>Awaiting Submission</span>
                                <?php endif; ?>
                            </span>

                        </div>


                    </div>

                <?php endforeach; ?>

            </div>



            <!-- NO RESULTS -->

            <div
                id="noAssignments"
                class="no-assignments"
                <?php if (count($assignments) === 0): ?>style="display: flex;"<?php endif; ?>
            >

                <i class="fa-solid fa-folder-open"></i>

                <h3>
                    No Assignments Found
                </h3>

                <p>
                    <?php if (count($assignments) === 0): ?>
                        No assignments are available for this student yet.
                    <?php else: ?>
                        Try changing your search or filter options.
                    <?php endif; ?>
                </p>

            </div>


            <?php endif; ?>


        </section>


    </main>


</div>


<script src="../js/parent.js"></script>


<!-- Assignments JavaScript -->

<script>


document.addEventListener(
    "DOMContentLoaded",
    function () {


        const searchInput =
            document.getElementById("assignmentSearch");


        const courseFilter =
            document.getElementById("courseFilter");


        const statusFilter =
            document.getElementById("statusFilter");


        const assignmentCards =
            document.querySelectorAll(".assignment-card");


        const noAssignments =
            document.getElementById("noAssignments");


        if (!searchInput || !courseFilter || !statusFilter) {
            return;
        }



        function filterAssignments() {


            const searchValue =
                searchInput.value
                    .toLowerCase()
                    .trim();


            const selectedCourse =
                courseFilter.value;


            const selectedStatus =
                statusFilter.value;


            let visibleCount = 0;



            assignmentCards.forEach(
                function (card) {


                    const title =
                        (card.getAttribute("data-title") || "")
                            .toLowerCase();


                    const course =
                        card.getAttribute("data-course");


                    const status =
                        card.getAttribute("data-status");


                    const matchesSearch =
                        title.includes(searchValue);


                    const matchesCourse =
                        selectedCourse === "all" ||
                        course === selectedCourse;


                    const matchesStatus =
                        selectedStatus === "all" ||
                        status === selectedStatus;



                    if (
                        matchesSearch &&
                        matchesCourse &&
                        matchesStatus
                    ) {

                        card.style.display = "flex";

                        visibleCount++;

                    }

                    else {

                        card.style.display = "none";

                    }


                }
            );


            if (noAssignments) {
                if (visibleCount === 0) {

                    noAssignments.style.display = "flex";

                }

                else {

                    noAssignments.style.display = "none";

                }
            }


        }



        searchInput.addEventListener("input", filterAssignments);


        courseFilter.addEventListener("change", filterAssignments);


        statusFilter.addEventListener("change", filterAssignments);


    }
);


</script>


</body>

</html>
