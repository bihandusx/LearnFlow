-- ============================================================
-- LEARNFLOW CRUD TEST SEED DATA
-- For the exact clean LearnFlow schema supplied by the user
-- Target: MySQL 8.0+
--
-- IMPORTANT:
-- 1) Run this AFTER importing the LearnFlow database schema.
-- 2) Intended for a fresh/empty learnflow_db database.
-- 3) Do NOT run it repeatedly unless you reset the database again.
--
--
-- CRUD TEST PERSONAS
-- ------------------
-- Alice Perera  : Active in WAD + DBMS; graded/submitted work; paid course/tute.
-- Nimal Silva   : Active in WAD only; pending course payment; failed/cancelled
--                 tute-payment path; can enroll in DBMS.
-- Kavindi       : DBMS enrollment is changed to Completed at the end of this
--                 seed so historical/read-only behavior can be tested.
-- Free PHP tute : left unowned so Get Free Tute can be tested.
-- Web Mid-Term  : scheduled for CURRENT_DATE, 00:00-23:59, so exam attempt
--                 testing is available on the day the seed is imported.

-- Demo login password for ALL seeded users:
--   Password@123
--
-- Seeded login emails:
--   Student:      alice.perera@learnflow.demo
--   Student:      nimal.silva@learnflow.demo
--   Student:      kavindi.jay@learnflow.demo
--   Teacher:      saman.perera@learnflow.demo
--   Teacher:      nadeesha.fernando@learnflow.demo
--   Parent:       kamal.perera@learnflow.demo
--   Admin:        admin@learnflow.demo
--   Coordinator:  coordinator@learnflow.demo
--
-- NOTE:
-- The schema contains 50 physical/base tables and 12 vw_* reporting views.
-- The old singular compatibility views are intentionally not used.
-- Role subtype rows are auto-created by trg_user_accounts_create_subtype.
-- ============================================================

USE learnflow_db;

SET NAMES utf8mb4;

START TRANSACTION;

-- ============================================================
-- 1. USER ACCOUNTS + ROLE SUBTYPES
-- ============================================================

-- Required because the database correctly blocks direct public creation
-- of Admin and AcademicCoordinator accounts.
SET @learnflow_allow_privileged_user_create = 1;

INSERT INTO user_accounts
(
    user_id,
    fullname,
    email,
    password,
    phone,
    address,
    status,
    role,
    profile_image
)
VALUES
(1, 'Alice Perera', 'alice.perera@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0771112233', 'Colombo 05, Sri Lanka', 'active', 'Student', NULL),

(2, 'Nimal Silva', 'nimal.silva@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0712223344', 'Kandy, Sri Lanka', 'active', 'Student', NULL),

(3, 'Kavindi Jayasinghe', 'kavindi.jay@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0753334455', 'Galle, Sri Lanka', 'active', 'Student', NULL),

(4, 'Dr. Saman Perera', 'saman.perera@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0764445566', 'Nugegoda, Sri Lanka', 'active', 'Teacher', NULL),

(5, 'Nadeesha Fernando', 'nadeesha.fernando@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0785556677', 'Negombo, Sri Lanka', 'active', 'Teacher', NULL),

(6, 'Kamal Perera', 'kamal.perera@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0706667788', 'Colombo 05, Sri Lanka', 'active', 'Parent', NULL),

(7, 'LearnFlow Administrator', 'admin@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0112345678', 'LearnFlow Main Office', 'active', 'Admin', NULL),

(8, 'Malini Wijesinghe', 'coordinator@learnflow.demo',
 '$2y$12$b1N1QNXAmmkYXFNJ1CJc9.HGsMVNtIlQ8pQ1Jv.Puk3gfLLLtml5y',
 '0778889900', 'LearnFlow Academic Office', 'active', 'AcademicCoordinator', NULL);

SET @learnflow_allow_privileged_user_create = 0;


-- Role subtype rows are created automatically by
-- trg_user_accounts_create_subtype. Update the generated Student rows
-- with demo profile data instead of inserting duplicate primary keys.
UPDATE students
SET
    registration_no = CASE student_id
        WHEN 1 THEN 'LF-STU-0001'
        WHEN 2 THEN 'LF-STU-0002'
        WHEN 3 THEN 'LF-STU-0003'
        ELSE registration_no
    END,
    nic = CASE student_id
        WHEN 1 THEN '200112345678'
        WHEN 2 THEN '200023456789'
        WHEN 3 THEN '200234567890'
        ELSE nic
    END,
    date_of_birth = CASE student_id
        WHEN 1 THEN '2001-04-18'
        WHEN 2 THEN '2000-08-07'
        WHEN 3 THEN '2002-02-14'
        ELSE date_of_birth
    END,
    gender = CASE student_id
        WHEN 1 THEN 'female'
        WHEN 2 THEN 'male'
        WHEN 3 THEN 'female'
        ELSE gender
    END
