<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id'], $_SESSION['role']) ||
    $_SESSION['role'] !== 'Student'
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
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

function shortText(?string $text, int $length = 120): string
{
    $text = trim((string) $text);

    if ($text === '') {
        return '';
    }

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length - 3) . '...';
}


/*
|--------------------------------------------------------------------------
| LOAD STUDENT PROFILE
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        u.fullname,
        u.email,
        u.status,
        s.registration_no

    FROM user_accounts u

    INNER JOIN students s
        ON s.student_id = u.user_id

    WHERE
        u.user_id = ?
        AND u.role = 'Student'

    LIMIT 1
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$student = $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


if (!$student) {
    session_unset();
    session_destroy();

    header("Location: ../auth/login.php");
    exit();
}


$studentName = $student['fullname'];


/*
|--------------------------------------------------------------------------
| ACTIVE / COMPLETED ENROLLED COURSES
|--------------------------------------------------------------------------
|
| Count distinct courses because a student may have historical enrollments
| in more than one batch belonging to the same course.
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(DISTINCT b.course_id) AS enrolled_courses

    FROM enrollments e

    INNER JOIN batches b
        ON b.batch_id = e.batch_id

    WHERE
        e.student_id = ?
        AND e.enrollment_status IN
        (
            'Active',
            'Completed'
        )
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$enrolledCourseCount =
    (int) (
        $stmt
        ->get_result()
        ->fetch_assoc()['enrolled_courses']
        ?? 0
    );

$stmt->close();


/*
|--------------------------------------------------------------------------
| PENDING ASSIGNMENTS
|--------------------------------------------------------------------------
|
| A pending assignment is:
| - published
| - belongs to an actively enrolled batch
| - still accepts submissions
| - has no submission from this student yet
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS pending_assignments

    FROM assessments a

    INNER JOIN assignments ass
        ON ass.assignment_id = a.assessment_id

    WHERE
        a.assessment_type = 'assignment'
        AND a.status = 'published'

        AND
        (
            a.open_date IS NULL
            OR a.open_date <= NOW()
        )

        AND
        (
            a.close_date IS NULL
            OR a.close_date >= NOW()
        )

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            WHERE
                e.student_id = ?
                AND e.batch_id = a.batch_id
                AND e.enrollment_status = 'Active'
        )

        AND
        (
            ass.due_date >= NOW()
            OR ass.allow_late_submission = 1
        )

        AND NOT EXISTS
        (
            SELECT 1

            FROM assignment_submissions sub

            WHERE
                sub.assignment_id = a.assessment_id
                AND sub.student_id = ?
        )
");

$stmt->bind_param(
    "ii",
    $studentId,
    $studentId
);

$stmt->execute();

$pendingAssignmentCount =
    (int) (
        $stmt
        ->get_result()
        ->fetch_assoc()['pending_assignments']
        ?? 0
    );

$stmt->close();


/*
|--------------------------------------------------------------------------
| AVAILABLE QUIZZES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS available_quizzes

    FROM assessments a

    INNER JOIN quizzes q
        ON q.quiz_id = a.assessment_id

    WHERE
        a.assessment_type = 'quiz'
        AND a.status = 'published'

        AND
        (
            a.open_date IS NULL
            OR a.open_date <= NOW()
        )

        AND
        (
            a.close_date IS NULL
            OR a.close_date >= NOW()
        )

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            WHERE
                e.student_id = ?
                AND e.batch_id = a.batch_id
                AND e.enrollment_status = 'Active'
        )

        AND
        (
            SELECT COUNT(*)

            FROM assessment_attempts aa

            WHERE
                aa.assessment_id = a.assessment_id
                AND aa.student_id = ?
        ) < q.attempt_limit
");

$stmt->bind_param(
    "ii",
    $studentId,
    $studentId
);

$stmt->execute();

$availableQuizCount =
    (int) (
        $stmt
        ->get_result()
        ->fetch_assoc()['available_quizzes']
        ?? 0
    );

$stmt->close();


/*
|--------------------------------------------------------------------------
| UPCOMING EXAMS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS upcoming_exams

    FROM assessments a

    INNER JOIN exams ex
        ON ex.exam_id = a.assessment_id

    WHERE
        a.assessment_type = 'exam'
        AND a.status = 'published'

        AND TIMESTAMP(
            ex.exam_date,
            ex.end_time
        ) >= NOW()

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            WHERE
                e.student_id = ?
                AND e.batch_id = a.batch_id
                AND e.enrollment_status = 'Active'
        )
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$upcomingExamCount =
    (int) (
        $stmt
        ->get_result()
        ->fetch_assoc()['upcoming_exams']
        ?? 0
    );

$stmt->close();


/*
|--------------------------------------------------------------------------
| UNREAD NOTIFICATIONS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS unread_notifications

    FROM notifications

    WHERE
        user_id = ?
        AND is_read = 0
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$unreadNotificationCount =
    (int) (
        $stmt
        ->get_result()
        ->fetch_assoc()['unread_notifications']
        ?? 0
    );

$stmt->close();


/*
|--------------------------------------------------------------------------
| OVERALL MODULE PROGRESS
|--------------------------------------------------------------------------
|
| Modules not yet accessed count as 0%.
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        COALESCE(
            AVG(
                COALESCE(
                    smp.progress_percentage,
                    0
                )
            ),
            0
        ) AS overall_progress

    FROM modules m

    LEFT JOIN student_module_progress smp
        ON smp.module_id = m.module_id
        AND smp.student_id = ?

    WHERE EXISTS
    (
        SELECT 1

        FROM enrollments e

        INNER JOIN batches b
            ON b.batch_id = e.batch_id

        WHERE
            e.student_id = ?
            AND b.course_id = m.course_id
            AND e.enrollment_status IN
            (
                'Active',
                'Completed'
            )
    )
");

$stmt->bind_param(
    "ii",
    $studentId,
    $studentId
);

$stmt->execute();

$overallProgress =
    round(
        (float) (
            $stmt
            ->get_result()
            ->fetch_assoc()['overall_progress']
            ?? 0
        ),
        1
    );

$stmt->close();


/*
|--------------------------------------------------------------------------
| ATTENDANCE RATE
|--------------------------------------------------------------------------
|
| Excused sessions are excluded from the denominator.
| Present + Late are treated as attended.
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT

        SUM(
            CASE
                WHEN a.attendance_status IN ('present', 'late')
                THEN 1
                ELSE 0
            END
        ) AS attended_count,

        SUM(
            CASE
                WHEN a.attendance_status IN ('present', 'late', 'absent')
                THEN 1
                ELSE 0
            END
        ) AS counted_sessions

    FROM attendance a

    WHERE
        a.student_id = ?
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$attendanceSummary =
    $stmt
    ->get_result()
    ->fetch_assoc();

$stmt->close();


$attendedCount =
    (int) (
        $attendanceSummary['attended_count']
        ?? 0
    );

$countedSessions =
    (int) (
        $attendanceSummary['counted_sessions']
        ?? 0
    );

$attendanceRate =
    $countedSessions > 0
        ? round(
            ($attendedCount / $countedSessions) * 100,
            1
        )
        : 0;


/*
|--------------------------------------------------------------------------
| NEXT CLASSES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        cs.session_id,
        cs.class_date,
        cs.start_time,
        cs.end_time,
        cs.venue,
        cs.topic,

        b.batch_name,

        c.course_name,

        u.fullname AS teacher_name

    FROM class_sessions cs

    INNER JOIN batches b
        ON b.batch_id = cs.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    INNER JOIN user_accounts u
        ON u.user_id = cs.teacher_id

    WHERE
        cs.status = 'scheduled'

        AND TIMESTAMP(
            cs.class_date,
            cs.end_time
        ) >= NOW()

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            WHERE
                e.student_id = ?
                AND e.batch_id = cs.batch_id
                AND e.enrollment_status = 'Active'
        )

    ORDER BY
        cs.class_date,
        cs.start_time

    LIMIT 4
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$nextClasses =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| NEXT PENDING ASSIGNMENTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        a.assessment_id,
        a.title,
        ass.due_date,

        c.course_name,
        b.batch_name

    FROM assessments a

    INNER JOIN assignments ass
        ON ass.assignment_id = a.assessment_id

    INNER JOIN batches b
        ON b.batch_id = a.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    WHERE
        a.assessment_type = 'assignment'
        AND a.status = 'published'

        AND
        (
            a.open_date IS NULL
            OR a.open_date <= NOW()
        )

        AND
        (
            a.close_date IS NULL
            OR a.close_date >= NOW()
        )

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            WHERE
                e.student_id = ?
                AND e.batch_id = a.batch_id
                AND e.enrollment_status = 'Active'
        )

        AND
        (
            ass.due_date >= NOW()
            OR ass.allow_late_submission = 1
        )

        AND NOT EXISTS
        (
            SELECT 1

            FROM assignment_submissions sub

            WHERE
                sub.assignment_id = a.assessment_id
                AND sub.student_id = ?
        )

    ORDER BY
        ass.due_date ASC

    LIMIT 4
");

$stmt->bind_param(
    "ii",
    $studentId,
    $studentId
);

$stmt->execute();

$pendingAssignments =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| UPCOMING EXAM LIST
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        a.assessment_id,
        a.title,

        ex.exam_date,
        ex.start_time,
        ex.end_time,
        ex.venue,

        c.course_name,
        b.batch_name

    FROM assessments a

    INNER JOIN exams ex
        ON ex.exam_id = a.assessment_id

    INNER JOIN batches b
        ON b.batch_id = a.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    WHERE
        a.assessment_type = 'exam'
        AND a.status = 'published'

        AND TIMESTAMP(
            ex.exam_date,
            ex.end_time
        ) >= NOW()

        AND EXISTS
        (
            SELECT 1

            FROM enrollments e

            WHERE
                e.student_id = ?
                AND e.batch_id = a.batch_id
                AND e.enrollment_status = 'Active'
        )

    ORDER BY
        ex.exam_date,
        ex.start_time

    LIMIT 3
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$upcomingExams =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| RECENT ANNOUNCEMENTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        an.announcement_id,
        an.title,
        an.content,
        an.posted_at,
        an.batch_id,

        c.course_name,
        b.batch_name,

        u.fullname AS author_name

    FROM announcements an

    LEFT JOIN batches b
        ON b.batch_id = an.batch_id

    LEFT JOIN courses c
        ON c.course_id = b.course_id

    INNER JOIN user_accounts u
        ON u.user_id = an.author_user_id

    WHERE
        an.status = 'published'

        AND
        (
            an.expiry_date IS NULL
            OR an.expiry_date >= NOW()
        )

        AND
        (
            an.batch_id IS NULL

            OR EXISTS
            (
                SELECT 1

                FROM enrollments e

                WHERE
                    e.student_id = ?
                    AND e.batch_id = an.batch_id
                    AND e.enrollment_status IN
                    (
                        'Active',
                        'Completed'
                    )
            )
        )

    ORDER BY
        an.posted_at DESC

    LIMIT 4
");

$stmt->bind_param(
    "i",
    $studentId
);

$stmt->execute();

$recentAnnouncements =
    $stmt
    ->get_result()
    ->fetch_all(MYSQLI_ASSOC);

$stmt->close();


/*
|--------------------------------------------------------------------------
| PENDING TASK TOTAL
|--------------------------------------------------------------------------
*/

