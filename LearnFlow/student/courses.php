<?php
session_start();
require_once "../config/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$projectName = 'LearnFlow';
$error = '';

$success = $_SESSION['course_success'] ?? '';
unset($_SESSION['course_success']);


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
$studentName = $_SESSION['name'] ?? 'Student';


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/*
|--------------------------------------------------------------------------
| ENROLLMENT ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {

        $error =
            'Invalid request. Please refresh the page and try again.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | ENROLL IN A BATCH
            |--------------------------------------------------------------------------
            */

            if (isset($_POST['enroll_batch'])) {

                $batchId =
                    (int) ($_POST['batch_id'] ?? 0);


                if ($batchId <= 0) {

                    throw new RuntimeException(
                        'Invalid batch selected.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | GET BATCH AND COURSE
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT
                        b.batch_id,
                        b.course_id,
                        b.status AS batch_status,

                        c.status AS course_status,

                        at.status AS term_status,

                        c.course_name,
                        b.batch_name

                    FROM batches b

                    INNER JOIN courses c
                        ON c.course_id = b.course_id

                    INNER JOIN academic_terms at
                        ON at.term_id = b.term_id

                    WHERE b.batch_id = ?

                    LIMIT 1
                ");


                $stmt->bind_param(
                    "i",
                    $batchId
                );


                $stmt->execute();


                $batch =
                    $stmt
                    ->get_result()
                    ->fetch_assoc();


                $stmt->close();


                if (!$batch) {

                    throw new RuntimeException(
                        'The selected batch does not exist.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDATE COURSE
                |--------------------------------------------------------------------------
                */

                if (
                    $batch['course_status'] !== 'active'
                ) {

                    throw new RuntimeException(
                        'This course is not open for enrollment.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDATE BATCH
                |--------------------------------------------------------------------------
                */

                if (
                    !in_array(
                        $batch['batch_status'],
                        [
                            'planned',
                            'active'
                        ],
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'This batch is not open for enrollment.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDATE TERM
                |--------------------------------------------------------------------------
                */

                if (
                    !in_array(
                        $batch['term_status'],
                        [
                            'planned',
                            'active'
                        ],
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'The academic term is not open for enrollment.'
                    );
                }


                $courseId =
                    (int) $batch['course_id'];


                /*
                |--------------------------------------------------------------------------
                | PREVENT MULTIPLE CURRENT BATCHES
                | FOR SAME COURSE
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT
                        e.enrollment_id

                    FROM enrollments e

                    INNER JOIN batches b
                        ON b.batch_id = e.batch_id

                    WHERE
                        e.student_id = ?
                        AND b.course_id = ?
                        AND e.enrollment_status IN (
                            'Pending',
                            'Active',
                            'Completed'
                        )

                    LIMIT 1
                ");


                $stmt->bind_param(
                    "ii",
                    $studentId,
                    $courseId
                );


                $stmt->execute();


                $alreadyInCourse =
                    $stmt
                    ->get_result()
                    ->num_rows > 0;


                $stmt->close();


                if ($alreadyInCourse) {

                    throw new RuntimeException(
                        'You are already enrolled in this course.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CHECK PREVIOUS ENROLLMENT
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT
                        enrollment_id,
                        enrollment_status

                    FROM enrollments

                    WHERE
                        student_id = ?
                        AND batch_id = ?

                    LIMIT 1
                ");


                $stmt->bind_param(
                    "ii",
                    $studentId,
                    $batchId
                );


                $stmt->execute();


                $existing =
                    $stmt
                    ->get_result()
                    ->fetch_assoc();


                $stmt->close();


                /*
                |--------------------------------------------------------------------------
                | RE-ENROLL
                |--------------------------------------------------------------------------
                */

                if ($existing) {

                    if (
                        !in_array(
                            $existing['enrollment_status'],
                            [
                                'Withdrawn',
                                'Cancelled'
                            ],
                            true
                        )
                    ) {

                        throw new RuntimeException(
                            'You are already enrolled in this batch.'
                        );
                    }


                    $stmt = $conn->prepare("
                        UPDATE enrollments

                        SET
                            enrollment_status = 'Active',
                            enrollment_date = CURRENT_DATE

                        WHERE
                            enrollment_id = ?
                            AND student_id = ?
                    ");


                    $enrollmentId =
                        (int) $existing['enrollment_id'];


                    $stmt->bind_param(
                        "ii",
                        $enrollmentId,
                        $studentId
                    );


                    $stmt->execute();

                    $stmt->close();

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | NEW ENROLLMENT
                    |--------------------------------------------------------------------------
                    */

                    $stmt = $conn->prepare("
                        INSERT INTO enrollments
                        (
                            student_id,
                            batch_id,
                            enrollment_status
                        )

                        VALUES
                        (
                            ?,
                            ?,
                            'Active'
                        )
                    ");


                    $stmt->bind_param(
                        "ii",
                        $studentId,
                        $batchId
                    );


                    $stmt->execute();

                    $stmt->close();
                }


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                $_SESSION['course_success'] =
                    'Successfully enrolled in '
                    . $batch['course_name']
                    . ' - '
                    . $batch['batch_name']
                    . '.';


                header(
                    "Location: courses.php"
                );

                exit();
            }


            /*
            |--------------------------------------------------------------------------
            | WITHDRAW FROM COURSE
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $_POST['withdraw_enrollment']
                )
            ) {

                $enrollmentId =
                    (int) (
                        $_POST['enrollment_id']
                        ?? 0
                    );


                if ($enrollmentId <= 0) {

                    throw new RuntimeException(
                        'Invalid enrollment selected.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | FIND STUDENT ENROLLMENT
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    SELECT
                        e.enrollment_id,
                        e.enrollment_status,

                        c.course_name,
                        b.batch_name

                    FROM enrollments e

                    INNER JOIN batches b
                        ON b.batch_id = e.batch_id

                    INNER JOIN courses c
                        ON c.course_id = b.course_id

                    WHERE
                        e.enrollment_id = ?
                        AND e.student_id = ?

                    LIMIT 1
                ");


                $stmt->bind_param(
                    "ii",
                    $enrollmentId,
                    $studentId
                );


                $stmt->execute();


                $enrollment =
                    $stmt
                    ->get_result()
                    ->fetch_assoc();


                $stmt->close();


                if (!$enrollment) {

                    throw new RuntimeException(
                        'Enrollment not found.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | ONLY ACTIVE/PENDING CAN WITHDRAW
                |--------------------------------------------------------------------------
                */

                if (
                    !in_array(
                        $enrollment[
                            'enrollment_status'
                        ],
                        [
                            'Pending',
                            'Active'
                        ],
                        true
                    )
                ) {

                    throw new RuntimeException(
                        'This enrollment can no longer be withdrawn.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | SOFT DELETE / WITHDRAW
                |--------------------------------------------------------------------------
                */

                $stmt = $conn->prepare("
                    UPDATE enrollments

                    SET
                        enrollment_status =
                        'Withdrawn'

                    WHERE
                        enrollment_id = ?
                        AND student_id = ?
                ");


                $stmt->bind_param(
                    "ii",
                    $enrollmentId,
                    $studentId
                );


                $stmt->execute();

                $stmt->close();


                $_SESSION['course_success'] =
                    'You have withdrawn from '
                    . $enrollment['course_name']
                    . ' - '
                    . $enrollment['batch_name']
                    . '.';


                header(
                    "Location: courses.php"
                );

                exit();
            }

        } catch (RuntimeException $e) {

            $error =
                $e->getMessage();

        } catch (Throwable $e) {

            $error =
                'The course operation could not be completed. Please try again.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| LOAD ENROLLED COURSES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT

        e.enrollment_id,
        e.enrollment_date,
        e.enrollment_status,

        b.batch_id,
        b.batch_name,
        b.status AS batch_status,
        b.start_date,
        b.end_date,

        c.course_id,
        c.course_name,
        c.description,
        c.course_fee,
        c.status AS course_status,

        s.subject_code,
        s.subject_name,

        at.term_name,

        GROUP_CONCAT(
            DISTINCT ua.fullname
            ORDER BY ua.fullname
            SEPARATOR ', '
        ) AS teacher_names

    FROM enrollments e

    INNER JOIN batches b
        ON b.batch_id = e.batch_id

    INNER JOIN courses c
        ON c.course_id = b.course_id

    INNER JOIN academic_terms at
        ON at.term_id = b.term_id

    LEFT JOIN subjects s
        ON s.subject_id = c.subject_id

    LEFT JOIN teacher_courses tc
        ON tc.course_id = c.course_id

    LEFT JOIN user_accounts ua
        ON ua.user_id = tc.teacher_id

    WHERE
        e.student_id = ?
        AND e.enrollment_status IN
        (
            'Pending',
            'Active',
            'Completed'
        )

    GROUP BY

        e.enrollment_id,
        e.enrollment_date,
        e.enrollment_status,

        b.batch_id,
        b.batch_name,
        b.status,
        b.start_date,
        b.end_date,

        c.course_id,
        c.course_name,
        c.description,
        c.course_fee,
        c.status,

        s.subject_code,
        s.subject_name,

        at.term_name

    ORDER BY

        FIELD(
            e.enrollment_status,
            'Active',
            'Pending',
            'Completed'
        ),

        c.course_name,
        b.batch_name
");


$stmt->bind_param(
    "i",
    $studentId
);


$stmt->execute();


$enrolledResult =
    $stmt->get_result();


$enrolledCourses =
    $enrolledResult->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();


/*
|--------------------------------------------------------------------------
| AVAILABLE COURSES / BATCHES
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT

        b.batch_id,
        b.batch_name,
        b.status AS batch_status,
        b.start_date,
        b.end_date,

        c.course_id,
        c.course_name,
        c.description,
        c.course_fee,

        s.subject_code,
        s.subject_name,

        at.term_name,

        GROUP_CONCAT(
            DISTINCT ua.fullname
            ORDER BY ua.fullname
            SEPARATOR ', '
        ) AS teacher_names

    FROM batches b

    INNER JOIN courses c
        ON c.course_id = b.course_id

    INNER JOIN academic_terms at
        ON at.term_id = b.term_id

    LEFT JOIN subjects s
        ON s.subject_id = c.subject_id

    LEFT JOIN teacher_courses tc
        ON tc.course_id = c.course_id

    LEFT JOIN user_accounts ua
        ON ua.user_id = tc.teacher_id

    WHERE

        c.status = 'active'

        AND b.status IN
        (
            'planned',
            'active'
        )

        AND at.status IN
        (
            'planned',
            'active'
        )

        AND NOT EXISTS
        (
            SELECT 1

            FROM enrollments e2

            INNER JOIN batches b2
                ON b2.batch_id = e2.batch_id

            WHERE
                e2.student_id = ?

                AND b2.course_id =
                    c.course_id

                AND e2.enrollment_status IN
                (
                    'Pending',
                    'Active',
                    'Completed'
                )
        )

    GROUP BY

        b.batch_id,
        b.batch_name,
        b.status,
        b.start_date,
        b.end_date,

        c.course_id,
        c.course_name,
        c.description,
        c.course_fee,

        s.subject_code,
        s.subject_name,

        at.term_name

    ORDER BY

        c.course_name,
        b.start_date,
        b.batch_name
");


$stmt->bind_param(
    "i",
    $studentId
);


$stmt->execute();


$availableResult =
    $stmt->get_result();


$availableBatches =
    $availableResult->fetch_all(
        MYSQLI_ASSOC
    );


$stmt->close();


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$enrolledCourseCount = 0;
$activeBatchCount = 0;
$completedCourseCount = 0;

$courseIds = [];


foreach (
    $enrolledCourses
    as $course
) {

    $courseIds[
        (int) $course['course_id']
    ] = true;


    if (
        $course['enrollment_status']
            === 'Active'
        &&
        $course['batch_status']
            === 'active'
    ) {

        $activeBatchCount++;
    }


    if (
        $course['enrollment_status']
            === 'Completed'
    ) {

        $completedCourseCount++;
    }
}


$enrolledCourseCount =
    count($courseIds);


/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/

function e(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


function money($amount): string
{
    return number_format(
        (float) $amount,
        2
    );
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

    <title>
        <?php echo e($projectName); ?>
        | My Courses
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
                class="menu-link active"
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
         CONTENT
         ===================================================== -->

    <div class="content-area">


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
                        Courses
                    </p>

                    <h1>
                        My Courses
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
                            <?php
                            echo e($studentName);
                            ?>
                        </strong>

                    </div>


                </div>


            </div>


        </header>



        <main class="dashboard-main">


            <!-- =================================================
                 OVERVIEW
                 ================================================= -->

            <section class="overview-cards">


                <article class="summary-card">


                    <div>

                        <p class="card-label">
                            Learning
                        </p>

                        <h2>
                            Manage your course enrollments.
                        </h2>

                    </div>


                    <div class="summary-icon">

                        <i class="fas fa-book-open"></i>

                    </div>


                </article>



                <article class="stat-card">


                    <div>

                        <p class="card-label">
                            Enrolled Courses
                        </p>

                        <h3>
                            <?php
                            echo $enrolledCourseCount;
                            ?>
                        </h3>

                    </div>


                    <span class="stat-badge">
                        Current courses
                    </span>


                </article>



                <article class="stat-card">


                    <div>

                        <p class="card-label">
                            Active Batches
                        </p>

                        <h3>
                            <?php
                            echo $activeBatchCount;
                            ?>
                        </h3>

                    </div>


                    <span class="stat-badge">
                        Active learning
                    </span>


                </article>


            </section>



            <!-- =================================================
                 SUCCESS
                 ================================================= -->

            <?php if ($success !== ''): ?>


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

                    <?php
                    echo e($success);
                    ?>

                </div>


            <?php endif; ?>



            <!-- =================================================
                 ERROR
                 ================================================= -->

            <?php if ($error !== ''): ?>


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

                    <?php
                    echo e($error);
                    ?>

                </div>


            <?php endif; ?>



            <!-- =================================================
                 ENROLLED COURSES
                 ================================================= -->

            <section class="course-section">


                <div class="course-section-header">


                    <div>

                        <p class="card-label">
                            My Learning
                        </p>

                        <h2>
                            Enrolled Courses
                        </h2>

                        <p>
                            These are the courses and batches
                            currently connected to your student account.
                        </p>

                    </div>


                </div>



                <?php if (empty($enrolledCourses)): ?>


                    <div class="course-empty-state">


                        <div class="course-empty-icon">

                            <i class="fas fa-book-open"></i>

                        </div>


                        <h3>
                            No Enrolled Courses
                        </h3>


                        <p>
                            Choose an available course below
                            to enroll.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="card-grid">


                        <?php
                        foreach (
                            $enrolledCourses
                            as $course
                        ):
                        ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-blue">

                                    <i class="fas fa-book"></i>

                                </div>


                                <p class="card-label">

                                    <?php

                                    echo e(
                                        $course[
                                            'subject_code'
                                        ]
                                        ?: (
                                            'COURSE-'
                                            . $course[
                                                'course_id'
                                            ]
                                        )
                                    );

                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $course[
                                            'course_name'
                                        ]
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Batch:
                                    </strong>

                                    <?php
                                    echo e(
                                        $course[
                                            'batch_name'
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Term:
                                    </strong>

                                    <?php
                                    echo e(
                                        $course[
                                            'term_name'
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Teacher:
                                    </strong>

                                    <?php

                                    echo e(
                                        $course[
                                            'teacher_names'
                                        ]
                                        ?: 'Not assigned yet'
                                    );

                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Status:
                                    </strong>

                                    <?php
                                    echo e(
                                        $course[
                                            'enrollment_status'
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Enrolled:
                                    </strong>

                                    <?php
                                    echo e(
                                        $course[
                                            'enrollment_date'
                                        ]
                                    );
                                    ?>

                                </p>



                                <div
                                    class="form-actions"
                                    style="
                                    justify-content:flex-start;
                                    flex-wrap:wrap;
                                    "
                                >


                                    <a
                                        class="primary-btn"
                                        href="course_details.php?id=<?php
                                        echo (int)
                                            $course[
                                                'course_id'
                                            ];
                                        ?>&batch_id=<?php
                                        echo (int)
                                            $course[
                                                'batch_id'
                                            ];
                                        ?>"
                                    >

                                        <i class="fas fa-eye"></i>

                                        View Course

                                    </a>



                                    <?php
                                    if (
                                        in_array(
                                            $course[
                                                'enrollment_status'
                                            ],
                                            [
                                                'Pending',
                                                'Active'
                                            ],
                                            true
                                        )
                                    ):
                                    ?>


                                        <form
                                            method="POST"
                                            action="courses.php"
                                            onsubmit="
                                            return confirm(
                                            'Withdraw from this course batch?'
                                            );
                                            "
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
                                                name="enrollment_id"
                                                value="<?php
                                                echo (int)
                                                    $course[
                                                        'enrollment_id'
                                                    ];
                                                ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="withdraw_enrollment"
                                                class="secondary-btn"
                                            >

                                                <i
                                                    class="fas fa-right-from-bracket"
                                                ></i>

                                                Withdraw

                                            </button>


                                        </form>


                                    <?php endif; ?>


                                </div>


                            </article>


                        <?php endforeach; ?>


                    </div>


                <?php endif; ?>


            </section>



            <!-- =================================================
                 AVAILABLE COURSES
                 ================================================= -->

            <section
                class="course-section"
                style="margin-top:24px;"
            >


                <div class="course-section-header">


                    <div>

                        <p class="card-label">
                            Course Enrollment
                        </p>

                        <h2>
                            Available Courses
                        </h2>

                        <p>
                            Enroll in an available batch.
                            A student can have only one current
                            enrollment for the same course.
                        </p>

                    </div>


                </div>



                <?php if (empty($availableBatches)): ?>


                    <div class="course-empty-state">


                        <div class="course-empty-icon">

                            <i class="fas fa-circle-check"></i>

                        </div>


                        <h3>
                            No Additional Courses Available
                        </h3>


                        <p>
                            There are currently no other active
                            or planned course batches available
                            for enrollment.
                        </p>


                    </div>


                <?php else: ?>


                    <div class="card-grid">


                        <?php
                        foreach (
                            $availableBatches
                            as $course
                        ):
                        ?>


                            <article class="dashboard-card">


                                <div class="card-icon bg-green">

                                    <i class="fas fa-plus"></i>

                                </div>


                                <p class="card-label">

                                    <?php

                                    echo e(
                                        $course[
                                            'subject_code'
                                        ]
                                        ?: (
                                            'COURSE-'
                                            . $course[
                                                'course_id'
                                            ]
                                        )
                                    );

                                    ?>

                                </p>


                                <h3>

                                    <?php
                                    echo e(
                                        $course[
                                            'course_name'
                                        ]
                                    );
                                    ?>

                                </h3>


                                <p>

                                    <strong>
                                        Subject:
                                    </strong>

                                    <?php

                                    echo e(
                                        $course[
                                            'subject_name'
                                        ]
                                        ?: 'General'
                                    );

                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Batch:
                                    </strong>

                                    <?php
                                    echo e(
                                        $course[
                                            'batch_name'
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Term:
                                    </strong>

                                    <?php
                                    echo e(
                                        $course[
                                            'term_name'
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Teacher:
                                    </strong>

                                    <?php

                                    echo e(
                                        $course[
                                            'teacher_names'
                                        ]
                                        ?: 'Not assigned yet'
                                    );

                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Starts:
                                    </strong>

                                    <?php
                                    echo e(
                                        $course[
                                            'start_date'
                                        ]
                                    );
                                    ?>

                                </p>


                                <p>

                                    <strong>
                                        Fee:
                                    </strong>

                                    LKR

                                    <?php
                                    echo money(
                                        $course[
                                            'course_fee'
                                        ]
                                    );
                                    ?>

                                </p>



                                <form
                                    method="POST"
                                    action="courses.php"
                                    class="form-actions"
                                    style="
                                    justify-content:flex-start;
                                    "
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
                                        name="batch_id"
                                        value="<?php
                                        echo (int)
                                            $course[
                                                'batch_id'
                                            ];
                                        ?>"
                                    >


                                    <button
                                        type="submit"
                                        name="enroll_batch"
                                        class="primary-btn"
                                    >

                                        <i
                                            class="fas fa-user-plus"
                                        ></i>

                                        Enroll Now

                                    </button>


                                </form>


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