WHERE student_id IN (1,2,3);

-- Teacher subtype rows are also created automatically.
UPDATE teachers
SET
    qualification = CASE teacher_id
        WHEN 4 THEN 'MSc in Computer Science'
        WHEN 5 THEN 'MSc in Information Systems'
        ELSE qualification
    END,
    specialization = CASE teacher_id
        WHEN 4 THEN 'Web Development and Software Engineering'
        WHEN 5 THEN 'Databases and Data Management'
        ELSE specialization
    END
WHERE teacher_id IN (4,5);

-- parents.parent_id=6 was auto-created by trg_user_accounts_create_subtype.

-- system_administrators.admin_id=7 was auto-created by trg_user_accounts_create_subtype.

-- course_coordinators.coordinator_id=8 was auto-created by trg_user_accounts_create_subtype.

INSERT INTO parent_student
(
    parent_id,
    student_id,
    relationship_type
)
VALUES
(6, 1, 'Father'),
(6, 2, 'Guardian');


-- Teacher accounts are forced to pending by the schema trigger.
-- Seed approval history and then activate the approved teachers.
INSERT INTO teacher_approvals
(
    approval_id,
    admin_id,
    teacher_id,
    decision,
    decision_date,
    remarks
)
VALUES
(1, 7, 4, 'approved', NOW(), 'Qualifications verified for demo dataset.'),
(2, 7, 5, 'approved', NOW(), 'Qualifications verified for demo dataset.');

UPDATE user_accounts
SET status = 'active'
WHERE user_id IN (4, 5);


INSERT INTO password_reset_tokens
(
    reset_id,
    user_id,
    token_hash,
    expires_at,
    used_at
)
VALUES
(
    1,
    2,
    SHA2('demo-reset-token-nimal-001', 256),
    DATE_ADD(NOW(), INTERVAL 1 DAY),
    NULL
);


-- ============================================================
-- 2. ACADEMIC STRUCTURE
-- ============================================================

INSERT INTO subjects
(
    subject_id,
    subject_code,
    subject_name,
    description,
    status
)
VALUES
(1, 'ICT-WEB', 'Web Technologies',
 'HTML, CSS, JavaScript, PHP and modern web application concepts.',
 'active'),

(2, 'ICT-DB', 'Database Systems',
 'Relational modelling, SQL, normalization and database administration.',
 'active');


INSERT INTO courses
(
    course_id,
    coordinator_id,
    subject_id,
    course_name,
    description,
    course_fee,
    status
)
VALUES
(1, 8, 1, 'Web Application Development',
 'Practical full-stack web development using HTML, CSS, JavaScript, PHP and MySQL.',
 18000.00, 'active'),

(2, 8, 2, 'Database Management Systems',
 'Database design, SQL querying, normalization, transactions and administration.',
 15000.00, 'active');


INSERT INTO academic_terms
(
    term_id,
    planned_by_coordinator_id,
    term_name,
    start_date,
    end_date,
    status
)
VALUES
(
    1,
    8,
    'Current Demo Term',
    DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY),
    DATE_ADD(CURRENT_DATE, INTERVAL 90 DAY),
    'active'
),

(
    2,
    8,
    'Next Demo Term',
    DATE_ADD(CURRENT_DATE, INTERVAL 100 DAY),
    DATE_ADD(CURRENT_DATE, INTERVAL 190 DAY),
    'planned'
);


INSERT INTO batches
(
    batch_id,
    course_id,
    term_id,
    batch_name,
    start_date,
    end_date,
    status
)
VALUES
(
    1,
    1,
    1,
    'WAD-A',
    DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY),
    DATE_ADD(CURRENT_DATE, INTERVAL 80 DAY),
    'active'
),

(
    2,
    2,
    1,
    'DBMS-A',
    DATE_SUB(CURRENT_DATE, INTERVAL 20 DAY),
    DATE_ADD(CURRENT_DATE, INTERVAL 80 DAY),
    'active'
);


INSERT INTO modules
(
    module_id,
    course_id,
    module_name,
    module_order,
    description
)
VALUES
(1, 1, 'HTML and CSS Fundamentals', 1,
 'Structure web pages and style responsive user interfaces.'),

(2, 1, 'PHP and Server-Side Development', 2,
 'Build dynamic server-side web applications with PHP.'),

(3, 2, 'Relational Database Design', 1,
 'Entity relationships, normalization and relational schemas.'),

(4, 2, 'SQL and Transaction Management', 2,
 'Queries, joins, transactions, indexing and database integrity.');


INSERT INTO teacher_courses
(
    teacher_id,
    course_id,
    assigned_by_coordinator_id,
    assigned_date
)
VALUES
(4, 1, 8, CURRENT_DATE),
(5, 2, 8, CURRENT_DATE);


