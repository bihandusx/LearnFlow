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

function meeting_format_date($date)
{
    $ts = strtotime((string) $date);
    return $ts ? date('d M Y', $ts) : '';
}

function meeting_format_time($time)
{
    $ts = strtotime((string) $time);
    return $ts ? date('g:i A', $ts) : '';
}

function meeting_status_class($status)
{
    if ($status === 'Confirmed') {
        return 'status-confirmed';
    }
    if ($status === 'Declined') {
        return 'status-declined';
    }
    return 'status-pending';
}

function meeting_mode_label($mode)
{
    return $mode === 'In-Person' ? 'In-Person' : 'Online';
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$contacts = [];
$allowedRecipientIds = [];
$meetings = [];
$upcomingMeetings = [];
$flashError = '';
$flashSuccess = '';

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];

    $teacherStmt = $conn->prepare(
        "SELECT DISTINCT u.UserID, u.Name, u.Role, c.CourseName
         FROM enrollment e
         INNER JOIN batch b ON b.BatchID = e.BatchID
         INNER JOIN course c ON c.CourseID = b.CourseID
         INNER JOIN teacher t ON t.TeacherID = c.TeacherID
         INNER JOIN users u ON u.UserID = t.TeacherID
         WHERE e.StudentID = ?
           AND c.TeacherID IS NOT NULL
         ORDER BY u.Name ASC, c.CourseName ASC"
    );
    $teacherStmt->bind_param("i", $studentId);
    $teacherStmt->execute();
    $teacherResult = $teacherStmt->get_result();

    $teachersById = [];
    while ($row = $teacherResult->fetch_assoc()) {
        $uid = (int) $row['UserID'];
        if (!isset($teachersById[$uid])) {
            $teachersById[$uid] = [
                'user_id' => $uid,
                'name' => $row['Name'],
                'subtitle' => trim((string) ($row['CourseName'] ?? 'Teacher')),
            ];
        } else {
            $courseName = trim((string) ($row['CourseName'] ?? ''));
            if ($courseName !== '' && strpos($teachersById[$uid]['subtitle'], $courseName) === false) {
                $teachersById[$uid]['subtitle'] .= ', ' . $courseName;
            }
        }
        $allowedRecipientIds[$uid] = true;
    }
    $teacherStmt->close();

    foreach ($teachersById as $t) {
        $contacts[] = $t;
    }

    $adminResult = $conn->query(
        "SELECT u.UserID, u.Name, u.Role
         FROM admin a
         INNER JOIN users u ON u.UserID = a.AdminID
         ORDER BY u.Name ASC"
    );
    if ($adminResult) {
        while ($row = $adminResult->fetch_assoc()) {
            $uid = (int) $row['UserID'];
            $contacts[] = [
                'user_id' => $uid,
                'name' => $row['Name'],
                'subtitle' => 'Admin',
            ];
            $allowedRecipientIds[$uid] = true;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_meeting'])) {
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $reason = trim((string) ($_POST['reason'] ?? ''));
        $preferredDate = trim((string) ($_POST['meeting_date'] ?? ''));
        $preferredTime = trim((string) ($_POST['meeting_time'] ?? ''));
        $modeInput = trim((string) ($_POST['meeting_mode'] ?? ''));
        $mode = $modeInput === 'in-person' ? 'In-Person' : ($modeInput === 'online' ? 'Online' : '');

        if ($recipientId <= 0 || !isset($allowedRecipientIds[$recipientId])) {
            $flashError = 'Please select a valid teacher or admin.';
        } elseif ($subject === '' || $reason === '') {
            $flashError = 'Subject and reason are required.';
        } elseif ($preferredDate === '' || $preferredTime === '' || $mode === '') {
            $flashError = 'Preferred date, time, and meeting mode are required.';
        } else {
            $subjectStore = substr($subject, 0, 150);
            $ins = $conn->prepare(
                "INSERT INTO meeting_request
                   (ParentID, StudentID, RecipientID, Subject, Reason,
                    PreferredDate, PreferredTime, Mode, Status, RequestedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())"
            );
            $ins->bind_param(
                "iiisssss",
                $parentId,
                $studentId,
                $recipientId,
                $subjectStore,
                $reason,
                $preferredDate,
                $preferredTime,
                $mode
            );
            if ($ins->execute()) {
                $ins->close();
                header('Location: meetings.php?sent=1');
                exit();
            }
            $flashError = 'Unable to send meeting request. Please try again.';
            $ins->close();
        }
    }

    if (isset($_GET['sent']) && (string) $_GET['sent'] === '1') {
        $flashSuccess = 'Your meeting request has been sent.';
    }

    $meetStmt = $conn->prepare(
        "SELECT m.MeetingRequestID, m.Subject, m.Reason, m.PreferredDate, m.PreferredTime,
                m.Mode, m.Status, m.RequestedAt, m.RecipientID,
                ru.Name AS RecipientName, ru.Role AS RecipientRole
         FROM meeting_request m
         INNER JOIN users ru ON ru.UserID = m.RecipientID
         WHERE m.ParentID = ?
         ORDER BY m.RequestedAt DESC, m.MeetingRequestID DESC"
    );
    $meetStmt->bind_param("i", $parentId);
    $meetStmt->execute();
    $meetResult = $meetStmt->get_result();

    $today = date('Y-m-d');

    while ($row = $meetResult->fetch_assoc()) {
        $status = (string) $row['Status'];
        $mode = (string) $row['Mode'];
        $item = [
            'subject' => (string) $row['Subject'],
            'reason' => (string) $row['Reason'],
            'recipient_name' => (string) $row['RecipientName'],
            'requested_label' => meeting_format_date($row['RequestedAt']),
            'preferred_date' => (string) $row['PreferredDate'],
            'preferred_time' => (string) $row['PreferredTime'],
            'preferred_time_label' => meeting_format_time($row['PreferredTime']),
            'mode' => $mode,
            'mode_label' => meeting_mode_label($mode),
            'status' => $status,
            'status_class' => meeting_status_class($status),
            'day' => $row['PreferredDate'] ? date('d', strtotime($row['PreferredDate'])) : '',
            'month' => $row['PreferredDate'] ? date('M', strtotime($row['PreferredDate'])) : '',
        ];
        $meetings[] = $item;

        if ($status === 'Confirmed' && $row['PreferredDate'] >= $today) {
            $upcomingMeetings[] = $item;
        }
    }
    $meetStmt->close();

    usort($upcomingMeetings, function ($a, $b) {
        $dateCmp = strcmp($a['preferred_date'], $b['preferred_date']);
        if ($dateCmp !== 0) {
            return $dateCmp;
        }
        return strcmp($a['preferred_time'], $b['preferred_time']);
    });
}

