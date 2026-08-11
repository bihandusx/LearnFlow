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

function contact_format_date($datetime)
{
    $ts = strtotime((string) $datetime);
    return $ts ? date('d M Y', $ts) : '';
}

function contact_role_label($role)
{
    $role = trim((string) $role);
    if ($role === 'Admin') {
        return 'Admin';
    }
    if ($role === 'Teacher') {
        return 'Teacher';
    }
    return $role !== '' ? $role : 'Contact';
}

$parentName = $parent['Name'];
$parentInitials = parent_initials($parentName);

$contacts = [];
$allowedRecipientIds = [];
$messages = [];
$flashError = '';
$flashSuccess = '';
$selectedMessageId = isset($_GET['msg']) ? (int) $_GET['msg'] : 0;

if ($linkedStudent) {
    $studentId = (int) $linkedStudent['StudentID'];

    // Teachers of enrolled courses (via course.TeacherID)
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
                'role' => 'Teacher',
                'role_label' => 'Teacher',
                'subtitle' => trim((string) ($row['CourseName'] ?? 'Teacher')),
                'is_admin' => false,
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

    // All admins
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
                'role' => 'Admin',
                'role_label' => 'Admin',
                'subtitle' => 'Admin',
                'is_admin' => true,
            ];
            $allowedRecipientIds[$uid] = true;
        }
    }

    // Compose / send
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $recipientId = (int) ($_POST['recipient_id'] ?? 0);
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));

        if ($recipientId <= 0 || !isset($allowedRecipientIds[$recipientId])) {
            $flashError = 'Please select a valid teacher or admin.';
        } elseif ($subject === '' || $body === '') {
            $flashError = 'Subject and message are required.';
        } else {
            $subjectStore = substr($subject, 0, 150);
            $ins = $conn->prepare(
                "INSERT INTO contact_message
                   (SenderID, RecipientID, StudentID, Subject, Body, SentDate, InReplyToMessageID)
                 VALUES (?, ?, ?, ?, ?, NOW(), NULL)"
            );
            $ins->bind_param("iiiss", $parentId, $recipientId, $studentId, $subjectStore, $body);
            if ($ins->execute()) {
                $newId = (int) $conn->insert_id;
                $ins->close();
                header('Location: contact.php?msg=' . $newId . '&sent=1');
                exit();
            }
            $flashError = 'Unable to send message. Please try again.';
            $ins->close();
        }
    }

    if (isset($_GET['sent']) && (string) $_GET['sent'] === '1') {
        $flashSuccess = 'Your message has been sent.';
    }

    // Root messages for this parent
    $msgStmt = $conn->prepare(
        "SELECT m.MessageID, m.Subject, m.Body, m.SentDate, m.RecipientID,
                ru.Name AS RecipientName, ru.Role AS RecipientRole,
                (SELECT r.MessageID FROM contact_message r
                  WHERE r.InReplyToMessageID = m.MessageID
                  ORDER BY r.SentDate ASC
                  LIMIT 1) AS ReplyMessageID
         FROM contact_message m
         INNER JOIN users ru ON ru.UserID = m.RecipientID
         WHERE m.SenderID = ?
           AND m.InReplyToMessageID IS NULL
         ORDER BY m.SentDate DESC, m.MessageID DESC"
    );
    $msgStmt->bind_param("i", $parentId);
    $msgStmt->execute();
    $msgResult = $msgStmt->get_result();

    while ($row = $msgResult->fetch_assoc()) {
        $isReplied = !empty($row['ReplyMessageID']);
        $messages[] = [
            'message_id' => (int) $row['MessageID'],
            'subject' => (string) $row['Subject'],
            'body' => (string) $row['Body'],
            'sent_date' => $row['SentDate'],
            'sent_label' => contact_format_date($row['SentDate']),
            'recipient_id' => (int) $row['RecipientID'],
            'recipient_name' => (string) $row['RecipientName'],
            'recipient_role' => contact_role_label($row['RecipientRole']),
            'is_replied' => $isReplied,
            'status_label' => $isReplied ? 'Replied' : 'Sent',
            'status_class' => $isReplied ? 'status-completed' : 'status-submitted',
        ];
    }
    $msgStmt->close();
}