INSERT INTO enrollments
(
    enrollment_id,
    student_id,
    batch_id,
    enrollment_date,
    enrollment_status
)
VALUES
(1, 1, 1, DATE_SUB(CURRENT_DATE, INTERVAL 18 DAY), 'Active'),
(2, 1, 2, DATE_SUB(CURRENT_DATE, INTERVAL 16 DAY), 'Active'),
(3, 2, 1, DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), 'Active'),
(4, 3, 2, DATE_SUB(CURRENT_DATE, INTERVAL 14 DAY), 'Active');


INSERT INTO student_module_progress
(
    progress_id,
    student_id,
    module_id,
    progress_percentage,
    progress_status,
    last_accessed_at,
    completed_at
)
VALUES
(1, 1, 1, 70.00, 'in_progress', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(2, 1, 2, 100.00, 'completed', DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 1, 3, 45.00, 'in_progress', DATE_SUB(NOW(), INTERVAL 3 HOUR), NULL),
(4, 1, 4, 0.00, 'not_started', NULL, NULL),
(5, 2, 1, 30.00, 'in_progress', DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(6, 2, 2, 0.00, 'not_started', NULL, NULL),
(7, 3, 3, 85.00, 'in_progress', DATE_SUB(NOW(), INTERVAL 5 HOUR), NULL),
(8, 3, 4, 100.00, 'completed', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY));


-- ============================================================
-- 3. LEARNING RESOURCES
-- ============================================================

INSERT INTO learning_resources
(
    resource_id,
    module_id,
    uploaded_by_user_id,
    approved_by_coordinator_id,
    title,
    description,
    file_url,
    upload_date,
    approval_status,
    resource_type
)
VALUES
(1, 1, 4, 8,
 'HTML and CSS Quick Notes',
 'Revision notes covering semantic HTML and core CSS concepts.',
 'uploads/materials/html_css_quick_notes.pdf',
 DATE_SUB(NOW(), INTERVAL 10 DAY),
 'approved',
 'study_material'),

(2, 1, 4, 8,
 'Responsive Web Design Recording',
 'Recorded lesson on media queries, flexbox and responsive layouts.',
 'uploads/recordings/responsive_web_design.mp4',
 DATE_SUB(NOW(), INTERVAL 9 DAY),
 'approved',
 'recording'),

(3, 2, 4, 8,
 'PHP Practice Tute 01',
 'Practice exercises covering forms, sessions and MySQL integration.',
 'uploads/tutes/php_practice_tute_01.pdf',
 DATE_SUB(NOW(), INTERVAL 8 DAY),
 'approved',
 'tute'),

(4, 3, 5, 8,
 'Normalization Study Guide',
 'Examples covering 1NF, 2NF, 3NF and BCNF.',
 'uploads/materials/normalization_guide.pdf',
 DATE_SUB(NOW(), INTERVAL 8 DAY),
 'approved',
 'study_material'),

(5, 3, 5, 8,
 'ER Modelling Lecture Recording',
 'Recorded class on entities, relationships, keys and cardinalities.',
 'uploads/recordings/er_modelling.mp4',
 DATE_SUB(NOW(), INTERVAL 7 DAY),
 'approved',
 'recording'),

(6, 4, 5, 8,
 'Advanced SQL Practice Tute',
 'SQL joins, subqueries, transactions and indexing exercises.',
 'uploads/tutes/advanced_sql_tute.pdf',
 DATE_SUB(NOW(), INTERVAL 6 DAY),
 'approved',
 'tute'),

(7, 2, 4, 8,
 'Free PHP Quick Reference',
 'A free quick-reference tute for testing the Get Free Tute workflow.',
 'uploads/tutes/free_php_quick_reference.pdf',
 DATE_SUB(NOW(), INTERVAL 2 DAY),
 'approved',
 'tute');


INSERT INTO study_materials
(
    resource_id,
    material_type
)
VALUES
(1, 'PDF Notes'),
(4, 'PDF Study Guide');


INSERT INTO recordings
(
    resource_id,
    duration_seconds,
    recorded_date
)
VALUES
(2, 4200, DATE_SUB(CURRENT_DATE, INTERVAL 12 DAY)),
(5, 5100, DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY));


INSERT INTO tutes
(
    resource_id,
    price,
    availability_status
)
VALUES
(3, 500.00, 'available'),
(6, 650.00, 'available'),
(7, 0.00, 'available');


INSERT INTO resource_access_logs
(
    access_id,
    student_id,
    resource_id,
    access_type,
    watched_seconds,
    completed,
    accessed_at
)
VALUES
(1, 1, 1, 'view', NULL, TRUE, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 1, 2, 'watch', 3900, TRUE, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 1, 4, 'download', NULL, TRUE, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 2, 1, 'download', NULL, TRUE, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 3, 5, 'watch', 2800, FALSE, DATE_SUB(NOW(), INTERVAL 1 DAY));