$totalCount = count($meetings);
$confirmedCount = 0;
$pendingCount = 0;
foreach ($meetings as $m) {
    if ($m['status'] === 'Confirmed') {
        $confirmedCount++;
    } elseif ($m['status'] === 'Pending') {
        $pendingCount++;
    }
}

$canSubmit = $linkedStudent && $contacts;

?>
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

            <div class="meeting-page-header">


                <div>

                    <h1>
                        Request a Meeting
                    </h1>

                    <p>
                        <?php if ($linkedStudent): ?>
                            Arrange a discussion about
                            <?php echo htmlspecialchars($linkedStudent['StudentName'], ENT_QUOTES, 'UTF-8'); ?>
                            with a teacher or the administration office.
                        <?php else: ?>
                            No linked student found for this parent account.
                        <?php endif; ?>
                    </p>

                </div>


                <div class="meeting-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $totalCount; ?>
                        </strong>

                        <span>
                            Total Requests
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $confirmedCount; ?>
                        </strong>

                        <span>
                            Confirmed
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $pendingCount; ?>
                        </strong>

                        <span>
                            Pending
                        </span>

                    </div>


                </div>


            </div>


            <?php if ($flashError !== ''): ?>
                <p class="contact-flash contact-flash-error">
                    <?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <?php if ($flashSuccess !== ''): ?>
                <p class="contact-flash contact-flash-success">
                    <?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>



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



                        <form
                            id="meetingForm"
                            method="post"
                            action="meetings.php"
                        >

                            <input
                                type="hidden"
                                name="send_meeting"
                                value="1"
                            >


                            <div class="profile-form-grid">


                                <!-- TEACHER -->

                                <div class="profile-form-group full-width">


                                    <label for="teacherSelect">
                                        Teacher / Department
                                    </label>


                                    <select
                                        id="teacherSelect"
                                        name="recipient_id"
                                        required
                                        <?php echo $canSubmit ? '' : 'disabled'; ?>
                                    >

                                        <?php if (!$contacts): ?>
                                            <option value="">
                                                No teachers or admins available
                                            </option>
                                        <?php else: ?>
                                            <?php foreach ($contacts as $contact): ?>
                                                <option value="<?php echo (int) $contact['user_id']; ?>">
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $contact['name'] . ' — ' . $contact['subtitle'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>

                                    </select>


                                </div>



                                <!-- SUBJECT -->

                                <div class="profile-form-group full-width">


                                    <label for="meetingSubject">
                                        Subject
                                    </label>


                                    <input
                                        type="text"
                                        id="meetingSubject"
                                        name="subject"
                                        placeholder="Enter a subject"
                                        maxlength="150"
                                        required
                                        <?php echo $canSubmit ? '' : 'disabled'; ?>
                                    >


                                </div>



                                <!-- PREFERRED DATE -->

                                <div class="profile-form-group">


                                    <label for="meetingDate">
                                        Preferred Date
                                    </label>


                                    <input
                                        type="date"
                                        id="meetingDate"
                                        name="meeting_date"
                                        required
                                        <?php echo $canSubmit ? '' : 'disabled'; ?>
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
                                        name="meeting_time"
                                        required
                                        <?php echo $canSubmit ? '' : 'disabled'; ?>
                                    >


                                </div>



                                <!-- MODE -->

                                <div class="profile-form-group full-width">


                                    <label for="meetingMode">
                                        Meeting Mode
                                    </label>


                                    <select
                                        id="meetingMode"
                                        name="meeting_mode"
                                        required
                                        <?php echo $canSubmit ? '' : 'disabled'; ?>
                                    >

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
                                        name="reason"
                                        rows="4"
                                        placeholder="Briefly describe what you'd like to discuss..."
                                        required
                                        <?php echo $canSubmit ? '' : 'disabled'; ?>
                                    ></textarea>


                                </div>


                            </div>



                            <div class="compose-form-actions">


                                <button
                                    type="submit"
                                    class="profile-save-btn"
                                    <?php echo $canSubmit ? '' : 'disabled'; ?>
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


                        <?php if (!$meetings): ?>

                            <p class="contact-empty-state">
                                No meeting requests yet.
                            </p>

                        <?php else: ?>

                            <?php foreach ($meetings as $meeting): ?>

                                <div class="meeting-history-item">


                                    <div class="meeting-history-icon">

                                        <i class="fa-solid fa-handshake"></i>

                                    </div>


                                    <div class="meeting-history-info">

                                        <h4>
                                            <?php echo htmlspecialchars($meeting['subject'], ENT_QUOTES, 'UTF-8'); ?>
                                        </h4>

                                        <p>
                                            <?php echo htmlspecialchars($meeting['recipient_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            • Requested
                                            <?php echo htmlspecialchars($meeting['requested_label'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>

                                    </div>


                                    <span class="status-badge <?php echo htmlspecialchars($meeting['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($meeting['status'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>


                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


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


                        <?php if (!$upcomingMeetings): ?>

                            <p class="contact-empty-state">
                                No upcoming confirmed meetings.
                            </p>

                        <?php else: ?>

                            <?php foreach ($upcomingMeetings as $upcoming): ?>

                                <div class="upcoming-meeting-item">


                                    <div class="meeting-date-badge">

                                        <strong>
                                            <?php echo htmlspecialchars($upcoming['day'], ENT_QUOTES, 'UTF-8'); ?>
                                        </strong>

                                        <span>
                                            <?php echo htmlspecialchars($upcoming['month'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>

                                    </div>


                                    <div class="upcoming-meeting-info">

                                        <h4>
                                            <?php echo htmlspecialchars($upcoming['subject'], ENT_QUOTES, 'UTF-8'); ?>
                                        </h4>

                                        <p>

                                            <i class="fa-solid fa-user-tie"></i>

                                            <?php echo htmlspecialchars($upcoming['recipient_name'], ENT_QUOTES, 'UTF-8'); ?>

                                        </p>

                                        <p>

                                            <i class="fa-solid <?php echo $upcoming['mode'] === 'In-Person' ? 'fa-building' : 'fa-video'; ?>"></i>

                                            <?php echo htmlspecialchars($upcoming['mode_label'], ENT_QUOTES, 'UTF-8'); ?>
                                            •
                                            <?php echo htmlspecialchars($upcoming['preferred_time_label'], ENT_QUOTES, 'UTF-8'); ?>

                                        </p>

                                    </div>


                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


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


</body>

</html>