$contactCount = count($contacts);
$messagesSent = count($messages);
$repliedCount = 0;
foreach ($messages as $m) {
    if ($m['is_replied']) {
        $repliedCount++;
    }
}

$selectedMessage = null;
if ($messages) {
    if ($selectedMessageId > 0) {
        foreach ($messages as $m) {
            if ($m['message_id'] === $selectedMessageId) {
                $selectedMessage = $m;
                break;
            }
        }
    }
    if ($selectedMessage === null) {
        $selectedMessage = $messages[0];
        $selectedMessageId = $selectedMessage['message_id'];
    }
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

            <div class="contact-page-header">


                <div>

                    <h1>
                        Contact Teachers & Admins
                    </h1>

                    <p>
                        <?php if ($linkedStudent): ?>
                            Send a message about
                            <?php echo htmlspecialchars($linkedStudent['StudentName'], ENT_QUOTES, 'UTF-8'); ?>
                            to a teacher or the administration office.
                        <?php else: ?>
                            No linked student found for this parent account.
                        <?php endif; ?>
                    </p>

                </div>


                <div class="contact-summary">


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $contactCount; ?>
                        </strong>

                        <span>
                            Contacts
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $messagesSent; ?>
                        </strong>

                        <span>
                            Messages Sent
                        </span>

                    </div>


                    <div class="summary-item">

                        <strong>
                            <?php echo (int) $repliedCount; ?>
                        </strong>

                        <span>
                            Replied
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
                 Row 1: Compose | Teachers & Admins
                 Row 2: Message History | Message Details
            ================================================== -->

            <div class="dashboard-grid contact-page-grid">


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



                    <form
                        id="contactForm"
                        method="post"
                        action="contact.php"
                    >

                        <input
                            type="hidden"
                            name="send_message"
                            value="1"
                        >


                        <div class="profile-form-grid">


                            <!-- RECIPIENT -->

                            <div class="profile-form-group full-width">


                                <label for="recipientSelect">
                                    To
                                </label>


                                <select
                                    id="recipientSelect"
                                    name="recipient_id"
                                    required
                                    <?php echo $linkedStudent && $contacts ? '' : 'disabled'; ?>
                                >

                                    <?php if (!$contacts): ?>
                                        <option value="">
                                            No contacts available
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


                                <label for="messageSubject">
                                    Subject
                                </label>


                                <input
                                    type="text"
                                    id="messageSubject"
                                    name="subject"
                                    placeholder="Enter a subject"
                                    maxlength="150"
                                    required
                                    <?php echo $linkedStudent && $contacts ? '' : 'disabled'; ?>
                                >


                            </div>



                            <!-- MESSAGE -->

                            <div class="profile-form-group full-width">


                                <label for="messageBody">
                                    Message
                                </label>


                                <textarea
                                    id="messageBody"
                                    name="body"
                                    rows="5"
                                    placeholder="Type your message here..."
                                    required
                                    <?php echo $linkedStudent && $contacts ? '' : 'disabled'; ?>
                                ></textarea>


                            </div>


                        </div>



                        <div class="compose-form-actions">


                            <button
                                type="submit"
                                class="profile-save-btn"
                                <?php echo $linkedStudent && $contacts ? '' : 'disabled'; ?>
                            >

                                <i class="fa-solid fa-paper-plane"></i>

                                Send Message

                            </button>


                        </div>


                    </form>


                </div>



                <!-- CONTACT DIRECTORY -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Teachers & Admins
                        </h3>


                    </div>



                    <div class="contact-directory-list">


                        <?php if (!$contacts): ?>

                            <p class="contact-empty-state">
                                <?php echo $linkedStudent
                                    ? 'No teachers or admins available yet.'
                                    : 'Link a student to see contacts.'; ?>
                            </p>

                        <?php else: ?>

                            <?php foreach ($contacts as $contact): ?>

                                <div class="contact-list-item">


                                    <div class="contact-avatar<?php echo $contact['is_admin'] ? ' admin-avatar' : ''; ?>">
                                        <?php echo htmlspecialchars(parent_initials($contact['name']), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>


                                    <div class="contact-info">

                                        <h4>
                                            <?php echo htmlspecialchars($contact['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </h4>

                                        <p>
                                            <?php echo htmlspecialchars($contact['subtitle'], ENT_QUOTES, 'UTF-8'); ?>
                                        </p>

                                    </div>


                                    <button
                                        type="button"
                                        class="message-btn"
                                        data-recipient="<?php echo (int) $contact['user_id']; ?>"
                                    >

                                        Message

                                    </button>


                                </div>

                            <?php endforeach; ?>

                        <?php endif; ?>


                    </div>


                </div>



                <!-- MESSAGE HISTORY -->

                <div class="dashboard-card">


                    <div class="card-header">


                        <h3>
                            Message History
                        </h3>


                    </div>


                    <?php if (!$messages): ?>

                        <p class="contact-empty-state">
                            No messages sent yet.
                        </p>

                    <?php else: ?>

                        <?php foreach ($messages as $msg): ?>

                            <a
                                href="contact.php?msg=<?php echo (int) $msg['message_id']; ?>"
                                class="message-history-item<?php echo $selectedMessageId === $msg['message_id'] ? ' is-selected' : ''; ?>"
                            >


                                <div class="message-history-icon">

                                    <i class="fa-solid fa-envelope"></i>

                                </div>


                                <div class="message-history-info">

                                    <h4>
                                        <?php echo htmlspecialchars($msg['subject'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h4>

                                    <p>
                                        To:
                                        <?php echo htmlspecialchars($msg['recipient_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        •
                                        <?php echo htmlspecialchars($msg['sent_label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>

                                </div>


                                <span class="status-badge <?php echo htmlspecialchars($msg['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($msg['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>


                            </a>

                        <?php endforeach; ?>

                    <?php endif; ?>


                </div>



                <!-- MESSAGE DETAILS -->

                <div
                    class="dashboard-card"
                    id="messageDetailCard"
                >


                    <div class="card-header">


                        <h3>
                            Message Details
                        </h3>


                    </div>


                    <?php if (!$selectedMessage): ?>

                        <p class="contact-empty-state">
                            Select a message from history to view details.
                        </p>

                    <?php else: ?>

                        <div class="message-detail-meta">

                            <div class="message-detail-row">
                                <span class="message-detail-label">Subject</span>
                                <span class="message-detail-value">
                                    <?php echo htmlspecialchars($selectedMessage['subject'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>

                            <div class="message-detail-row">
                                <span class="message-detail-label">To</span>
                                <span class="message-detail-value">
                                    <?php
                                    echo htmlspecialchars(
                                        $selectedMessage['recipient_name'] . ' (' . $selectedMessage['recipient_role'] . ')',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </span>
                            </div>

                            <div class="message-detail-row">
                                <span class="message-detail-label">Sent</span>
                                <span class="message-detail-value">
                                    <?php echo htmlspecialchars($selectedMessage['sent_label'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </div>

                            <div class="message-detail-row">
                                <span class="message-detail-label">Status</span>
                                <span class="message-detail-value">
                                    <span class="status-badge <?php echo htmlspecialchars($selectedMessage['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($selectedMessage['status_label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="message-detail-status-date">
                                        •
                                        <?php echo htmlspecialchars($selectedMessage['sent_label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </span>
                            </div>

                        </div>

                        <div class="message-detail-body">

                            <span class="message-detail-label">Message</span>

                            <p>
                                <?php echo nl2br(htmlspecialchars($selectedMessage['body'], ENT_QUOTES, 'UTF-8')); ?>
                            </p>

                        </div>

                    <?php endif; ?>


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


                        if (!recipientSelect || recipientSelect.disabled) {
                            return;
                        }


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


    }
);


</script>


</body>

</html>