-- ============================================================
-- 4. CLASS SESSIONS + ATTENDANCE
-- ============================================================

INSERT INTO class_sessions
(
    session_id,
    batch_id,
    teacher_id,
    class_date,
    start_time,
    end_time,
    venue,
    topic,
    status
)
VALUES
(1, 1, 4,
 DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY),
 '09:00:00', '11:00:00',
 'Lab A',
 'CSS Layouts and Flexbox',
 'completed'),

(2, 1, 4,
 DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY),
 '09:00:00', '11:00:00',
 'Lab A',
 'PHP Forms and Validation',
 'scheduled'),

(3, 2, 5,
 DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY),
 '13:00:00', '15:00:00',
 'Room B2',
 'Database Normalization',
 'completed'),

(4, 2, 5,
 DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY),
 '13:00:00', '15:00:00',
 'Room B2',
 'SQL Joins and Transactions',
 'scheduled');


INSERT INTO attendance
(
    attendance_id,
    session_id,
    student_id,
    attendance_status,
    marked_at
)
VALUES
(1, 1, 1, 'present', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(2, 1, 2, 'late', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 3, 1, 'present', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(4, 3, 3, 'absent', DATE_SUB(NOW(), INTERVAL 2 DAY));


-- ============================================================
-- 5. ASSESSMENTS
-- ============================================================

INSERT INTO assessments
(
    assessment_id,
    batch_id,
    teacher_id,
    title,
    description,
    open_date,
    close_date,
    total_marks,
    assessment_type,
    status
)
VALUES
(1, 1, 4,
 'Responsive Portfolio Assignment',
 'Create a responsive personal portfolio using HTML and CSS.',
 DATE_SUB(NOW(), INTERVAL 5 DAY),
 DATE_ADD(NOW(), INTERVAL 10 DAY),
 100.00,
 'assignment',
 'published'),

(2, 1, 4,
 'Web Fundamentals Quiz',
 'Quiz covering HTML, CSS and basic client/server concepts.',
 DATE_SUB(NOW(), INTERVAL 2 DAY),
 DATE_ADD(NOW(), INTERVAL 20 DAY),
 20.00,
 'quiz',
 'published'),

(3, 1, 4,
 'Web Development Mid-Term Exam',
 'Mid-term exam for the Web Application Development course.',
 DATE_SUB(NOW(), INTERVAL 1 DAY),
 DATE_ADD(NOW(), INTERVAL 30 DAY),
 100.00,
 'exam',
 'published'),

(4, 2, 5,
 'Database Design Assignment',
 'Design an ER model and normalized relational schema.',
 DATE_SUB(NOW(), INTERVAL 4 DAY),
 DATE_ADD(NOW(), INTERVAL 12 DAY),
 50.00,
 'assignment',
 'published'),

(5, 2, 5,
 'SQL Basics Quiz',
 'Quiz covering SELECT, JOIN, GROUP BY and transactions.',
 DATE_SUB(NOW(), INTERVAL 2 DAY),
 DATE_ADD(NOW(), INTERVAL 20 DAY),
 20.00,
 'quiz',
 'published'),

(6, 2, 5,
 'Database Systems Mid-Term Exam',
 'Mid-term examination for Database Management Systems.',
 DATE_SUB(NOW(), INTERVAL 1 DAY),
 DATE_ADD(NOW(), INTERVAL 30 DAY),
 100.00,
 'exam',
 'published');


INSERT INTO assignments
(
    assignment_id,
    due_date,
    instructions,
    submission_type,
    max_file_size_mb,
    allow_late_submission
)
VALUES
(1,
 DATE_ADD(NOW(), INTERVAL 10 DAY),
 'Submit a ZIP file containing HTML/CSS source files plus a short design explanation.',
 'both',
 20,
 TRUE),

(4,
 DATE_ADD(NOW(), INTERVAL 12 DAY),
 'Submit the ER diagram and normalized table design as PDF.',
 'file',
 15,
 FALSE);


INSERT INTO quizzes
(
    quiz_id,
    duration_minutes,
    attempt_limit,
    passing_marks,
    randomize_questions
)
VALUES
(2, 20, 2, 10.00, TRUE),
(5, 25, 2, 10.00, FALSE);


INSERT INTO exams
(
    exam_id,
    exam_date,
    start_time,
    end_time,
    venue,
    duration_minutes,
    attempt_limit,
    passing_marks
)
VALUES
(3,
 CURRENT_DATE,
 '00:00:00',
 '23:59:59',
 'Examination Hall A',
 120,
 1,
 50.00),

(6,
 DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY),
 '13:00:00',
 '15:00:00',
 'Examination Hall B',
 120,
 1,
 50.00);


INSERT INTO assignment_submissions
(
    submission_id,
    assignment_id,
    student_id,
    file_url,
    text_answer,
    submission_date,
    submission_status,
    marks,
    feedback,
    graded_by_teacher_id,
    graded_at
)
VALUES
(1, 1, 1,
 'uploads/submissions/alice_portfolio.zip',
 'Responsive portfolio completed with semantic HTML and CSS Grid.',
 DATE_SUB(NOW(), INTERVAL 1 DAY),
 'Graded',
 88.00,
 'Strong layout and responsiveness. Improve accessibility labels.',
 4,
 NOW()),

(2, 1, 2,
 'uploads/submissions/nimal_portfolio.zip',
 'My portfolio submission.',
 NOW(),
 'Submitted',
 NULL,
 NULL,
 NULL,
 NULL),

(3, 4, 1,
 'uploads/submissions/alice_database_design.pdf',
 NULL,
 NOW(),
 'Submitted',
 NULL,
 NULL,
 NULL,
 NULL),

(4, 4, 3,
 'uploads/submissions/kavindi_database_design.pdf',
 NULL,
 DATE_SUB(NOW(), INTERVAL 1 DAY),
 'Graded',
 42.00,
 'Good ER model. Minor normalization corrections required.',
 5,
 NOW());


-- ============================================================
-- 6. QUESTION BANK + QUESTIONS
-- ============================================================

INSERT INTO question_banks
(
    question_bank_id,
    course_id,
    coordinator_id,
    bank_name,
    description
)
VALUES
(1, 1, 8,
 'Web Development Question Bank',
 'Reusable questions for Web Development quizzes and exams.'),

(2, 2, 8,
 'Database Systems Question Bank',
 'Reusable questions for DBMS quizzes and exams.');


INSERT INTO questions
(
    question_id,
    question_bank_id,
    question_text,
    question_type,
    default_marks
)
VALUES
(1, 1,
 'Which HTML element is used for the largest heading?',
 'mcq',
 5.00),

(2, 1,
 'CSS can be used to control page layout and presentation.',
 'true_false',
 5.00),

(3, 1,
 'Briefly explain the difference between GET and POST in HTTP forms.',
 'short_answer',
 10.00),

(4, 2,
 'Which SQL command is used to retrieve rows from a table?',
 'mcq',
 5.00),

(5, 2,
 'A primary key may contain duplicate values.',
 'true_false',
 5.00),

(6, 2,
 'Explain normalization and discuss the purpose of 1NF, 2NF and 3NF.',
 'essay',
 20.00);


INSERT INTO question_options
(
    option_id,
    question_id,
    option_text,
    is_correct
)
VALUES
(1, 1, '<h1>', TRUE),
(2, 1, '<h6>', FALSE),
(3, 1, '<header>', FALSE),
(4, 1, '<title>', FALSE),

(5, 2, 'True', TRUE),
(6, 2, 'False', FALSE),

(7, 4, 'SELECT', TRUE),
(8, 4, 'UPDATE', FALSE),
(9, 4, 'DELETE', FALSE),
(10, 4, 'DROP', FALSE),

(11, 5, 'True', FALSE),
(12, 5, 'False', TRUE);


INSERT INTO quiz_questions
(
    quiz_id,
    question_id,
    question_order,
    marks
)
VALUES
(2, 1, 1, 10.00),
(2, 2, 2, 10.00),

(5, 4, 1, 10.00),
(5, 5, 2, 10.00);


INSERT INTO exam_questions
(
    exam_id,
    question_id,
    question_order,
    marks
)
VALUES
(3, 1, 1, 20.00),
(3, 2, 2, 20.00),
(3, 3, 3, 60.00),

(6, 4, 1, 20.00),
(6, 5, 2, 20.00),
(6, 6, 3, 60.00);


-- ============================================================
-- 6A. QUIZ / EXAM ATTEMPTS + ANSWERS
-- ============================================================

INSERT INTO assessment_attempts
(
    attempt_id,
    assessment_id,
    student_id,
    attempt_number,
    started_at,
    submitted_at,
    score,
    grade,
    attempt_status
)
VALUES
(1, 2, 1, 1,
 DATE_SUB(NOW(), INTERVAL 30 MINUTE),
 DATE_SUB(NOW(), INTERVAL 10 MINUTE),
 15.00,
 'B',
 'graded'),

(2, 2, 2, 1,
 DATE_SUB(NOW(), INTERVAL 25 MINUTE),
 DATE_SUB(NOW(), INTERVAL 5 MINUTE),
 10.00,
 'C',
 'submitted'),

(3, 5, 3, 1,
 DATE_SUB(NOW(), INTERVAL 20 MINUTE),
 DATE_SUB(NOW(), INTERVAL 3 MINUTE),
 20.00,
 'A',
 'graded');


INSERT INTO attempt_answers
(
    answer_id,
    attempt_id,
    question_id,
    selected_option_id,
    answer_text,
    awarded_marks,
    feedback
)
VALUES
(1, 1, 1, 1, NULL, 10.00, 'Correct.'),
(2, 1, 2, 6, NULL, 5.00, 'Partially credited for demo data.'),

(3, 2, 1, 1, NULL, 10.00, 'Correct.'),
(4, 2, 2, 6, NULL, 0.00, 'Incorrect.'),

(5, 3, 4, 7, NULL, 10.00, 'Correct.'),
(6, 3, 5, 12, NULL, 10.00, 'Correct.');


-- ============================================================
-- 7. COMMUNICATION
-- ============================================================

INSERT INTO announcements
(
    announcement_id,
    batch_id,
    author_user_id,
    title,
    content,
    posted_at,
    expiry_date,
    status
)
VALUES
(1, NULL, 7,
 'Welcome to LearnFlow',
 'Welcome to the LearnFlow demo environment. Please check your dashboard regularly.',
 NOW(),
 DATE_ADD(NOW(), INTERVAL 30 DAY),
 'published'),

(2, 1, 4,
 'Web Development Class Reminder',
 'The next class will cover PHP forms and validation. Bring your laptops.',
 NOW(),
 DATE_ADD(NOW(), INTERVAL 7 DAY),
 'published'),

(3, 2, 5,
 'DBMS Assignment Update',
 'Please review normalization examples before completing the assignment.',
 NOW(),
 DATE_ADD(NOW(), INTERVAL 10 DAY),
 'published');


INSERT INTO notifications
(
    notification_id,
    user_id,
    title,
    message,
    link_url,
    is_read,
    created_at
)
VALUES
(1, 1,
 'New Web Development Assignment',
 'Responsive Portfolio Assignment is now available.',
 'assignments.php',
 FALSE,
 NOW()),

(2, 1,
 'Upcoming DBMS Class',
 'Your SQL Joins and Transactions class is scheduled soon.',
 'schedule.php',
 FALSE,
 NOW()),

(3, 2,
 'Quiz Available',
 'Web Fundamentals Quiz is open for attempts.',
 'quizzes.php',
 TRUE,
 DATE_SUB(NOW(), INTERVAL 1 DAY)),

(4, 3,
 'New DBMS Announcement',
 'A new announcement has been published for your DBMS batch.',
 'announcements.php',
 FALSE,
 NOW());


INSERT INTO messages
(
    message_id,
    sender_user_id,
    receiver_user_id,
    subject,
    content,
    sent_at,
    is_read
)
VALUES
(1, 4, 1,
 'Portfolio Assignment',
 'Please remember to include responsive design screenshots in your submission.',
 DATE_SUB(NOW(), INTERVAL 1 DAY),
 TRUE),

(2, 1, 4,
 'Question about Assignment',
 'Can I use CSS Grid together with Flexbox in the portfolio?',
 NOW(),
 FALSE),

(3, 5, 3,
 'Database Assignment Feedback',
 'Your ER model structure is good. Please check the relationship cardinalities.',
 NOW(),
 FALSE);


INSERT INTO discussion_forums
(
    forum_id,
    batch_id,
    title,
    description,
    created_at,
    status
)
VALUES
(1, 1,
 'Web Development Discussion',
 'Ask questions and discuss Web Development lessons and assignments.',
 NOW(),
 'open'),

(2, 2,
 'Database Systems Discussion',
 'Discuss database design, SQL and assessment questions.',
 NOW(),
 'open');


INSERT INTO discussion_posts
(
    post_id,
    forum_id,
    user_id,
    title,
    content,
    created_at,
    updated_at
)
VALUES
(1, 1, 1,
 'CSS Grid vs Flexbox',
 'When should we prefer CSS Grid over Flexbox for page layouts?',
 DATE_SUB(NOW(), INTERVAL 2 HOUR),
 DATE_SUB(NOW(), INTERVAL 2 HOUR)),

(2, 1, 4,
 'PHP Practice Tips',
 'Use prepared statements when working with user input and databases.',
 DATE_SUB(NOW(), INTERVAL 1 HOUR),
 DATE_SUB(NOW(), INTERVAL 1 HOUR)),

(3, 2, 3,
 'Normalization Question',
 'Can someone explain the difference between partial and transitive dependency?',
 DATE_SUB(NOW(), INTERVAL 90 MINUTE),
 DATE_SUB(NOW(), INTERVAL 90 MINUTE));


INSERT INTO discussion_replies
(
    reply_id,
    post_id,
    user_id,
    content,
    created_at,
    updated_at
)
VALUES
(1, 1, 4,
 'Use Grid for two-dimensional layouts and Flexbox mainly for one-dimensional alignment.',
 DATE_SUB(NOW(), INTERVAL 90 MINUTE),
 DATE_SUB(NOW(), INTERVAL 90 MINUTE)),

(2, 1, 2,
 'I usually use Grid for the page structure and Flexbox inside components.',
 DATE_SUB(NOW(), INTERVAL 60 MINUTE),
 DATE_SUB(NOW(), INTERVAL 60 MINUTE)),

(3, 3, 5,
 'Partial dependency relates to part of a composite key; transitive dependency occurs through another non-key attribute.',
 DATE_SUB(NOW(), INTERVAL 45 MINUTE),
 DATE_SUB(NOW(), INTERVAL 45 MINUTE));


-- ============================================================
-- 8. TUTE PURCHASES + PAYMENTS
-- ============================================================

INSERT INTO tute_purchases
(
    purchase_id,
    student_id,
    tute_resource_id,
    purchase_date,
    amount,
    purchase_status
)
VALUES
(1, 1, 3, DATE_SUB(NOW(), INTERVAL 4 DAY), 500.00, 'completed'),
(2, 3, 6, NOW(), 650.00, 'pending'),
(3, 2, 3, DATE_SUB(NOW(), INTERVAL 2 DAY), 500.00, 'cancelled');


INSERT INTO payments
(
    payment_id,
    student_id,
    amount,
    payment_method,
    payment_date,
    payment_status,
    receipt_no,
    reference_no,
    payment_type
)
VALUES
(1, 1, 9000.00,
 'bank_transfer',
 DATE_SUB(NOW(), INTERVAL 7 DAY),
 'paid',
 'LF-R-0001',
 'BANK-WAD-1001',
 'course'),

(2, 1, 500.00,
 'card',
 DATE_SUB(NOW(), INTERVAL 4 DAY),
 'paid',
 'LF-R-0002',
 'CARD-TUTE-2001',
 'tute'),

(3, 3, 650.00,
 'bank_transfer',
 NOW(),
 'pending',
 NULL,
 'BANK-TUTE-3001',
 'tute'),

(4, 2, 5000.00,
 'cash',
 NOW(),
 'pending',
 NULL,
 'CASH-WAD-4001',
 'course'),

(5, 2, 500.00,
 'bank_transfer',
 DATE_SUB(NOW(), INTERVAL 2 DAY),
 'failed',
 NULL,
 'BANK-TUTE-FAILED-5001',
 'tute');


INSERT INTO course_payments
(
    payment_id,
    enrollment_id
)
VALUES
(1, 1),
(4, 3);


INSERT INTO tute_payments
(
    payment_id,
    purchase_id
)
VALUES
(2, 1),
(3, 2),
(5, 3);


-- ============================================================
-- 9. ACADEMIC REPORTS
-- ============================================================

INSERT INTO academic_reports
(
    report_id,
    student_id,
    course_id,
    term_id,
    generated_by_user_id,
    generated_date,
    overall_grade,
    overall_progress,
    attendance_percentage,
    assessment_performance,
    course_completion_status,
    remarks,
    report_status
)
VALUES
(1, 1, 1, 1, 8,
 NOW(),
 'B+',
 85.00,
 92.00,
 88.00,
 'in_progress',
 'Good overall performance. Continue regular quiz and assignment practice.',
 'published'),

(2, 3, 2, 1, 8,
 NOW(),
 'A-',
 91.00,
 80.00,
 90.00,
 'in_progress',
 'Strong academic performance with good participation.',
 'published');


-- ============================================================
-- 10. SYSTEM ADMINISTRATION
-- ============================================================

INSERT INTO system_settings
(
    setting_id,
    setting_key,
    setting_value,
    updated_by_admin_id,
    updated_at
)
VALUES
(1, 'site_name', 'LearnFlow', 7, NOW()),
(2, 'support_email', 'support@learnflow.demo', 7, NOW()),
(3, 'maintenance_mode', '0', 7, NOW());


INSERT INTO website_content
(
    content_id,
    content_type,
    title,
    content,
    status,
    updated_by_admin_id,
    updated_at
)
VALUES
(1, 'home_banner',
 'Learn Smarter with LearnFlow',
 'Access courses, resources, assessments and progress information from one place.',
 'published',
 7,
 NOW()),

(2, 'about',
 'About LearnFlow',
 'LearnFlow is a Learning Management and Class Administration System for educational institutions.',
 'published',
 7,
 NOW());


INSERT INTO audit_logs
(
    log_id,
    user_id,
    action,
    entity_type,
    entity_id,
    description,
    created_at
)
VALUES
(1, 7,
 'SEED_DATABASE',
 'system',
 NULL,
 'Demo seed data was inserted into LearnFlow.',
 NOW()),

(2, 8,
 'CREATE_COURSE',
 'course',
 1,
 'Created the Web Application Development demo course.',
 DATE_SUB(NOW(), INTERVAL 20 DAY)),

(3, 4,
 'UPLOAD_RESOURCE',
 'learning_resource',
 1,
 'Uploaded HTML and CSS Quick Notes.',
 DATE_SUB(NOW(), INTERVAL 10 DAY));


-- ============================================================
-- FINAL TEST-STATE ADJUSTMENT
-- ============================================================
-- Kavindi is intentionally converted to a Completed enrollment after all
-- Active-only seed operations have been inserted. This gives you a ready
-- account for testing historical read access and blocked new activity.
UPDATE enrollments
SET enrollment_status = 'Completed'
WHERE enrollment_id = 4;

COMMIT;


-- ============================================================
-- VERIFICATION
-- ============================================================
-- There should now be data in all 50 physical/base tables.
-- Reporting views will show rows automatically.

SELECT 'user_accounts' AS table_name, COUNT(*) AS row_count FROM user_accounts
UNION ALL SELECT 'students', COUNT(*) FROM students
UNION ALL SELECT 'teachers', COUNT(*) FROM teachers
UNION ALL SELECT 'parents', COUNT(*) FROM parents
UNION ALL SELECT 'system_administrators', COUNT(*) FROM system_administrators
UNION ALL SELECT 'course_coordinators', COUNT(*) FROM course_coordinators
UNION ALL SELECT 'parent_student', COUNT(*) FROM parent_student
UNION ALL SELECT 'password_reset_tokens', COUNT(*) FROM password_reset_tokens
UNION ALL SELECT 'subjects', COUNT(*) FROM subjects
UNION ALL SELECT 'courses', COUNT(*) FROM courses
UNION ALL SELECT 'academic_terms', COUNT(*) FROM academic_terms
UNION ALL SELECT 'batches', COUNT(*) FROM batches
UNION ALL SELECT 'modules', COUNT(*) FROM modules
UNION ALL SELECT 'teacher_courses', COUNT(*) FROM teacher_courses
UNION ALL SELECT 'enrollments', COUNT(*) FROM enrollments
UNION ALL SELECT 'student_module_progress', COUNT(*) FROM student_module_progress
UNION ALL SELECT 'learning_resources', COUNT(*) FROM learning_resources
UNION ALL SELECT 'study_materials', COUNT(*) FROM study_materials
UNION ALL SELECT 'recordings', COUNT(*) FROM recordings
UNION ALL SELECT 'tutes', COUNT(*) FROM tutes
UNION ALL SELECT 'resource_access_logs', COUNT(*) FROM resource_access_logs
UNION ALL SELECT 'class_sessions', COUNT(*) FROM class_sessions
UNION ALL SELECT 'attendance', COUNT(*) FROM attendance
UNION ALL SELECT 'assessments', COUNT(*) FROM assessments
UNION ALL SELECT 'assignments', COUNT(*) FROM assignments
UNION ALL SELECT 'quizzes', COUNT(*) FROM quizzes
UNION ALL SELECT 'exams', COUNT(*) FROM exams
UNION ALL SELECT 'assignment_submissions', COUNT(*) FROM assignment_submissions
UNION ALL SELECT 'question_banks', COUNT(*) FROM question_banks
UNION ALL SELECT 'questions', COUNT(*) FROM questions
UNION ALL SELECT 'question_options', COUNT(*) FROM question_options
UNION ALL SELECT 'quiz_questions', COUNT(*) FROM quiz_questions
UNION ALL SELECT 'exam_questions', COUNT(*) FROM exam_questions
UNION ALL SELECT 'assessment_attempts', COUNT(*) FROM assessment_attempts
UNION ALL SELECT 'attempt_answers', COUNT(*) FROM attempt_answers
UNION ALL SELECT 'announcements', COUNT(*) FROM announcements
UNION ALL SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL SELECT 'messages', COUNT(*) FROM messages
UNION ALL SELECT 'discussion_forums', COUNT(*) FROM discussion_forums
UNION ALL SELECT 'discussion_posts', COUNT(*) FROM discussion_posts
UNION ALL SELECT 'discussion_replies', COUNT(*) FROM discussion_replies
UNION ALL SELECT 'tute_purchases', COUNT(*) FROM tute_purchases
UNION ALL SELECT 'payments', COUNT(*) FROM payments
UNION ALL SELECT 'course_payments', COUNT(*) FROM course_payments
UNION ALL SELECT 'tute_payments', COUNT(*) FROM tute_payments
UNION ALL SELECT 'academic_reports', COUNT(*) FROM academic_reports
UNION ALL SELECT 'system_settings', COUNT(*) FROM system_settings
UNION ALL SELECT 'website_content', COUNT(*) FROM website_content
UNION ALL SELECT 'audit_logs', COUNT(*) FROM audit_logs
UNION ALL SELECT 'teacher_approvals', COUNT(*) FROM teacher_approvals
ORDER BY table_name;


-- Useful login verification.
SELECT
    user_id,
    fullname,
    email,
    role,
    status
FROM user_accounts
ORDER BY user_id;


-- View examples: no INSERT is required for these.
SELECT * FROM vw_student_course_progress;
SELECT * FROM vw_outstanding_course_fees;
SELECT * FROM vw_payment_receipts;
