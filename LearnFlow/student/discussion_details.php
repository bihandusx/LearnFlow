<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';
$projectName = 'LearnFlow';

$forumId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($forumId <= 0) {
    header("Location: discussions.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| MESSAGES
|--------------------------------------------------------------------------
*/

$successMessage =
    $_SESSION['discussion_success'] ?? '';

$errorMessage =
    $_SESSION['discussion_error'] ?? '';

unset(
    $_SESSION['discussion_success'],
    $_SESSION['discussion_error']
);

$flagMessages = [
    'updated' => 'Discussion post updated successfully.',
    'deleted' => 'Discussion post deleted successfully.',
    'reply_updated' => 'Reply updated successfully.',
    'reply_deleted' => 'Reply deleted successfully.'
];

foreach ($flagMessages as $flag => $message) {

    if (isset($_GET[$flag])) {
        $successMessage = $message;
    }
}


/*
|--------------------------------------------------------------------------
| LOAD FORUM
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        df.forum_id,
        df.title,
        df.description,
        df.created_at,
        df.status,

        b.batch_id,
        b.batch_name,

        c.course_id,
        c.course_name,

        e.enrollment_status

    FROM discussion_forums df

    INNER JOIN batches b
        ON b.batch_id = df.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    INNER JOIN enrollments e
        ON e.batch_id = b.batch_id

    WHERE
        df.forum_id = ?
        AND e.student_id = ?
        AND e.enrollment_status IN ('Active', 'Completed')

    LIMIT 1
");

$stmt->bind_param(
    "ii",
    $forumId,
    $userId
);

$stmt->execute();

$forum = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


if (!$forum) {
    header("Location: discussions.php");
    exit();
}


/*
|--------------------------------------------------------------------------
| PARTICIPATION PERMISSION
|--------------------------------------------------------------------------
*/

$canParticipate =
    $forum['status'] === 'open' &&
    $forum['enrollment_status'] === 'Active';


/*
|--------------------------------------------------------------------------
| CREATE POST / REPLY
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !hash_equals(
            $_SESSION['csrf_token'],
            $token
        )
    ) {

        $errorMessage =
            'Invalid request. Please refresh the page and try again.';

    } elseif (!$canParticipate) {

        $errorMessage =
            'This forum is read-only. New posts and replies are not currently allowed.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CREATE POST
            |--------------------------------------------------------------------------
            */

            if (isset($_POST['create_post'])) {

                $title =
                    trim($_POST['post_title'] ?? '');

                $content =
                    trim($_POST['post_content'] ?? '');

                if (
                    $title === '' ||
                    $content === ''
                ) {

                    throw new RuntimeException(
                        'Please enter both a post title and message.'
                    );
                }

                if (mb_strlen($title) > 180) {

                    throw new RuntimeException(
                        'Post title cannot exceed 180 characters.'
                    );
                }

                $stmt = $conn->prepare("
                    INSERT INTO discussion_posts
                    (
                        forum_id,
                        user_id,
                        title,
                        content
                    )
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iiss",
                    $forumId,
                    $userId,
                    $title,
                    $content
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['discussion_success'] =
                    'Discussion post added successfully.';

                header(
                    "Location: discussion_details.php?id=" .
                    $forumId
                );

                exit();
            }


            /*
            |--------------------------------------------------------------------------
            | CREATE REPLY
            |--------------------------------------------------------------------------
            */

            if (isset($_POST['create_reply'])) {

                $postId =
                    (int) ($_POST['post_id'] ?? 0);

                $content =
                    trim($_POST['reply_content'] ?? '');

                if (
                    $postId <= 0 ||
                    $content === ''
                ) {

                    throw new RuntimeException(
                        'Please enter a reply.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDATE POST
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT post_id
                    FROM discussion_posts

                    WHERE
                        post_id = ?
                        AND forum_id = ?

                    LIMIT 1
                ");

                $stmt->bind_param(
                    "ii",
                    $postId,
                    $forumId
                );

                $stmt->execute();

                $validPost =
                    $stmt
                        ->get_result()
                        ->num_rows === 1;

                $stmt->close();


                if (!$validPost) {

                    throw new RuntimeException(
                        'The selected discussion post is invalid.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | INSERT REPLY
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    INSERT INTO discussion_replies
                    (
                        post_id,
                        user_id,
                        content
                    )
                    VALUES (?, ?, ?)
                ");

                $stmt->bind_param(
                    "iis",
                    $postId,
                    $userId,
                    $content
                );

                $stmt->execute();
                $stmt->close();

                $_SESSION['discussion_success'] =
                    'Reply added successfully.';

                header(
                    "Location: discussion_details.php?id=" .
                    $forumId .
                    "#post-" .
                    $postId
                );

                exit();
            }

        } catch (RuntimeException $ex) {

            $errorMessage =
                $ex->getMessage();

        } catch (Throwable $ex) {

            $errorMessage =
                'The discussion operation could not be completed. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| LOAD POSTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        dp.post_id,
        dp.title,
        dp.content,
        dp.created_at,
        dp.updated_at,
        dp.user_id,

        ua.fullname AS author_name,
        ua.role AS author_role

    FROM discussion_posts dp

    INNER JOIN user_accounts ua
        ON ua.user_id = dp.user_id

    WHERE dp.forum_id = ?

    ORDER BY dp.created_at DESC
");

$stmt->bind_param(
    "i",
    $forumId
);

$stmt->execute();

$posts = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| LOAD REPLIES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        dr.reply_id,
        dr.post_id,
        dr.content,
        dr.created_at,
        dr.updated_at,
        dr.user_id,

        ua.fullname AS author_name,
        ua.role AS author_role

    FROM discussion_replies dr

    INNER JOIN discussion_posts dp
        ON dp.post_id = dr.post_id

    INNER JOIN user_accounts ua
        ON ua.user_id = dr.user_id

    WHERE dp.forum_id = ?

    ORDER BY dr.created_at ASC
");

$stmt->bind_param(
    "i",
    $forumId
);

$stmt->execute();

$allReplies = $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| GROUP REPLIES
|--------------------------------------------------------------------------
*/

$repliesByPost = [];

foreach ($allReplies as $reply) {

    $repliesByPost[
        (int) $reply['post_id']
    ][] = $reply;
}


foreach ($posts as &$post) {

    $post['replies'] =
        $repliesByPost[
            (int) $post['post_id']
        ] ?? [];
}

unset($post);


$totalPosts =
    count($posts);

$totalReplies =
    count($allReplies);

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo e($projectName); ?>
        | Discussion Details
    </title>

    <link
        rel="stylesheet"
        href="../css/student.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    >

</head>


<body>


<div class="dashboard-shell">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">


        <div class="brand-panel">


            <div class="brand-icon">

                <i class="fas fa-graduation-cap"></i>

            </div>


            <div>

                <p class="brand-label">
                    LearnFlow
                </p>

                <p class="brand-subtitle">
                    Student Portal
                </p>

            </div>


        </div>



        <nav class="sidebar-menu">


            <a
                href="dashboard.php"
                class="menu-link"
            >
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>


            <a
                href="profile.php"
                class="menu-link"
            >
                <i class="fas fa-user"></i>
                Profile
            </a>


            <a
                href="courses.php"
                class="menu-link"
            >
                <i class="fas fa-book-open"></i>
                Courses
            </a>


            <a
                href="materials.php"
                class="menu-link"
            >
                <i class="fas fa-folder-open"></i>
                Materials
            </a>


            <a
                href="assignments.php"
                class="menu-link"
            >
                <i class="fas fa-file-alt"></i>
                Assignments
            </a>


            <a
                href="quizzes.php"
                class="menu-link"
            >
                <i class="fas fa-check-square"></i>
                Quizzes
            </a>


            <a
                href="exams.php"
                class="menu-link"
            >
                <i class="fas fa-clipboard-list"></i>
                Exams
            </a>


            <a
                href="grades.php"
                class="menu-link"
            >
                <i class="fas fa-chart-column"></i>
                Grades
            </a>


            <a
                href="progress.php"
                class="menu-link"
            >
                <i class="fas fa-chart-line"></i>
                Progress
            </a>


            <a
                href="attendance.php"
                class="menu-link"
            >
                <i class="fas fa-calendar-check"></i>
                Attendance
            </a>


            <a
                href="schedule.php"
                class="menu-link"
            >
                <i class="fas fa-calendar-days"></i>
                Schedule
            </a>


            <a
                href="announcements.php"
                class="menu-link"
            >
                <i class="fas fa-bullhorn"></i>
                Announcements
            </a>


            <a
                href="discussions.php"
                class="menu-link active"
            >
                <i class="fas fa-comments"></i>
                Discussions
            </a>


            <a
                href="tute_store.php"
                class="menu-link"
            >
                <i class="fas fa-store"></i>
                Tute Store
            </a>


            <a
                href="payments.php"
                class="menu-link"
            >
                <i class="fas fa-credit-card"></i>
                Payments
            </a>


            <a
                href="notifications.php"
                class="menu-link"
            >
                <i class="fas fa-bell"></i>
                Notifications
            </a>


            <a
                href="../auth/logout.php"
                class="menu-link logout-link"
            >
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>


        </nav>


    </aside>



    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <div class="content-area">


        <!-- TOPBAR -->

        <header class="topbar">


            <div class="topbar-left">


                <button
                    class="mobile-menu-btn"
                    type="button"
                >
                    <i class="fas fa-bars"></i>
                </button>


                <div class="dashboard-title">

                    <p class="small-label">
                        Community
                    </p>

                    <h1>
                        Discussion Details
                    </h1>

                </div>


            </div>



            <div class="topbar-right">


                <div class="project-pill">
                    LearnFlow
                </div>


                <a
                    href="notifications.php"
                    class="icon-btn"
                >
                    <i class="fas fa-bell"></i>
                </a>


                <div class="profile-chip">


                    <div class="avatar-placeholder">

                        <i class="fas fa-user-circle"></i>

                    </div>


                    <div>

                        <span>
                            Hello,
                        </span>

                        <strong>
                            <?php echo e($studentName); ?>
                        </strong>

                    </div>


                </div>


            </div>


        </header>



        <main class="dashboard-main">


            <!-- SUCCESS -->

            <?php if ($successMessage !== ''): ?>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#ecfdf5;
                        color:#166534;
                        border:1px solid #bbf7d0;
                    "
                >

                    <i class="fas fa-circle-check"></i>

                    <?php echo e($successMessage); ?>

                </div>

            <?php endif; ?>



            <!-- ERROR -->

            <?php if ($errorMessage !== ''): ?>

                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#fef2f2;
                        color:#991b1b;
                        border:1px solid #fecaca;
                    "
                >

                    <i class="fas fa-circle-exclamation"></i>

                    <?php echo e($errorMessage); ?>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 OVERVIEW
                 ================================================= -->

            <section class="overview-cards">


                <article class="summary-card">

                    <div>

                        <p class="card-label">
                            <?php echo e($forum['course_name']); ?>
                        </p>

                        <h2>
                            <?php echo e($forum['title']); ?>
                        </h2>

                    </div>


                    <div class="summary-icon">

                        <i class="fas fa-comments"></i>

                    </div>

                </article>



                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Posts
                        </p>

                        <h3>
                            <?php echo $totalPosts; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        Forum posts
                    </span>

                </article>



                <article class="stat-card">

                    <div>

                        <p class="card-label">
                            Replies
                        </p>

                        <h3>
                            <?php echo $totalReplies; ?>
                        </h3>

                    </div>

                    <span class="stat-badge">
                        <?php
                        echo e(
                            ucfirst($forum['status'])
                        );
                        ?>
                    </span>

                </article>


            </section>



            <!-- =================================================
                 MODIFIED FORUM INFORMATION SECTION
                 ================================================= -->

            <section
                class="discussion-detail-section"
                style="
                    margin-top:24px;
                    background:#ffffff;
                    border:1px solid #e2e8f0;
                    border-radius:18px;
                    padding:24px;
                "
            >


                <!-- HEADER -->

                <div
                    style="
                        display:flex;
                        justify-content:space-between;
                        align-items:flex-start;
                        gap:20px;
                        margin-bottom:22px;
                    "
                >


                    <div>


                        <p
                            style="
                                margin:0 0 5px 0;
                                font-size:11px;
                                font-weight:700;
                                letter-spacing:1px;
                                text-transform:uppercase;
                                color:#2563eb;
                            "
                        >
                            Forum Information
                        </p>


                        <h2
                            style="
                                margin:0;
                                font-size:22px;
                                font-weight:700;
                                line-height:1.35;
                                color:#0f172a;
                            "
                        >
                            <?php echo e($forum['title']); ?>
                        </h2>


                        <p
                            style="
                                margin:6px 0 0 0;
                                font-size:13px;
                                color:#64748b;
                            "
                        >

                            <?php
                            echo e(
                                $forum['course_name']
                            );
                            ?>

                            <span
                                style="
                                    display:inline-block;
                                    margin:0 6px;
                                    color:#94a3b8;
                                "
                            >
                                •
                            </span>

                            <?php
                            echo e(
                                $forum['batch_name']
                            );
                            ?>

                        </p>


                    </div>



                    <a
                        href="discussions.php"
                        class="secondary-btn"
                        style="
                            flex-shrink:0;
                            text-decoration:none;
                        "
                    >

                        <i class="fas fa-arrow-left"></i>

                        Back to Forums

                    </a>


                </div>



                <!-- INFORMATION GRID -->

                <div
                    style="
                        display:grid;
                        grid-template-columns:repeat(2, minmax(0, 1fr));
                        gap:14px;
                        margin-bottom:18px;
                    "
                >


                    <!-- COURSE -->

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:14px;
                            min-height:68px;
                            padding:14px 16px;
                            border:1px solid #dbe3ee;
                            border-radius:13px;
                            background:#f8fafc;
                        "
                    >


                        <div
                            style="
                                width:38px;
                                height:38px;
                                min-width:38px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:10px;
                                background:#eff6ff;
                                color:#2563eb;
                            "
                        >

                            <i class="fas fa-book"></i>

                        </div>


                        <div style="min-width:0;">


                            <span
                                style="
                                    display:block;
                                    margin-bottom:3px;
                                    font-size:11px;
                                    font-weight:600;
                                    color:#64748b;
                                "
                            >
                                Course
                            </span>


                            <strong
                                style="
                                    display:block;
                                    font-size:14px;
                                    color:#0f172a;
                                    overflow-wrap:anywhere;
                                "
                            >
                                <?php
                                echo e(
                                    $forum['course_name']
                                );
                                ?>
                            </strong>


                        </div>


                    </div>



                    <!-- BATCH -->

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:14px;
                            min-height:68px;
                            padding:14px 16px;
                            border:1px solid #dbe3ee;
                            border-radius:13px;
                            background:#f8fafc;
                        "
                    >


                        <div
                            style="
                                width:38px;
                                height:38px;
                                min-width:38px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:10px;
                                background:#eff6ff;
                                color:#2563eb;
                            "
                        >

                            <i class="fas fa-layer-group"></i>

                        </div>


                        <div>


                            <span
                                style="
                                    display:block;
                                    margin-bottom:3px;
                                    font-size:11px;
                                    font-weight:600;
                                    color:#64748b;
                                "
                            >
                                Batch
                            </span>


                            <strong
                                style="
                                    display:block;
                                    font-size:14px;
                                    color:#0f172a;
                                "
                            >
                                <?php
                                echo e(
                                    $forum['batch_name']
                                );
                                ?>
                            </strong>


                        </div>


                    </div>



                    <!-- FORUM STATUS -->

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:14px;
                            min-height:68px;
                            padding:14px 16px;
                            border:1px solid #dbe3ee;
                            border-radius:13px;
                            background:#f8fafc;
                        "
                    >


                        <div
                            style="
                                width:38px;
                                height:38px;
                                min-width:38px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:10px;
                                background:#eff6ff;
                                color:#2563eb;
                            "
                        >

                            <i class="fas fa-toggle-on"></i>

                        </div>


                        <div>


                            <span
                                style="
                                    display:block;
                                    margin-bottom:3px;
                                    font-size:11px;
                                    font-weight:600;
                                    color:#64748b;
                                "
                            >
                                Forum Status
                            </span>


                            <strong
                                style="
                                    display:inline-flex;
                                    align-items:center;
                                    padding:4px 10px;
                                    border-radius:999px;
                                    background:#ecfdf5;
                                    color:#15803d;
                                    font-size:12px;
                                    font-weight:700;
                                "
                            >
                                <?php
                                echo e(
                                    ucfirst(
                                        $forum['status']
                                    )
                                );
                                ?>
                            </strong>


                        </div>


                    </div>



                    <!-- ENROLLMENT -->

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:14px;
                            min-height:68px;
                            padding:14px 16px;
                            border:1px solid #dbe3ee;
                            border-radius:13px;
                            background:#f8fafc;
                        "
                    >


                        <div
                            style="
                                width:38px;
                                height:38px;
                                min-width:38px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:10px;
                                background:#eff6ff;
                                color:#2563eb;
                            "
                        >

                            <i class="fas fa-user-check"></i>

                        </div>


                        <div>


                            <span
                                style="
                                    display:block;
                                    margin-bottom:3px;
                                    font-size:11px;
                                    font-weight:600;
                                    color:#64748b;
                                "
                            >
                                Enrollment
                            </span>


                            <strong
                                style="
                                    display:inline-flex;
                                    align-items:center;
                                    padding:4px 10px;
                                    border-radius:999px;
                                    background:#eff6ff;
                                    color:#1d4ed8;
                                    font-size:12px;
                                    font-weight:700;
                                "
                            >
                                <?php
                                echo e(
                                    $forum[
                                        'enrollment_status'
                                    ]
                                );
                                ?>
                            </strong>


                        </div>


                    </div>


                </div>



                <!-- ABOUT FORUM -->

                <div
                    style="
                        padding:16px 18px;
                        border-radius:13px;
                        background:#f8fafc;
                        border:1px solid #eef2f7;
                    "
                >


                    <div
                        style="
                            display:flex;
                            align-items:flex-start;
                            gap:12px;
                        "
                    >


                        <div
                            style="
                                width:36px;
                                height:36px;
                                min-width:36px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                border-radius:10px;
                                background:#eff6ff;
                                color:#2563eb;
                            "
                        >

                            <i class="fas fa-circle-info"></i>

                        </div>


                        <div>


                            <h3
                                style="
                                    margin:0 0 5px 0;
                                    font-size:14px;
                                    font-weight:700;
                                    color:#0f172a;
                                "
                            >
                                About This Forum
                            </h3>


                            <p
                                style="
                                    margin:0;
                                    font-size:13px;
                                    line-height:1.6;
                                    color:#475569;
                                "
                            >
                                <?php
                                echo nl2br(
                                    e(
                                        $forum['description']
                                        ?: 'No forum description has been added.'
                                    )
                                );
                                ?>
                            </p>


                        </div>


                    </div>


                </div>


            </section>



            <!-- =================================================
                 CREATE DISCUSSION
                 ================================================= -->

            <?php if ($canParticipate): ?>


                <section class="discussion-detail-section">


                    <div class="discussion-detail-header">


                        <div>

                            <p class="card-label">
                                Create
                            </p>

                            <h2>
                                Start a New Discussion
                            </h2>

                            <p>
                                Ask a question or start a conversation
                                with your course community.
                            </p>

                        </div>


                    </div>



                    <form
                        method="POST"
                        action="discussion_details.php?id=<?php echo $forumId; ?>"
                    >


                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php
                            echo e(
                                $_SESSION['csrf_token']
                            );
                            ?>"
                        >



                        <div class="form-group">


                            <label for="postTitle">
                                Post Title
                            </label>


                            <input
                                class="discussion-input"
                                type="text"
                                id="postTitle"
                                name="post_title"
                                maxlength="180"
                                required
                            >


                        </div>



                        <div
                            class="form-group"
                            style="margin-top:14px;"
                        >


                            <label for="postContent">
                                Message
                            </label>


                            <textarea
                                class="discussion-textarea"
                                id="postContent"
                                name="post_content"
                                required
                            ></textarea>


                        </div>



                        <div class="form-actions">


                            <button
                                type="submit"
                                name="create_post"
                                class="primary-btn"
                            >

                                <i class="fas fa-paper-plane"></i>

                                Publish Post

                            </button>


                        </div>


                    </form>


                </section>


            <?php else: ?>


                <div
                    style="
                        margin-bottom:20px;
                        padding:14px 16px;
                        border-radius:12px;
                        background:#fff7ed;
                        color:#9a3412;
                        border:1px solid #fed7aa;
                    "
                >

                    <i class="fas fa-lock"></i>

                    This forum is currently read-only.
                    You can view existing discussions,
                    but you cannot create or edit content.

                </div>


            <?php endif; ?>



            <!-- =================================================
                 POSTS
                 ================================================= -->

            <section class="discussion-detail-section">


                <div class="discussion-detail-header">


                    <div>

                        <p class="card-label">
                            Community
                        </p>

                        <h2>
                            Discussion Posts
                        </h2>

                        <p>
                            <?php echo $totalPosts; ?>
                            post(s) in this forum.
                        </p>

                    </div>


                </div>



                <?php if (empty($posts)): ?>


                    <div class="discussion-detail-empty">


                        <div class="discussion-detail-empty-icon">

                            <i class="fas fa-comment-slash"></i>

                        </div>


                        <h3>
                            No Posts Yet
                        </h3>


                        <p>
                            There are no discussion posts
                            in this forum yet.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="discussion-post-list">


                        <?php foreach ($posts as $post): ?>


                            <article
                                class="discussion-post-card"
                                id="post-<?php
                                echo (int) $post['post_id'];
                                ?>"
                            >


                                <div class="discussion-post-author-row">


                                    <div class="discussion-post-avatar">

                                        <i class="fas fa-user"></i>

                                    </div>


                                    <div class="discussion-post-author-info">


                                        <strong>
                                            <?php
                                            echo e(
                                                $post['author_name']
                                            );
                                            ?>
                                        </strong>


                                        <p>

                                            <?php
                                            echo e(
                                                $post['author_role']
                                            );
                                            ?>

                                            ·

                                            <?php
                                            echo e(
                                                $post['created_at']
                                            );
                                            ?>

                                        </p>


                                    </div>



                                    <?php
                                    if (
                                        $canParticipate &&
                                        (int) $post['user_id'] === $userId
                                    ):
                                    ?>


                                        <div
                                            style="
                                                display:flex;
                                                gap:8px;
                                                flex-wrap:wrap;
                                            "
                                        >


                                            <a
                                                class="secondary-btn"
                                                href="edit_post.php?id=<?php
                                                echo (int) $post['post_id'];
                                                ?>"
                                            >

                                                <i class="fas fa-pen"></i>

                                                Edit

                                            </a>



                                            <form
                                                method="POST"
                                                action="delete_post.php"
                                                onsubmit="return confirm('Delete this discussion post and all of its replies?');"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?php
                                                    echo e(
                                                        $_SESSION['csrf_token']
                                                    );
                                                    ?>"
                                                >


                                                <input
                                                    type="hidden"
                                                    name="post_id"
                                                    value="<?php
                                                    echo (int) $post['post_id'];
                                                    ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    class="secondary-btn"
                                                >

                                                    <i class="fas fa-trash"></i>

                                                    Delete

                                                </button>


                                            </form>


                                        </div>


                                    <?php endif; ?>


                                </div>



                                <div class="discussion-post-body">


                                    <h3>
                                        <?php
                                        echo e(
                                            $post['title']
                                        );
                                        ?>
                                    </h3>


                                    <p>
                                        <?php
                                        echo nl2br(
                                            e(
                                                $post['content']
                                            )
                                        );
                                        ?>
                                    </p>


                                </div>



                                <!-- REPLIES -->

                                <div class="discussion-reply-area">


                                    <h4>

                                        <?php
                                        echo count(
                                            $post['replies']
                                        );
                                        ?>

                                        Replies

                                    </h4>



                                    <?php
                                    foreach (
                                        $post['replies']
                                        as $reply
                                    ):
                                    ?>


                                        <div class="discussion-reply-card">


                                            <div class="discussion-post-author-row">


                                                <div class="discussion-post-avatar">

                                                    <i class="fas fa-reply"></i>

                                                </div>


                                                <div class="discussion-post-author-info">


                                                    <strong>
                                                        <?php
                                                        echo e(
                                                            $reply[
                                                                'author_name'
                                                            ]
                                                        );
                                                        ?>
                                                    </strong>


                                                    <p>

                                                        <?php
                                                        echo e(
                                                            $reply[
                                                                'author_role'
                                                            ]
                                                        );
                                                        ?>

                                                        ·

                                                        <?php
                                                        echo e(
                                                            $reply[
                                                                'created_at'
                                                            ]
                                                        );
                                                        ?>

                                                    </p>


                                                </div>



                                                <?php
                                                if (
                                                    $canParticipate &&
                                                    (int) $reply['user_id']
                                                    === $userId
                                                ):
                                                ?>


                                                    <div
                                                        style="
                                                            display:flex;
                                                            gap:8px;
                                                            flex-wrap:wrap;
                                                        "
                                                    >


                                                        <a
                                                            class="secondary-btn"
                                                            href="edit_reply.php?id=<?php
                                                            echo (int) $reply[
                                                                'reply_id'
                                                            ];
                                                            ?>"
                                                        >

                                                            <i class="fas fa-pen"></i>

                                                            Edit

                                                        </a>



                                                        <form
                                                            method="POST"
                                                            action="delete_reply.php"
                                                            onsubmit="return confirm('Delete this reply?');"
                                                        >


                                                            <input
                                                                type="hidden"
                                                                name="csrf_token"
                                                                value="<?php
                                                                echo e(
                                                                    $_SESSION[
                                                                        'csrf_token'
                                                                    ]
                                                                );
                                                                ?>"
                                                            >


                                                            <input
                                                                type="hidden"
                                                                name="reply_id"
                                                                value="<?php
                                                                echo (int) $reply[
                                                                    'reply_id'
                                                                ];
                                                                ?>"
                                                            >


                                                            <button
                                                                type="submit"
                                                                class="secondary-btn"
                                                            >

                                                                <i class="fas fa-trash"></i>

                                                                Delete

                                                            </button>


                                                        </form>


                                                    </div>


                                                <?php endif; ?>


                                            </div>



                                            <p style="margin-top:12px;">

                                                <?php
                                                echo nl2br(
                                                    e(
                                                        $reply['content']
                                                    )
                                                );
                                                ?>

                                            </p>


                                        </div>


                                    <?php endforeach; ?>



                                    <?php if ($canParticipate): ?>


                                        <form
                                            method="POST"
                                            action="discussion_details.php?id=<?php
                                            echo $forumId;
                                            ?>#post-<?php
                                            echo (int) $post['post_id'];
                                            ?>"
                                            class="discussion-reply-form"
                                        >


                                            <input
                                                type="hidden"
                                                name="csrf_token"
                                                value="<?php
                                                echo e(
                                                    $_SESSION['csrf_token']
                                                );
                                                ?>"
                                            >


                                            <input
                                                type="hidden"
                                                name="post_id"
                                                value="<?php
                                                echo (int) $post['post_id'];
                                                ?>"
                                            >


                                            <textarea
                                                class="discussion-reply-input"
                                                name="reply_content"
                                                rows="2"
                                                placeholder="Write a reply..."
                                                required
                                            ></textarea>


                                            <button
                                                type="submit"
                                                name="create_reply"
                                                class="primary-btn"
                                            >

                                                <i class="fas fa-reply"></i>

                                                Reply

                                            </button>


                                        </form>


                                    <?php endif; ?>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>


        </main>


    </div>


</div>


<script src="../js/student.js"></script>


</body>

</html>