$pendingTaskCount =
    $pendingAssignmentCount
    + $availableQuizCount
    + $upcomingExamCount;

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
        | Student Dashboard
    </title>

    <link
        rel="stylesheet"
        href="../css/student.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
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
                class="menu-link active"
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
                class="menu-link"
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

                <?php if ($unreadNotificationCount > 0): ?>

                    <span
                        style="
                            margin-left:auto;
                            min-width:22px;
                            height:22px;
                            display:inline-flex;
                            align-items:center;
                            justify-content:center;
                            border-radius:999px;
                            background:#ef4444;
                            color:#ffffff;
                            font-size:11px;
                            font-weight:700;
                            padding:0 6px;
                        "
                    >

                        <?php
                        echo $unreadNotificationCount > 99
                            ? '99+'
                            : $unreadNotificationCount;
                        ?>

                    </span>

                <?php endif; ?>

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
         CONTENT AREA
         ===================================================== -->

    <div class="content-area">


        <!-- =================================================
             TOPBAR
             ================================================= -->

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
                        Dashboard
                    </p>


                    <h1>

                        Welcome,
                        <?php echo e($studentName); ?>!

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
                    title="<?php
                    echo $unreadNotificationCount;
                    ?> unread notification(s)"
                    style="position:relative;"
                >

                    <i class="fas fa-bell"></i>


                    <?php if ($unreadNotificationCount > 0): ?>

                        <span
                            style="
                                position:absolute;
                                top:-6px;
                                right:-6px;
                                min-width:18px;
                                height:18px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                padding:0 5px;
                                border-radius:999px;
                                background:#ef4444;
                                color:#ffffff;
                                font-size:10px;
                                font-weight:700;
                            "
                        >

                            <?php
                            echo $unreadNotificationCount > 99
                                ? '99+'
                                : $unreadNotificationCount;
                            ?>

                        </span>

                    <?php endif; ?>


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



        <!-- =================================================
             MAIN DASHBOARD
             ================================================= -->

        <main class="dashboard-main">


            <!-- =================================================
                 OVERVIEW CARDS
                 ================================================= -->

            <section class="overview-cards">


                <article class="summary-card">


                    <div>


                        <p class="card-label">
                            Welcome back
                        </p>


                        <h2>
                            Ready to continue your learning journey?
                        </h2>


                        <p
                            style="
                                margin-top:8px;
                                opacity:.86;
                            "
                        >

                            <?php
                            echo e(
                                $student['registration_no']
                            );
                            ?>

                        </p>


                    </div>


                    <div class="summary-icon">

                        <i class="fas fa-user-graduate"></i>

                    </div>


                </article>



                <article class="stat-card">


                    <div>


                        <p class="card-label">
                            Enrolled Courses
                        </p>


                        <h3>
                            <?php echo $enrolledCourseCount; ?>
                        </h3>


                    </div>


                    <a
                        href="courses.php"
                        class="stat-badge"
                    >
                        View courses
                    </a>


                </article>



                <article class="stat-card">


                    <div>


                        <p class="card-label">
                            Pending Tasks
                        </p>


                        <h3>
                            <?php echo $pendingTaskCount; ?>
                        </h3>


                    </div>


                    <a
                        href="assignments.php"
                        class="stat-badge"
                    >
                        Review now
                    </a>


                </article>


            </section>



            <!-- =================================================
                 ACADEMIC SNAPSHOT
                 ================================================= -->

            <section class="card-grid">


                <a
                    href="progress.php"
                    class="dashboard-card"
                >


                    <div class="card-icon bg-cyan">

                        <i class="fas fa-chart-line"></i>

                    </div>


                    <p class="card-label">
                        Overall Progress
                    </p>


                    <h3>
                        <?php echo e($overallProgress); ?>%
                    </h3>


                    <p>
                        Average module progress across your
                        enrolled courses.
                    </p>


                </a>



                <a
                    href="attendance.php"
                    class="dashboard-card"
                >


                    <div class="card-icon bg-green">

                        <i class="fas fa-calendar-check"></i>

                    </div>


                    <p class="card-label">
                        Attendance
                    </p>


                    <h3>
                        <?php echo e($attendanceRate); ?>%
                    </h3>


                    <p>
                        Present and late sessions counted
                        against marked classes.
                    </p>


                </a>



                <a
                    href="quizzes.php"
                    class="dashboard-card"
                >


                    <div class="card-icon bg-purple">

                        <i class="fas fa-question-circle"></i>

                    </div>


                    <p class="card-label">
                        Available Quizzes
                    </p>


                    <h3>
                        <?php echo $availableQuizCount; ?>
                    </h3>


                    <p>
                        Published quizzes currently available
                        for an attempt.
                    </p>


                </a>



                <a
                    href="exams.php"
                    class="dashboard-card"
                >


                    <div class="card-icon bg-orange">

                        <i class="fas fa-clipboard-list"></i>

                    </div>


                    <p class="card-label">
                        Upcoming Exams
                    </p>


                    <h3>
                        <?php echo $upcomingExamCount; ?>
                    </h3>


                    <p>
                        Published exams that have not
                        finished yet.
                    </p>


                </a>


            </section>



            <!-- =================================================
                 UPCOMING CLASSES
                 ================================================= -->

            <section
                class="course-detail-section"
                style="margin-top:24px;"
            >


                <div class="course-detail-header">


                    <div>

                        <p class="card-label">
                            Schedule
                        </p>

                        <h2>
                            Upcoming Classes
                        </h2>

                        <p>
                            Your next scheduled class sessions.
                        </p>

                    </div>


                    <a
                        href="schedule.php"
                        class="secondary-btn"
                    >

                        <i class="fas fa-calendar-days"></i>

                        Full Schedule

                    </a>


                </div>


                <?php if (empty($nextClasses)): ?>


                    <div class="course-empty-state">


                        <div class="course-empty-icon">

                            <i class="fas fa-calendar"></i>

                        </div>


                        <h3>
                            No Upcoming Classes
                        </h3>


                        <p>
                            There are no future scheduled classes
                            for your active batches.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="card-grid">


                        <?php foreach ($nextClasses as $class): ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-indigo">

                                    <i class="fas fa-chalkboard-teacher"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        $class['course_name']
                                    );
                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $class['topic']
                                        ?: 'Scheduled Class'
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Batch:
                                    </strong>

                                    <?php
                                    echo e(
                                        $class['batch_name']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Date:
                                    </strong>

                                    <?php
                                    echo e(
                                        $class['class_date']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Time:
                                    </strong>

                                    <?php
                                    echo e(
                                        substr(
                                            $class['start_time'],
                                            0,
                                            5
                                        )
                                    );
                                    ?>

                                    -

                                    <?php
                                    echo e(
                                        substr(
                                            $class['end_time'],
                                            0,
                                            5
                                        )
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Teacher:
                                    </strong>

                                    <?php
                                    echo e(
                                        $class['teacher_name']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Venue:
                                    </strong>

                                    <?php
                                    echo e(
                                        $class['venue']
                                        ?: 'Not specified'
                                    );
                                    ?>

                                </p>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>



            <!-- =================================================
                 PENDING ASSIGNMENTS
                 ================================================= -->

            <section
                class="course-detail-section"
                style="margin-top:24px;"
            >


                <div class="course-detail-header">


                    <div>

                        <p class="card-label">
                            Tasks
                        </p>

                        <h2>
                            Pending Assignments
                        </h2>

                        <p>
                            Assignments that have not yet been submitted.
                        </p>

                    </div>


                    <a
                        href="assignments.php"
                        class="secondary-btn"
                    >

                        <i class="fas fa-file-alt"></i>

                        All Assignments

                    </a>


                </div>


                <?php if (empty($pendingAssignments)): ?>


                    <div class="course-empty-state">


                        <div class="course-empty-icon">

                            <i class="fas fa-circle-check"></i>

                        </div>


                        <h3>
                            No Pending Assignments
                        </h3>


                        <p>
                            You have no currently pending assignments.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="card-grid">


                        <?php
                        foreach (
                            $pendingAssignments
                            as $assignment
                        ):
                        ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-purple">

                                    <i class="fas fa-file-alt"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        $assignment[
                                            'course_name'
                                        ]
                                    );
                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $assignment['title']
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Batch:
                                    </strong>

                                    <?php
                                    echo e(
                                        $assignment[
                                            'batch_name'
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Due:
                                    </strong>

                                    <?php
                                    echo e(
                                        $assignment[
                                            'due_date'
                                        ]
                                    );
                                    ?>

                                </p>


                                <div
                                    class="form-actions"
                                    style="justify-content:flex-start;"
                                >

                                    <a
                                        href="assignment_details.php?id=<?php
                                        echo (int)
                                            $assignment[
                                                'assessment_id'
                                            ];
                                        ?>"
                                        class="primary-btn"
                                    >

                                        <i class="fas fa-eye"></i>

                                        Open Assignment

                                    </a>

                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>



            <!-- =================================================
                 UPCOMING EXAMS
                 ================================================= -->

            <section
                class="course-detail-section"
                style="margin-top:24px;"
            >


                <div class="course-detail-header">


                    <div>

                        <p class="card-label">
                            Assessments
                        </p>

                        <h2>
                            Upcoming Exams
                        </h2>

                    </div>


                    <a
                        href="exams.php"
                        class="secondary-btn"
                    >

                        <i class="fas fa-clipboard-list"></i>

                        All Exams

                    </a>


                </div>


                <?php if (empty($upcomingExams)): ?>


                    <div class="course-empty-state">


                        <div class="course-empty-icon">

                            <i class="fas fa-calendar-check"></i>

                        </div>


                        <h3>
                            No Upcoming Exams
                        </h3>


                        <p>
                            There are currently no published future exams.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="card-grid">


                        <?php foreach ($upcomingExams as $exam): ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-orange">

                                    <i class="fas fa-clipboard-list"></i>

                                </div>


                                <p class="card-label">

                                    <?php
                                    echo e(
                                        $exam['course_name']
                                    );
                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $exam['title']
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Date:
                                    </strong>

                                    <?php
                                    echo e(
                                        $exam['exam_date']
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Time:
                                    </strong>

                                    <?php
                                    echo e(
                                        substr(
                                            $exam['start_time'],
                                            0,
                                            5
                                        )
                                    );
                                    ?>

                                    -

                                    <?php
                                    echo e(
                                        substr(
                                            $exam['end_time'],
                                            0,
                                            5
                                        )
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Venue:
                                    </strong>

                                    <?php
                                    echo e(
                                        $exam['venue']
                                        ?: 'Not specified'
                                    );
                                    ?>

                                </p>


                                <div
                                    class="form-actions"
                                    style="justify-content:flex-start;"
                                >

                                    <a
                                        href="exam_attempt.php?id=<?php
                                        echo (int)
                                            $exam[
                                                'assessment_id'
                                            ];
                                        ?>"
                                        class="primary-btn"
                                    >

                                        <i class="fas fa-eye"></i>

                                        Open Exam

                                    </a>

                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>



            <!-- =================================================
                 ANNOUNCEMENTS
                 ================================================= -->

            <section
                class="course-detail-section"
                style="margin-top:24px;"
            >


                <div class="course-detail-header">


                    <div>

                        <p class="card-label">
                            Updates
                        </p>

                        <h2>
                            Recent Announcements
                        </h2>

                    </div>


                    <a
                        href="announcements.php"
                        class="secondary-btn"
                    >

                        <i class="fas fa-bullhorn"></i>

                        All Announcements

                    </a>


                </div>


                <?php if (empty($recentAnnouncements)): ?>


                    <div class="course-empty-state">


                        <div class="course-empty-icon">

                            <i class="fas fa-bullhorn"></i>

                        </div>


                        <h3>
                            No Announcements
                        </h3>


                        <p>
                            There are no current announcements for you.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="card-grid">


                        <?php
                        foreach (
                            $recentAnnouncements
                            as $announcement
                        ):
                        ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-red">

                                    <i class="fas fa-bullhorn"></i>

                                </div>


                                <p class="card-label">

                                    <?php

                                    echo e(
                                        $announcement[
                                            'course_name'
                                        ]
                                        ?: 'System'
                                    );

                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $announcement['title']
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <?php
                                    echo e(
                                        shortText(
                                            $announcement[
                                                'content'
                                            ],
                                            130
                                        )
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Posted:
                                    </strong>

                                    <?php
                                    echo e(
                                        $announcement[
                                            'posted_at'
                                        ]
                                    );
                                    ?>

                                </p>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>



            <!-- =================================================
                 STUDENT FEATURE CARDS
                 ================================================= -->

            <section
                class="card-grid"
                style="margin-top:24px;"
            >


                <a
                    href="courses.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-blue">

                        <i class="fas fa-book-open"></i>

                    </div>

                    <h3>
                        My Courses
                    </h3>

                    <p>
                        View enrolled courses and learning modules.
                    </p>

                </a>


                <a
                    href="materials.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-green">

                        <i class="fas fa-folder-open"></i>

                    </div>

                    <h3>
                        Learning Materials
                    </h3>

                    <p>
                        Access notes, tutes, recordings and resources.
                    </p>

                </a>


                <a
                    href="assignments.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-purple">

                        <i class="fas fa-file-alt"></i>

                    </div>

                    <h3>
                        Assignments
                    </h3>

                    <p>
                        View assignments and submit your work.
                    </p>

                </a>


                <a
                    href="quizzes.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-teal">

                        <i class="fas fa-question-circle"></i>

                    </div>

                    <h3>
                        Quizzes
                    </h3>

                    <p>
                        Attempt available quizzes and review attempts.
                    </p>

                </a>


                <a
                    href="exams.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-orange">

                        <i class="fas fa-clipboard-list"></i>

                    </div>

                    <h3>
                        Exams
                    </h3>

                    <p>
                        View examination schedules and attempts.
                    </p>

                </a>


                <a
                    href="grades.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-orange">

                        <i class="fas fa-chart-column"></i>

                    </div>

                    <h3>
                        Grades
                    </h3>

                    <p>
                        View results, marks and teacher feedback.
                    </p>

                </a>


                <a
                    href="progress.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-cyan">

                        <i class="fas fa-chart-line"></i>

                    </div>

                    <h3>
                        Progress
                    </h3>

                    <p>
                        Track learning progress and course completion.
                    </p>

                </a>


                <a
                    href="attendance.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-green">

                        <i class="fas fa-calendar-check"></i>

                    </div>

                    <h3>
                        Attendance
                    </h3>

                    <p>
                        Review attendance records and attendance rate.
                    </p>

                </a>


                <a
                    href="schedule.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-indigo">

                        <i class="fas fa-calendar-days"></i>

                    </div>

                    <h3>
                        Schedule
                    </h3>

                    <p>
                        View upcoming classes and examinations.
                    </p>

                </a>


                <a
                    href="discussions.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-red">

                        <i class="fas fa-comments"></i>

                    </div>

                    <h3>
                        Discussions
                    </h3>

                    <p>
                        Participate in course discussion forums.
                    </p>

                </a>


                <a
                    href="tute_store.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-purple">

                        <i class="fas fa-store"></i>

                    </div>

                    <h3>
                        Tute Store
                    </h3>

                    <p>
                        Purchase and access digital tutes.
                    </p>

                </a>


                <a
                    href="payments.php"
                    class="dashboard-card"
                >

                    <div class="card-icon bg-blue">

                        <i class="fas fa-credit-card"></i>

                    </div>

                    <h3>
                        Payments
                    </h3>

                    <p>
                        View course fees, payment history and receipts.
                    </p>

                </a>


            </section>


        </main>


    </div>


</div>


<script src="../js/student.js"></script>


</body>

</html>
