-- ============================================================
-- LEARNFLOW - FINAL COMPLETE DATABASE (REQUIREMENTS-ALIGNED)
-- Target: MySQL 8.0+
-- IMPORTANT: Import into an empty learnflow_db database. If an older learnflow_db exists, back it up/drop it first.
-- Covers all documented Student, Teacher, Parent, Academic Coordinator and Administrator requirements; includes EER subtype integrity, security controls, analytics, progress/engagement tracking, payments and compatibility views.
-- Import this into an empty database using phpMyAdmin or MySQL Workbench.
-- ============================================================

CREATE DATABASE IF NOT EXISTS learnflow_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE learnflow_db;
SET NAMES utf8mb4;

-- ============================================================
-- 1. USERS AND ROLE SUBTYPES
-- ============================================================
CREATE TABLE user_accounts (
    user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Store password_hash() output only',
    phone VARCHAR(30) NULL,
    address VARCHAR(255) NULL,
    status ENUM('pending','active','suspended','inactive') NOT NULL DEFAULT 'active',
    role ENUM('Student','Teacher','Parent','Admin','AcademicCoordinator') NOT NULL,
    profile_image VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

CREATE TABLE students (
    student_id INT UNSIGNED PRIMARY KEY,
    registration_no VARCHAR(60) NOT NULL UNIQUE,
    nic VARCHAR(30) NULL UNIQUE,
    date_of_birth DATE NULL,
    gender ENUM('male','female','other','prefer_not_to_say') NULL,
    CONSTRAINT fk_students_user FOREIGN KEY (student_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE teachers (
    teacher_id INT UNSIGNED PRIMARY KEY,
    qualification VARCHAR(255) NULL,
    specialization VARCHAR(255) NULL,
    CONSTRAINT fk_teachers_user FOREIGN KEY (teacher_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE parents (
    parent_id INT UNSIGNED PRIMARY KEY,
    CONSTRAINT fk_parents_user FOREIGN KEY (parent_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE system_administrators (
    admin_id INT UNSIGNED PRIMARY KEY,
    CONSTRAINT fk_admins_user FOREIGN KEY (admin_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE course_coordinators (
    coordinator_id INT UNSIGNED PRIMARY KEY,
    CONSTRAINT fk_coordinators_user FOREIGN KEY (coordinator_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE parent_student (
    parent_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    relationship_type VARCHAR(50) NOT NULL,
    PRIMARY KEY (parent_id, student_id),
    CONSTRAINT fk_parent_student_parent FOREIGN KEY (parent_id) REFERENCES parents(parent_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_parent_student_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 1A. AUTHENTICATION SUPPORT
-- ============================================================
CREATE TABLE password_reset_tokens (
    reset_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE COMMENT 'Store SHA-256 hash of the reset token, never the raw token',
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_password_reset_user_expiry (user_id, expires_at),
    INDEX idx_password_reset_expiry_used (expires_at, used_at)
) ENGINE=InnoDB;

-- ============================================================
-- 2. ACADEMIC STRUCTURE
-- ============================================================
-- SUBJECT is a reusable academic catalog item. Courses may optionally be linked
-- to a subject so the Administrator requirement "manage subjects" is represented
-- without breaking the current PHP pages that create/read courses directly.
CREATE TABLE subjects (
    subject_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(40) NULL UNIQUE,
    subject_name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE courses (
    course_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coordinator_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NULL,
    course_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    course_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('draft','active','inactive','completed') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_courses_fee CHECK (course_fee >= 0),
    INDEX idx_courses_subject_status (subject_id, status),
    CONSTRAINT fk_courses_subject FOREIGN KEY (subject_id) REFERENCES subjects(subject_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_courses_coordinator FOREIGN KEY (coordinator_id) REFERENCES course_coordinators(coordinator_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE academic_terms (
    term_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    planned_by_coordinator_id INT UNSIGNED NOT NULL,
    term_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
    CONSTRAINT chk_term_dates CHECK (end_date >= start_date),
    CONSTRAINT fk_terms_coordinator FOREIGN KEY (planned_by_coordinator_id) REFERENCES course_coordinators(coordinator_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE batches (
    batch_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    batch_name VARCHAR(120) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('planned','active','completed','cancelled') NOT NULL DEFAULT 'planned',
    CONSTRAINT chk_batch_dates CHECK (end_date IS NULL OR end_date >= start_date),
    CONSTRAINT fk_batches_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_batches_term FOREIGN KEY (term_id) REFERENCES academic_terms(term_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_batch_course_term_name (course_id, term_id, batch_name)
) ENGINE=InnoDB;

CREATE TABLE modules (
    module_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    module_name VARCHAR(150) NOT NULL,
    module_order INT UNSIGNED NOT NULL,
    description TEXT NULL,
    CONSTRAINT fk_modules_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_module_order (course_id, module_order),
    UNIQUE KEY uq_module_name (course_id, module_name)
) ENGINE=InnoDB;

-- Associative entity: TEACHER M:N COURSE
CREATE TABLE teacher_courses (
    teacher_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    assigned_by_coordinator_id INT UNSIGNED NOT NULL,
    assigned_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    PRIMARY KEY (teacher_id, course_id),
    CONSTRAINT fk_teacher_courses_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_teacher_courses_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_teacher_courses_coordinator FOREIGN KEY (assigned_by_coordinator_id) REFERENCES course_coordinators(coordinator_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Associative entity: STUDENT M:N BATCH
CREATE TABLE enrollments (
    enrollment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    batch_id INT UNSIGNED NOT NULL,
    enrollment_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    enrollment_status ENUM('Pending','Active','Completed','Withdrawn','Cancelled') NOT NULL DEFAULT 'Active',
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_student_batch (student_id, batch_id)
) ENGINE=InnoDB;

-- ============================================================
-- 2A. STUDENT MODULE PROGRESS
-- ============================================================
CREATE TABLE student_module_progress (
    progress_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    module_id INT UNSIGNED NOT NULL,
    progress_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    progress_status ENUM('not_started','in_progress','completed') NOT NULL DEFAULT 'not_started',
    last_accessed_at DATETIME NULL,
    completed_at DATETIME NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_module_progress_percentage
        CHECK (progress_percentage BETWEEN 0 AND 100),
    CONSTRAINT fk_module_progress_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_module_progress_module FOREIGN KEY (module_id) REFERENCES modules(module_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_student_module_progress (student_id, module_id),
    INDEX idx_module_progress_student_status (student_id, progress_status)
) ENGINE=InnoDB;

-- ============================================================
-- 3. LEARNING RESOURCES
-- LEARNING_RESOURCE ISA STUDY_MATERIAL / RECORDING / TUTE
-- ============================================================
CREATE TABLE learning_resources (
    resource_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module_id INT UNSIGNED NOT NULL,
    uploaded_by_user_id INT UNSIGNED NOT NULL,
    approved_by_coordinator_id INT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    file_url VARCHAR(500) NOT NULL,
    upload_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    resource_type ENUM('study_material','recording','tute') NOT NULL,
    CONSTRAINT fk_resources_module FOREIGN KEY (module_id) REFERENCES modules(module_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_resources_uploader FOREIGN KEY (uploaded_by_user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_resources_approver FOREIGN KEY (approved_by_coordinator_id) REFERENCES course_coordinators(coordinator_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE study_materials (
    resource_id INT UNSIGNED PRIMARY KEY,
    material_type VARCHAR(80) NOT NULL,
    CONSTRAINT fk_study_material_resource FOREIGN KEY (resource_id) REFERENCES learning_resources(resource_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE recordings (
    resource_id INT UNSIGNED PRIMARY KEY,
    duration_seconds INT UNSIGNED NULL,
    recorded_date DATE NULL,
    CONSTRAINT fk_recording_resource FOREIGN KEY (resource_id) REFERENCES learning_resources(resource_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tutes (
    resource_id INT UNSIGNED PRIMARY KEY,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    availability_status ENUM('available','unavailable','archived') NOT NULL DEFAULT 'available',
    CONSTRAINT chk_tute_price CHECK (price >= 0),
    CONSTRAINT fk_tute_resource FOREIGN KEY (resource_id) REFERENCES learning_resources(resource_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 3A. STUDENT RESOURCE ACCESS / ENGAGEMENT
-- ============================================================
CREATE TABLE resource_access_logs (
    access_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    resource_id INT UNSIGNED NOT NULL,
    access_type ENUM('view','download','watch') NOT NULL,
    watched_seconds INT UNSIGNED NULL,
    completed BOOLEAN NOT NULL DEFAULT FALSE,
    accessed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resource_access_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_resource_access_resource FOREIGN KEY (resource_id) REFERENCES learning_resources(resource_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_resource_access_student_date (student_id, accessed_at),
    INDEX idx_resource_access_resource_date (resource_id, accessed_at)
) ENGINE=InnoDB;

-- ============================================================
-- 4. CLASS SESSIONS AND ATTENDANCE
-- ============================================================
CREATE TABLE class_sessions (
    session_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    class_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(150) NULL,
    topic VARCHAR(255) NULL,
    status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    INDEX idx_sessions_batch_date (batch_id, class_date, start_time),
    CONSTRAINT chk_session_time CHECK (end_time > start_time),
    CONSTRAINT fk_sessions_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_sessions_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Associative entity: STUDENT M:N CLASS_SESSION
CREATE TABLE attendance (
    attendance_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    attendance_status ENUM('present','absent','late','excused') NOT NULL,
    marked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_session FOREIGN KEY (session_id) REFERENCES class_sessions(session_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uq_attendance_session_student (session_id, student_id)
) ENGINE=InnoDB;

-- ============================================================
-- 5. ASSESSMENTS
-- ASSESSMENT ISA ASSIGNMENT / QUIZ / EXAM
-- ============================================================
CREATE TABLE assessments (
    assessment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    open_date DATETIME NULL,
    close_date DATETIME NULL,
    total_marks DECIMAL(7,2) NOT NULL,
    assessment_type ENUM('assignment','quiz','exam') NOT NULL,
    status ENUM('draft','published','closed','archived') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_assessments_batch_type_status (batch_id, assessment_type, status),
    INDEX idx_assessments_teacher_status (teacher_id, status),
    CONSTRAINT chk_assessment_marks CHECK (total_marks >= 0),
    CONSTRAINT chk_assessment_dates CHECK (close_date IS NULL OR open_date IS NULL OR close_date >= open_date),
    CONSTRAINT fk_assessments_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_assessments_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE assignments (
    assignment_id INT UNSIGNED PRIMARY KEY,
    due_date DATETIME NOT NULL,
    instructions TEXT NULL,
    submission_type ENUM('file','text','both') NOT NULL DEFAULT 'file',
    max_file_size_mb INT UNSIGNED NULL,
    allow_late_submission BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_assignment_assessment FOREIGN KEY (assignment_id) REFERENCES assessments(assessment_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quizzes (
    quiz_id INT UNSIGNED PRIMARY KEY,
    duration_minutes INT UNSIGNED NOT NULL,
    attempt_limit INT UNSIGNED NOT NULL DEFAULT 1,
    passing_marks DECIMAL(7,2) NOT NULL DEFAULT 0,
    randomize_questions BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_quiz_assessment FOREIGN KEY (quiz_id) REFERENCES assessments(assessment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_quiz_attempt_limit CHECK (attempt_limit >= 1),
    CONSTRAINT chk_quiz_pass_marks CHECK (passing_marks >= 0)
) ENGINE=InnoDB;

CREATE TABLE exams (
    exam_id INT UNSIGNED PRIMARY KEY,
    exam_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(150) NULL,
    duration_minutes INT UNSIGNED NULL,
    attempt_limit INT UNSIGNED NOT NULL DEFAULT 1,
    passing_marks DECIMAL(7,2) NOT NULL DEFAULT 0,
    CONSTRAINT fk_exam_assessment FOREIGN KEY (exam_id) REFERENCES assessments(assessment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_exam_time CHECK (end_time > start_time),
    CONSTRAINT chk_exam_attempt_limit CHECK (attempt_limit >= 1),
    CONSTRAINT chk_exam_pass_marks CHECK (passing_marks >= 0)
) ENGINE=InnoDB;

-- Associative entity: STUDENT M:N ASSIGNMENT
CREATE TABLE assignment_submissions (
    submission_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    file_url VARCHAR(500) NULL,
    text_answer LONGTEXT NULL,
    submission_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submission_status ENUM('Submitted','Late','Graded') NOT NULL DEFAULT 'Submitted',
    marks DECIMAL(7,2) NULL,
    feedback TEXT NULL,
    graded_by_teacher_id INT UNSIGNED NULL,
    graded_at DATETIME NULL,
    INDEX idx_submission_student_date (student_id, submission_date),
    INDEX idx_submission_status (submission_status),
    CONSTRAINT fk_submission_assignment FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_submission_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_submission_grader FOREIGN KEY (graded_by_teacher_id) REFERENCES teachers(teacher_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_submission_marks CHECK (marks IS NULL OR marks >= 0),
    UNIQUE KEY uq_assignment_student (assignment_id, student_id)
) ENGINE=InnoDB;

-- ============================================================
-- 6. QUESTION BANK
-- Questions are used only by QUIZ and EXAM.
-- ============================================================
CREATE TABLE question_banks (
    question_bank_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    coordinator_id INT UNSIGNED NOT NULL,
    bank_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    CONSTRAINT fk_question_bank_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_question_bank_coordinator FOREIGN KEY (coordinator_id) REFERENCES course_coordinators(coordinator_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE questions (
    question_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_bank_id INT UNSIGNED NOT NULL,
    question_text LONGTEXT NOT NULL,
    question_type ENUM('mcq','true_false','short_answer','essay') NOT NULL,
    default_marks DECIMAL(7,2) NOT NULL DEFAULT 1.00,
    CONSTRAINT chk_question_marks CHECK (default_marks >= 0),
    CONSTRAINT fk_questions_bank FOREIGN KEY (question_bank_id) REFERENCES question_banks(question_bank_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE question_options (
    option_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id INT UNSIGNED NOT NULL,
    option_text TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT fk_question_options_question FOREIGN KEY (question_id) REFERENCES questions(question_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Associative entity: QUIZ M:N QUESTION
CREATE TABLE quiz_questions (
    quiz_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    question_order INT UNSIGNED NOT NULL,
    marks DECIMAL(7,2) NOT NULL,
    PRIMARY KEY (quiz_id, question_id),
    CONSTRAINT chk_quiz_question_marks CHECK (marks >= 0),
    CONSTRAINT fk_quiz_questions_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_quiz_questions_question FOREIGN KEY (question_id) REFERENCES questions(question_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_quiz_question_order (quiz_id, question_order)
) ENGINE=InnoDB;

-- Associative entity: EXAM M:N QUESTION
CREATE TABLE exam_questions (
    exam_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    question_order INT UNSIGNED NOT NULL,
    marks DECIMAL(7,2) NOT NULL,
    PRIMARY KEY (exam_id, question_id),
    CONSTRAINT chk_exam_question_marks CHECK (marks >= 0),
    CONSTRAINT fk_exam_questions_exam FOREIGN KEY (exam_id) REFERENCES exams(exam_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_exam_questions_question FOREIGN KEY (question_id) REFERENCES questions(question_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_exam_question_order (exam_id, question_order)
) ENGINE=InnoDB;

-- Associative/event entity: STUDENT M:N QUIZ/EXAM assessment
CREATE TABLE assessment_attempts (
    attempt_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    attempt_number INT UNSIGNED NOT NULL DEFAULT 1,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    score DECIMAL(7,2) NULL,
    grade VARCHAR(20) NULL,
    attempt_status ENUM('in_progress','submitted','graded','abandoned') NOT NULL DEFAULT 'in_progress',
    INDEX idx_attempt_student_assessment_status (student_id, assessment_id, attempt_status),
    INDEX idx_attempt_assessment_status (assessment_id, attempt_status),
    CONSTRAINT fk_attempt_assessment FOREIGN KEY (assessment_id) REFERENCES assessments(assessment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_attempt_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_attempt_number CHECK (attempt_number >= 1),
    CONSTRAINT chk_attempt_score CHECK (score IS NULL OR score >= 0),
    UNIQUE KEY uq_student_assessment_attempt (assessment_id, student_id, attempt_number)
) ENGINE=InnoDB;

CREATE TABLE attempt_answers (
    answer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected_option_id INT UNSIGNED NULL,
    answer_text LONGTEXT NULL,
    awarded_marks DECIMAL(7,2) NULL,
    feedback TEXT NULL,
    CONSTRAINT fk_answer_attempt FOREIGN KEY (attempt_id) REFERENCES assessment_attempts(attempt_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_answer_question FOREIGN KEY (question_id) REFERENCES questions(question_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_answer_option FOREIGN KEY (selected_option_id) REFERENCES question_options(option_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT chk_answer_marks CHECK (awarded_marks IS NULL OR awarded_marks >= 0),
    UNIQUE KEY uq_attempt_question (attempt_id, question_id)
) ENGINE=InnoDB;

-- ============================================================
-- 7. COMMUNICATION
-- ============================================================
CREATE TABLE announcements (
    announcement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id INT UNSIGNED NULL COMMENT 'NULL means system-wide announcement',
    author_user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    content LONGTEXT NOT NULL,
    posted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATETIME NULL,
    status ENUM('draft','published','expired','archived') NOT NULL DEFAULT 'published',
    CONSTRAINT fk_announcement_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_announcement_author FOREIGN KEY (author_user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE notifications (
    notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(500) NULL,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_read_created (user_id, is_read, created_at),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
    message_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT UNSIGNED NOT NULL,
    receiver_user_id INT UNSIGNED NOT NULL,
    subject VARCHAR(180) NULL,
    content LONGTEXT NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    INDEX idx_messages_receiver_read_sent (receiver_user_id, is_read, sent_at),
    INDEX idx_messages_sender_sent (sender_user_id, sent_at),
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE discussion_forums (
    forum_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open','closed','archived') NOT NULL DEFAULT 'open',
    CONSTRAINT fk_forums_batch FOREIGN KEY (batch_id) REFERENCES batches(batch_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE discussion_posts (
    post_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    forum_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    content LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_discussion_posts_forum_created (forum_id, created_at),
    CONSTRAINT fk_posts_forum FOREIGN KEY (forum_id) REFERENCES discussion_forums(forum_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE discussion_replies (
    reply_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    content LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_replies_post FOREIGN KEY (post_id) REFERENCES discussion_posts(post_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_replies_user FOREIGN KEY (user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. TUTE PURCHASES AND PAYMENTS
-- PAYMENT ISA COURSE_PAYMENT / TUTE_PAYMENT
-- ============================================================
CREATE TABLE tute_purchases (
    purchase_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    tute_resource_id INT UNSIGNED NOT NULL,
    purchase_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10,2) NOT NULL,
    purchase_status ENUM('pending','completed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    CONSTRAINT chk_purchase_amount CHECK (amount >= 0),
    CONSTRAINT fk_purchase_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_purchase_tute FOREIGN KEY (tute_resource_id) REFERENCES tutes(resource_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_student_tute (student_id, tute_resource_id)
) ENGINE=InnoDB;

CREATE TABLE payments (
    payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','bank_transfer','card','other') NOT NULL,
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending','paid','failed','cancelled','refunded') NOT NULL DEFAULT 'pending',
    receipt_no VARCHAR(100) NULL UNIQUE,
    reference_no VARCHAR(150) NULL,
    payment_type ENUM('course','tute') NOT NULL,
    INDEX idx_payments_student_date (student_id, payment_date),
    INDEX idx_payments_status_type (payment_status, payment_type),
    CONSTRAINT chk_payment_amount CHECK (amount >= 0),
    CONSTRAINT fk_payments_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE course_payments (
    payment_id INT UNSIGNED PRIMARY KEY,
    enrollment_id INT UNSIGNED NOT NULL,
    CONSTRAINT fk_course_payment_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_course_payment_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(enrollment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE tute_payments (
    payment_id INT UNSIGNED PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL UNIQUE,
    CONSTRAINT fk_tute_payment_payment FOREIGN KEY (payment_id) REFERENCES payments(payment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_tute_payment_purchase FOREIGN KEY (purchase_id) REFERENCES tute_purchases(purchase_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 9. ACADEMIC REPORTS
-- Replaces MODULE_PROGRESS
-- ============================================================
CREATE TABLE academic_reports (
    report_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    term_id INT UNSIGNED NOT NULL,
    generated_by_user_id INT UNSIGNED NOT NULL,
    generated_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    overall_grade VARCHAR(20) NULL,
    overall_progress DECIMAL(5,2) NULL,
    attendance_percentage DECIMAL(5,2) NULL,
    assessment_performance DECIMAL(5,2) NULL,
    course_completion_status ENUM('not_started','in_progress','completed','not_completed') NOT NULL DEFAULT 'in_progress',
    remarks TEXT NULL,
    report_status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    INDEX idx_reports_student_status_date (student_id, report_status, generated_date),
    CONSTRAINT chk_report_progress CHECK (overall_progress IS NULL OR (overall_progress BETWEEN 0 AND 100)),
    CONSTRAINT chk_report_attendance CHECK (attendance_percentage IS NULL OR (attendance_percentage BETWEEN 0 AND 100)),
    CONSTRAINT chk_report_performance CHECK (assessment_performance IS NULL OR (assessment_performance BETWEEN 0 AND 100)),
    CONSTRAINT fk_report_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_report_course FOREIGN KEY (course_id) REFERENCES courses(course_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_report_term FOREIGN KEY (term_id) REFERENCES academic_terms(term_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_report_generator FOREIGN KEY (generated_by_user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    UNIQUE KEY uq_student_course_term_report (student_id, course_id, term_id)
) ENGINE=InnoDB;

-- ============================================================
-- 10. SYSTEM ADMINISTRATION
-- ============================================================
CREATE TABLE system_settings (
    setting_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_by_admin_id INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_settings_admin FOREIGN KEY (updated_by_admin_id) REFERENCES system_administrators(admin_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE website_content (
    content_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    content_type VARCHAR(80) NOT NULL,
    title VARCHAR(180) NOT NULL,
    content LONGTEXT NOT NULL,
    status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    updated_by_admin_id INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_website_content_admin FOREIGN KEY (updated_by_admin_id) REFERENCES system_administrators(admin_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NULL,
    entity_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES user_accounts(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE teacher_approvals (
    approval_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    decision ENUM('approved','rejected') NOT NULL,
    decision_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remarks TEXT NULL,
    INDEX idx_teacher_approval_teacher_date (teacher_id, decision_date),
    CONSTRAINT fk_teacher_approval_admin FOREIGN KEY (admin_id) REFERENCES system_administrators(admin_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_teacher_approval_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 11. BUSINESS-RULE / ISA INTEGRITY TRIGGERS
-- ============================================================

DELIMITER $$


-- ============================================================
-- USER ROLE / SUBTYPE VALIDATION
-- ============================================================
-- Public/self registration must never create privileged accounts.
-- An administrator-controlled process may temporarily set
-- @learnflow_allow_privileged_user_create = 1 for an authorized insert.
CREATE TRIGGER trg_user_accounts_before_insert
BEFORE INSERT ON user_accounts
FOR EACH ROW
BEGIN
    IF NEW.role IN ('Admin','AcademicCoordinator')
       AND COALESCE(@learnflow_allow_privileged_user_create, 0) <> 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Privileged Admin/Coordinator accounts must be created by an authorized administrator';
    END IF;

    IF NEW.role = 'Teacher' THEN
        SET NEW.status = 'pending';
    ELSEIF NEW.status = 'pending' THEN
        SET NEW.status = 'active';
    END IF;
END$$


CREATE TRIGGER trg_student_role_before_insert
BEFORE INSERT ON students
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);

    SELECT role INTO v_role
    FROM user_accounts
    WHERE user_id = NEW.student_id;

    IF v_role IS NULL OR v_role <> 'Student' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Student subtype requires USER role=Student';
    END IF;
END$$


CREATE TRIGGER trg_teacher_role_before_insert
BEFORE INSERT ON teachers
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);

    SELECT role INTO v_role
    FROM user_accounts
    WHERE user_id = NEW.teacher_id;

    IF v_role IS NULL OR v_role <> 'Teacher' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Teacher subtype requires USER role=Teacher';
    END IF;
END$$


CREATE TRIGGER trg_parent_role_before_insert
BEFORE INSERT ON parents
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);

    SELECT role INTO v_role
    FROM user_accounts
    WHERE user_id = NEW.parent_id;

    IF v_role IS NULL OR v_role <> 'Parent' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Parent subtype requires USER role=Parent';
    END IF;
END$$


CREATE TRIGGER trg_admin_role_before_insert
BEFORE INSERT ON system_administrators
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);

    SELECT role INTO v_role
    FROM user_accounts
    WHERE user_id = NEW.admin_id;

    IF v_role IS NULL OR v_role <> 'Admin' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Administrator subtype requires USER role=Admin';
    END IF;
END$$


CREATE TRIGGER trg_coordinator_role_before_insert
BEFORE INSERT ON course_coordinators
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);

    SELECT role INTO v_role
    FROM user_accounts
    WHERE user_id = NEW.coordinator_id;

    IF v_role IS NULL OR v_role <> 'AcademicCoordinator' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Coordinator subtype requires USER role=AcademicCoordinator';
    END IF;
END$$


-- ============================================================
-- STEP 1: PREVENT DIRECT USER ROLE CHANGES
-- ============================================================

CREATE TRIGGER trg_user_accounts_prevent_role_change
BEFORE UPDATE ON user_accounts
FOR EACH ROW
BEGIN
    IF NEW.role <> OLD.role THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User role cannot be changed directly after account creation';
    END IF;
END$$


-- ============================================================
-- LEARNING RESOURCE VALIDATION
-- ============================================================

CREATE TRIGGER trg_resource_uploader_before_insert
BEFORE INSERT ON learning_resources
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);
    DECLARE v_course_id INT UNSIGNED;
    DECLARE v_allowed INT DEFAULT 0;
    DECLARE v_course_coordinator INT UNSIGNED;

    SELECT role INTO v_role
    FROM user_accounts
    WHERE user_id = NEW.uploaded_by_user_id;

    SELECT m.course_id, c.coordinator_id
    INTO v_course_id, v_course_coordinator
    FROM modules m
    INNER JOIN courses c ON c.course_id = m.course_id
    WHERE m.module_id = NEW.module_id;

    IF v_role = 'Teacher' THEN
        SELECT COUNT(*) INTO v_allowed
        FROM teacher_courses
        WHERE teacher_id = NEW.uploaded_by_user_id
          AND course_id = v_course_id;

        IF v_allowed = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Teacher may upload resources only to an assigned course';
        END IF;
    ELSEIF v_role <> 'Admin' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only Teacher or System Administrator may upload learning resources';
    END IF;

    IF NEW.approved_by_coordinator_id IS NOT NULL
       AND NEW.approved_by_coordinator_id <> v_course_coordinator THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Resource approval must be performed by the coordinator responsible for the course';
    END IF;
END$$

CREATE TRIGGER trg_resource_uploader_before_update
BEFORE UPDATE ON learning_resources
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);
    DECLARE v_course_id INT UNSIGNED;
    DECLARE v_allowed INT DEFAULT 0;
    DECLARE v_course_coordinator INT UNSIGNED;

    SELECT role INTO v_role FROM user_accounts WHERE user_id = NEW.uploaded_by_user_id;
    SELECT m.course_id, c.coordinator_id
    INTO v_course_id, v_course_coordinator
    FROM modules m
    INNER JOIN courses c ON c.course_id = m.course_id
    WHERE m.module_id = NEW.module_id;

    IF v_role = 'Teacher' THEN
        SELECT COUNT(*) INTO v_allowed
        FROM teacher_courses
        WHERE teacher_id = NEW.uploaded_by_user_id
          AND course_id = v_course_id;
        IF v_allowed = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Teacher may manage resources only for an assigned course';
        END IF;
    ELSEIF v_role <> 'Admin' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Only Teacher or System Administrator may manage learning resources';
    END IF;

    IF NEW.approved_by_coordinator_id IS NOT NULL
       AND NEW.approved_by_coordinator_id <> v_course_coordinator THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Resource approval must be performed by the coordinator responsible for the course';
    END IF;
END$$

CREATE TRIGGER trg_study_material_type_before_insert
BEFORE INSERT ON study_materials
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);

    SELECT resource_type INTO v_type
    FROM learning_resources
    WHERE resource_id = NEW.resource_id;

    IF v_type IS NULL OR v_type <> 'study_material' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Study material subtype requires resource_type=study_material';
    END IF;
END$$


CREATE TRIGGER trg_recording_type_before_insert
BEFORE INSERT ON recordings
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);

    SELECT resource_type INTO v_type
    FROM learning_resources
    WHERE resource_id = NEW.resource_id;

    IF v_type IS NULL OR v_type <> 'recording' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Recording subtype requires resource_type=recording';
    END IF;
END$$


CREATE TRIGGER trg_tute_type_before_insert
BEFORE INSERT ON tutes
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);

    SELECT resource_type INTO v_type
    FROM learning_resources
    WHERE resource_id = NEW.resource_id;

    IF v_type IS NULL OR v_type <> 'tute' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tute subtype requires resource_type=tute';
    END IF;
END$$


-- ============================================================
-- TEACHER / COURSE ASSIGNMENT VALIDATION
-- ============================================================
CREATE TRIGGER trg_class_session_teacher_before_insert
BEFORE INSERT ON class_sessions
FOR EACH ROW
BEGIN
    DECLARE v_ok INT DEFAULT 0;
    SELECT COUNT(*) INTO v_ok
    FROM batches b
    INNER JOIN teacher_courses tc ON tc.course_id = b.course_id
    WHERE b.batch_id = NEW.batch_id AND tc.teacher_id = NEW.teacher_id;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Class session teacher must be assigned to the batch course';
    END IF;
END$$

CREATE TRIGGER trg_class_session_teacher_before_update
BEFORE UPDATE ON class_sessions
FOR EACH ROW
BEGIN
    DECLARE v_ok INT DEFAULT 0;
    SELECT COUNT(*) INTO v_ok
    FROM batches b
    INNER JOIN teacher_courses tc ON tc.course_id = b.course_id
    WHERE b.batch_id = NEW.batch_id AND tc.teacher_id = NEW.teacher_id;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Class session teacher must be assigned to the batch course';
    END IF;
END$$

CREATE TRIGGER trg_assessment_teacher_before_insert
BEFORE INSERT ON assessments
FOR EACH ROW
BEGIN
    DECLARE v_ok INT DEFAULT 0;
    SELECT COUNT(*) INTO v_ok
    FROM batches b
    INNER JOIN teacher_courses tc ON tc.course_id = b.course_id
    WHERE b.batch_id = NEW.batch_id AND tc.teacher_id = NEW.teacher_id;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assessment teacher must be assigned to the batch course';
    END IF;
END$$

CREATE TRIGGER trg_assessment_teacher_before_update
BEFORE UPDATE ON assessments
FOR EACH ROW
BEGIN
    DECLARE v_ok INT DEFAULT 0;
    SELECT COUNT(*) INTO v_ok
    FROM batches b
    INNER JOIN teacher_courses tc ON tc.course_id = b.course_id
    WHERE b.batch_id = NEW.batch_id AND tc.teacher_id = NEW.teacher_id;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assessment teacher must be assigned to the batch course';
    END IF;
END$$

-- ============================================================
-- STEP 2: ATTENDANCE ENROLLMENT VALIDATION
-- ============================================================

CREATE TRIGGER trg_attendance_enrollment_before_insert
BEFORE INSERT ON attendance
FOR EACH ROW
BEGIN
    DECLARE v_enrolled INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_enrolled
    FROM class_sessions cs
    INNER JOIN enrollments e ON e.batch_id = cs.batch_id
    WHERE cs.session_id = NEW.session_id
      AND e.student_id = NEW.student_id
      AND e.enrollment_status IN ('Active','Completed');

    IF v_enrolled = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Attendance cannot be recorded: student is not enrolled in this batch';
    END IF;
END$$


CREATE TRIGGER trg_attendance_enrollment_before_update
BEFORE UPDATE ON attendance
FOR EACH ROW
BEGIN
    DECLARE v_enrolled INT DEFAULT 0;

    IF NEW.student_id <> OLD.student_id
       OR NEW.session_id <> OLD.session_id THEN

        SELECT COUNT(*)
        INTO v_enrolled
        FROM class_sessions cs
        INNER JOIN enrollments e ON e.batch_id = cs.batch_id
        WHERE cs.session_id = NEW.session_id
          AND e.student_id = NEW.student_id
          AND e.enrollment_status IN ('Active','Completed');

        IF v_enrolled = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Attendance cannot be updated: student is not enrolled in this batch';
        END IF;
    END IF;
END$$


-- ============================================================
-- ASSESSMENT SUBTYPE VALIDATION
-- ============================================================

CREATE TRIGGER trg_assignment_type_before_insert
BEFORE INSERT ON assignments
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);

    SELECT assessment_type INTO v_type
    FROM assessments
    WHERE assessment_id = NEW.assignment_id;

    IF v_type IS NULL OR v_type <> 'assignment' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assignment subtype requires assessment_type=assignment';
    END IF;
END$$


CREATE TRIGGER trg_quiz_type_before_insert
BEFORE INSERT ON quizzes
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);

    SELECT assessment_type INTO v_type
    FROM assessments
    WHERE assessment_id = NEW.quiz_id;

    IF v_type IS NULL OR v_type <> 'quiz' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Quiz subtype requires assessment_type=quiz';
    END IF;
END$$


CREATE TRIGGER trg_exam_type_before_insert
BEFORE INSERT ON exams
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);

    SELECT assessment_type INTO v_type
    FROM assessments
    WHERE assessment_id = NEW.exam_id;

    IF v_type IS NULL OR v_type <> 'exam' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Exam subtype requires assessment_type=exam';
    END IF;
END$$


-- ============================================================
-- STEPS 3 + 4: ASSIGNMENT ENROLLMENT AND MARK VALIDATION
-- ============================================================

CREATE TRIGGER trg_assignment_submission_before_insert
BEFORE INSERT ON assignment_submissions
FOR EACH ROW
BEGIN
    DECLARE v_enrolled INT DEFAULT 0;
    DECLARE v_total_marks DECIMAL(7,2);
    DECLARE v_due_date DATETIME;
    DECLARE v_allow_late BOOLEAN;
    DECLARE v_status VARCHAR(30);

    SELECT COUNT(*), MAX(a.total_marks), MAX(ass.due_date), MAX(ass.allow_late_submission), MAX(a.status)
    INTO v_enrolled, v_total_marks, v_due_date, v_allow_late, v_status
    FROM assignments ass
    INNER JOIN assessments a ON a.assessment_id = ass.assignment_id
    INNER JOIN enrollments e ON e.batch_id = a.batch_id
    WHERE ass.assignment_id = NEW.assignment_id
      AND e.student_id = NEW.student_id
      AND e.enrollment_status = 'Active';

    IF v_enrolled = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assignment submission denied: student is not actively enrolled in this batch';
    END IF;
    IF v_status <> 'published' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assignment is not published for submission';
    END IF;
    IF CURRENT_TIMESTAMP > v_due_date THEN
        IF v_allow_late = FALSE THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assignment deadline has passed';
        ELSE
            SET NEW.submission_status = 'Late';
        END IF;
    ELSEIF NEW.submission_status <> 'Graded' THEN
        SET NEW.submission_status = 'Submitted';
    END IF;
    IF NEW.marks IS NOT NULL AND (NEW.marks < 0 OR NEW.marks > v_total_marks) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assignment marks must be between 0 and the assessment total marks';
    END IF;
END$$

CREATE TRIGGER trg_assignment_submission_before_update
BEFORE UPDATE ON assignment_submissions
FOR EACH ROW
BEGIN
    DECLARE v_enrolled INT DEFAULT 0;
    DECLARE v_total_marks DECIMAL(7,2);

    IF NEW.student_id <> OLD.student_id
       OR NEW.assignment_id <> OLD.assignment_id THEN

        SELECT COUNT(*)
        INTO v_enrolled
        FROM assignments ass
        INNER JOIN assessments a
            ON a.assessment_id = ass.assignment_id
        INNER JOIN enrollments e
            ON e.batch_id = a.batch_id
        WHERE ass.assignment_id = NEW.assignment_id
          AND e.student_id = NEW.student_id
          AND e.enrollment_status = 'Active';

        IF v_enrolled = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Assignment submission update denied: student is not actively enrolled in this batch';
        END IF;
    END IF;

    IF NEW.marks IS NOT NULL THEN
        SELECT a.total_marks
        INTO v_total_marks
        FROM assignments ass
        INNER JOIN assessments a
            ON a.assessment_id = ass.assignment_id
        WHERE ass.assignment_id = NEW.assignment_id;

        IF NEW.marks < 0 OR NEW.marks > v_total_marks THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Assignment marks must be between 0 and the assessment total marks';
        END IF;
    END IF;
END$$


-- ============================================================
-- STEPS 3 + 4 + 5: QUIZ/EXAM ATTEMPT VALIDATION
-- ============================================================

CREATE TRIGGER trg_assessment_attempt_before_insert
BEFORE INSERT ON assessment_attempts
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);
    DECLARE v_enrolled INT DEFAULT 0;
    DECLARE v_total_marks DECIMAL(7,2);
    DECLARE v_attempt_limit INT DEFAULT 0;
    DECLARE v_attempt_count INT DEFAULT 0;
    DECLARE v_open_date DATETIME;
    DECLARE v_close_date DATETIME;
    DECLARE v_status VARCHAR(30);

    SELECT a.assessment_type, a.total_marks, a.open_date, a.close_date, a.status
    INTO v_type, v_total_marks, v_open_date, v_close_date, v_status
    FROM assessments a
    WHERE a.assessment_id = NEW.assessment_id;

    IF v_type IS NULL OR v_type NOT IN ('quiz','exam') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assessment attempts are allowed only for Quiz or Exam';
    END IF;
    IF v_status <> 'published' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assessment is not published';
    END IF;
    IF v_open_date IS NOT NULL AND CURRENT_TIMESTAMP < v_open_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assessment is not open yet';
    END IF;
    IF v_close_date IS NOT NULL AND CURRENT_TIMESTAMP > v_close_date THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Assessment has closed';
    END IF;

    SELECT COUNT(*)
    INTO v_enrolled
    FROM assessments a
    INNER JOIN enrollments e
        ON e.batch_id = a.batch_id
    WHERE a.assessment_id = NEW.assessment_id
      AND e.student_id = NEW.student_id
      AND e.enrollment_status = 'Active';

    IF v_enrolled = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assessment attempt denied: student is not actively enrolled in this batch';
    END IF;

    IF NEW.score IS NOT NULL
       AND (NEW.score < 0 OR NEW.score > v_total_marks) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assessment score must be between 0 and the assessment total marks';
    END IF;

    IF v_type = 'quiz' THEN
        SELECT attempt_limit INTO v_attempt_limit
        FROM quizzes
        WHERE quiz_id = NEW.assessment_id;
    ELSE
        SELECT attempt_limit INTO v_attempt_limit
        FROM exams
        WHERE exam_id = NEW.assessment_id;
    END IF;

    IF NEW.attempt_number < 1 OR NEW.attempt_number > v_attempt_limit THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Attempt number exceeds the allowed attempt limit';
    END IF;

    SELECT COUNT(*)
    INTO v_attempt_count
    FROM assessment_attempts
    WHERE assessment_id = NEW.assessment_id
      AND student_id = NEW.student_id;

    IF v_attempt_count >= v_attempt_limit THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Attempt limit reached for this assessment';
    END IF;
END$$


CREATE TRIGGER trg_assessment_attempt_before_update
BEFORE UPDATE ON assessment_attempts
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);
    DECLARE v_enrolled INT DEFAULT 0;
    DECLARE v_total_marks DECIMAL(7,2);
    DECLARE v_attempt_limit INT DEFAULT 0;
    DECLARE v_other_attempts INT DEFAULT 0;

    SELECT a.assessment_type, a.total_marks
    INTO v_type, v_total_marks
    FROM assessments a
    WHERE a.assessment_id = NEW.assessment_id;

    IF v_type IS NULL OR v_type NOT IN ('quiz','exam') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assessment attempts are allowed only for Quiz or Exam';
    END IF;

    IF NEW.student_id <> OLD.student_id
       OR NEW.assessment_id <> OLD.assessment_id THEN

        SELECT COUNT(*)
        INTO v_enrolled
        FROM assessments a
        INNER JOIN enrollments e
            ON e.batch_id = a.batch_id
        WHERE a.assessment_id = NEW.assessment_id
          AND e.student_id = NEW.student_id
          AND e.enrollment_status = 'Active';

        IF v_enrolled = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Assessment attempt update denied: student is not actively enrolled in this batch';
        END IF;
    END IF;

    IF NEW.score IS NOT NULL
       AND (NEW.score < 0 OR NEW.score > v_total_marks) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Assessment score must be between 0 and the assessment total marks';
    END IF;

    IF v_type = 'quiz' THEN
        SELECT attempt_limit INTO v_attempt_limit
        FROM quizzes
        WHERE quiz_id = NEW.assessment_id;
    ELSE
        SELECT attempt_limit INTO v_attempt_limit
        FROM exams
        WHERE exam_id = NEW.assessment_id;
    END IF;

    IF NEW.attempt_number < 1 OR NEW.attempt_number > v_attempt_limit THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Attempt number exceeds the allowed attempt limit';
    END IF;

    IF NEW.student_id <> OLD.student_id
       OR NEW.assessment_id <> OLD.assessment_id THEN

        SELECT COUNT(*)
        INTO v_other_attempts
        FROM assessment_attempts
        WHERE assessment_id = NEW.assessment_id
          AND student_id = NEW.student_id
          AND attempt_id <> OLD.attempt_id;

        IF v_other_attempts >= v_attempt_limit THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Attempt limit reached for this assessment';
        END IF;
    END IF;
END$$


-- ============================================================
-- QUESTION BANK / ASSESSMENT COURSE VALIDATION
-- ============================================================
CREATE TRIGGER trg_quiz_question_course_before_insert
BEFORE INSERT ON quiz_questions
FOR EACH ROW
BEGIN
    DECLARE v_assessment_course INT UNSIGNED;
    DECLARE v_question_course INT UNSIGNED;
    SELECT b.course_id INTO v_assessment_course
    FROM assessments a INNER JOIN batches b ON b.batch_id = a.batch_id
    WHERE a.assessment_id = NEW.quiz_id;
    SELECT qb.course_id INTO v_question_course
    FROM questions q INNER JOIN question_banks qb ON qb.question_bank_id = q.question_bank_id
    WHERE q.question_id = NEW.question_id;
    IF v_assessment_course IS NULL OR v_question_course IS NULL OR v_assessment_course <> v_question_course THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Quiz question must come from a question bank for the same course';
    END IF;
END$$

CREATE TRIGGER trg_exam_question_course_before_insert
BEFORE INSERT ON exam_questions
FOR EACH ROW
BEGIN
    DECLARE v_assessment_course INT UNSIGNED;
    DECLARE v_question_course INT UNSIGNED;
    SELECT b.course_id INTO v_assessment_course
    FROM assessments a INNER JOIN batches b ON b.batch_id = a.batch_id
    WHERE a.assessment_id = NEW.exam_id;
    SELECT qb.course_id INTO v_question_course
    FROM questions q INNER JOIN question_banks qb ON qb.question_bank_id = q.question_bank_id
    WHERE q.question_id = NEW.question_id;
    IF v_assessment_course IS NULL OR v_question_course IS NULL OR v_assessment_course <> v_question_course THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Exam question must come from a question bank for the same course';
    END IF;
END$$

-- ============================================================
-- STEP 6: ANSWER / OPTION / AWARDED MARK VALIDATION
-- ============================================================

CREATE TRIGGER trg_attempt_answer_before_insert
BEFORE INSERT ON attempt_answers
FOR EACH ROW
BEGIN
    DECLARE v_assessment_id INT UNSIGNED;
    DECLARE v_type VARCHAR(50);
    DECLARE v_question_ok INT DEFAULT 0;
    DECLARE v_option_ok INT DEFAULT 0;
    DECLARE v_question_marks DECIMAL(7,2);

    SELECT aa.assessment_id, a.assessment_type
    INTO v_assessment_id, v_type
    FROM assessment_attempts aa
    INNER JOIN assessments a ON a.assessment_id = aa.assessment_id
    WHERE aa.attempt_id = NEW.attempt_id;

    IF v_type = 'quiz' THEN
        SELECT COUNT(*), MAX(marks)
        INTO v_question_ok, v_question_marks
        FROM quiz_questions
        WHERE quiz_id = v_assessment_id
          AND question_id = NEW.question_id;
    ELSEIF v_type = 'exam' THEN
        SELECT COUNT(*), MAX(marks)
        INTO v_question_ok, v_question_marks
        FROM exam_questions
        WHERE exam_id = v_assessment_id
          AND question_id = NEW.question_id;
    ELSE
        SET v_question_ok = 0;
    END IF;

    IF v_question_ok = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Answer denied: question does not belong to this assessment';
    END IF;

    IF NEW.selected_option_id IS NOT NULL THEN
        SELECT COUNT(*)
        INTO v_option_ok
        FROM question_options
        WHERE option_id = NEW.selected_option_id
          AND question_id = NEW.question_id;

        IF v_option_ok = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selected option does not belong to the specified question';
        END IF;
    END IF;

    IF NEW.awarded_marks IS NOT NULL
       AND (NEW.awarded_marks < 0 OR NEW.awarded_marks > v_question_marks) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Awarded marks exceed the marks allocated to this question';
    END IF;
END$$


CREATE TRIGGER trg_attempt_answer_before_update
BEFORE UPDATE ON attempt_answers
FOR EACH ROW
BEGIN
    DECLARE v_assessment_id INT UNSIGNED;
    DECLARE v_type VARCHAR(50);
    DECLARE v_question_ok INT DEFAULT 0;
    DECLARE v_option_ok INT DEFAULT 0;
    DECLARE v_question_marks DECIMAL(7,2);

    SELECT aa.assessment_id, a.assessment_type
    INTO v_assessment_id, v_type
    FROM assessment_attempts aa
    INNER JOIN assessments a ON a.assessment_id = aa.assessment_id
    WHERE aa.attempt_id = NEW.attempt_id;

    IF v_type = 'quiz' THEN
        SELECT COUNT(*), MAX(marks)
        INTO v_question_ok, v_question_marks
        FROM quiz_questions
        WHERE quiz_id = v_assessment_id
          AND question_id = NEW.question_id;
    ELSEIF v_type = 'exam' THEN
        SELECT COUNT(*), MAX(marks)
        INTO v_question_ok, v_question_marks
        FROM exam_questions
        WHERE exam_id = v_assessment_id
          AND question_id = NEW.question_id;
    ELSE
        SET v_question_ok = 0;
    END IF;

    IF v_question_ok = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Answer update denied: question does not belong to this assessment';
    END IF;

    IF NEW.selected_option_id IS NOT NULL THEN
        SELECT COUNT(*)
        INTO v_option_ok
        FROM question_options
        WHERE option_id = NEW.selected_option_id
          AND question_id = NEW.question_id;

        IF v_option_ok = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Selected option does not belong to the specified question';
        END IF;
    END IF;

    IF NEW.awarded_marks IS NOT NULL
       AND (NEW.awarded_marks < 0 OR NEW.awarded_marks > v_question_marks) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Awarded marks exceed the marks allocated to this question';
    END IF;
END$$


-- ============================================================
-- STEP 9: MODULE PROGRESS ENROLLMENT VALIDATION
-- ============================================================

CREATE TRIGGER trg_module_progress_before_insert
BEFORE INSERT ON student_module_progress
FOR EACH ROW
BEGIN
    DECLARE v_enrolled INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_enrolled
    FROM modules m
    INNER JOIN batches b
        ON b.course_id = m.course_id
    INNER JOIN enrollments e
        ON e.batch_id = b.batch_id
    WHERE m.module_id = NEW.module_id
      AND e.student_id = NEW.student_id
      AND e.enrollment_status IN ('Active','Completed');

    IF v_enrolled = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Module progress denied: student is not enrolled in the module course';
    END IF;

    IF NEW.progress_status = 'completed'
       AND NEW.progress_percentage < 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Completed module progress must be 100 percent';
    END IF;

    IF NEW.progress_status = 'not_started'
       AND NEW.progress_percentage <> 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Not-started module progress must be 0 percent';
    END IF;
END$$


CREATE TRIGGER trg_module_progress_before_update
BEFORE UPDATE ON student_module_progress
FOR EACH ROW
BEGIN
    DECLARE v_enrolled INT DEFAULT 0;

    IF NEW.student_id <> OLD.student_id
       OR NEW.module_id <> OLD.module_id THEN

        SELECT COUNT(*)
        INTO v_enrolled
        FROM modules m
        INNER JOIN batches b
            ON b.course_id = m.course_id
        INNER JOIN enrollments e
            ON e.batch_id = b.batch_id
        WHERE m.module_id = NEW.module_id
          AND e.student_id = NEW.student_id
          AND e.enrollment_status IN ('Active','Completed');

        IF v_enrolled = 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Module progress update denied: student is not enrolled in the module course';
        END IF;
    END IF;

    IF NEW.progress_status = 'completed'
       AND NEW.progress_percentage < 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Completed module progress must be 100 percent';
    END IF;

    IF NEW.progress_status = 'not_started'
       AND NEW.progress_percentage <> 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Not-started module progress must be 0 percent';
    END IF;
END$$


-- ============================================================
-- ANNOUNCEMENT VALIDATION
-- ============================================================

CREATE TRIGGER trg_announcement_author_before_insert
BEFORE INSERT ON announcements
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);

    SELECT role INTO v_role
    FROM user_accounts
    WHERE user_id = NEW.author_user_id;

    IF v_role IS NULL
       OR v_role NOT IN ('Teacher','AcademicCoordinator','Admin') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Announcements may only be published by Teacher, Course Coordinator or System Administrator';
    END IF;
END$$


-- ============================================================
-- TUTE PURCHASE AND PAYMENT CONSISTENCY
-- ============================================================
CREATE TRIGGER trg_tute_purchase_before_insert
BEFORE INSERT ON tute_purchases
FOR EACH ROW
BEGIN
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_status VARCHAR(30);
    SELECT price, availability_status INTO v_price, v_status
    FROM tutes WHERE resource_id = NEW.tute_resource_id;
    IF v_status IS NULL OR v_status <> 'available' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tute is not available for purchase';
    END IF;
    SET NEW.amount = v_price;
END$$

-- ============================================================
-- PAYMENT TYPE VALIDATION
-- ============================================================

CREATE TRIGGER trg_course_payment_type_before_insert
BEFORE INSERT ON course_payments
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);
    DECLARE v_payment_student INT UNSIGNED;
    DECLARE v_enrollment_student INT UNSIGNED;
    SELECT payment_type, student_id INTO v_type, v_payment_student
    FROM payments WHERE payment_id = NEW.payment_id;
    SELECT student_id INTO v_enrollment_student
    FROM enrollments WHERE enrollment_id = NEW.enrollment_id;
    IF v_type IS NULL OR v_type <> 'course' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Course payment subtype requires payment_type=course';
    END IF;
    IF v_payment_student <> v_enrollment_student THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Course payment student must match enrollment student';
    END IF;
END$$

CREATE TRIGGER trg_tute_payment_type_before_insert
BEFORE INSERT ON tute_payments
FOR EACH ROW
BEGIN
    DECLARE v_type VARCHAR(50);
    DECLARE v_payment_student INT UNSIGNED;
    DECLARE v_purchase_student INT UNSIGNED;
    SELECT payment_type, student_id INTO v_type, v_payment_student
    FROM payments WHERE payment_id = NEW.payment_id;
    SELECT student_id INTO v_purchase_student
    FROM tute_purchases WHERE purchase_id = NEW.purchase_id;
    IF v_type IS NULL OR v_type <> 'tute' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tute payment subtype requires payment_type=tute';
    END IF;
    IF v_payment_student <> v_purchase_student THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tute payment student must match tute purchase student';
    END IF;
END$$

-- ============================================================
-- DISCUSSION ACCESS VALIDATION
-- ============================================================
CREATE TRIGGER trg_discussion_post_before_insert
BEFORE INSERT ON discussion_posts
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);
    DECLARE v_batch INT UNSIGNED;
    DECLARE v_course INT UNSIGNED;
    DECLARE v_ok INT DEFAULT 0;
    SELECT role INTO v_role FROM user_accounts WHERE user_id = NEW.user_id;
    SELECT df.batch_id, b.course_id INTO v_batch, v_course
    FROM discussion_forums df INNER JOIN batches b ON b.batch_id = df.batch_id
    WHERE df.forum_id = NEW.forum_id AND df.status = 'open';
    IF v_batch IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Discussion forum is not open';
    END IF;
    IF v_role = 'Student' THEN
        SELECT COUNT(*) INTO v_ok FROM enrollments
        WHERE batch_id=v_batch AND student_id=NEW.user_id AND enrollment_status='Active';
    ELSEIF v_role = 'Teacher' THEN
        SELECT COUNT(*) INTO v_ok FROM teacher_courses
        WHERE course_id=v_course AND teacher_id=NEW.user_id;
    ELSEIF v_role IN ('Admin','AcademicCoordinator') THEN
        SET v_ok = 1;
    END IF;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User is not authorized to post in this forum';
    END IF;
END$$

CREATE TRIGGER trg_discussion_reply_before_insert
BEFORE INSERT ON discussion_replies
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);
    DECLARE v_batch INT UNSIGNED;
    DECLARE v_course INT UNSIGNED;
    DECLARE v_ok INT DEFAULT 0;
    SELECT role INTO v_role FROM user_accounts WHERE user_id = NEW.user_id;
    SELECT df.batch_id, b.course_id INTO v_batch, v_course
    FROM discussion_posts dp
    INNER JOIN discussion_forums df ON df.forum_id = dp.forum_id
    INNER JOIN batches b ON b.batch_id = df.batch_id
    WHERE dp.post_id = NEW.post_id AND df.status = 'open';
    IF v_batch IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Discussion forum is not open';
    END IF;
    IF v_role = 'Student' THEN
        SELECT COUNT(*) INTO v_ok FROM enrollments
        WHERE batch_id=v_batch AND student_id=NEW.user_id AND enrollment_status='Active';
    ELSEIF v_role = 'Teacher' THEN
        SELECT COUNT(*) INTO v_ok FROM teacher_courses
        WHERE course_id=v_course AND teacher_id=NEW.user_id;
    ELSEIF v_role IN ('Admin','AcademicCoordinator') THEN
        SET v_ok = 1;
    END IF;
    IF v_ok = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'User is not authorized to reply in this forum';
    END IF;
END$$

-- ============================================================
-- ACADEMIC REPORT VALIDATION
-- ============================================================

CREATE TRIGGER trg_report_generator_before_insert
BEFORE INSERT ON academic_reports
FOR EACH ROW
BEGIN
    DECLARE v_role VARCHAR(50);
    DECLARE v_enrolled INT DEFAULT 0;
    DECLARE v_course_coordinator INT UNSIGNED;

    SELECT role INTO v_role FROM user_accounts WHERE user_id = NEW.generated_by_user_id;
    SELECT coordinator_id INTO v_course_coordinator FROM courses WHERE course_id = NEW.course_id;

    IF v_role IS NULL OR v_role NOT IN ('AcademicCoordinator','Admin') THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Academic Reports may only be generated by Course Coordinator or System Administrator';
    END IF;

    IF v_role = 'AcademicCoordinator' AND NEW.generated_by_user_id <> v_course_coordinator THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Coordinator may generate reports only for courses they coordinate';
    END IF;

    SELECT COUNT(*) INTO v_enrolled
    FROM enrollments e
    INNER JOIN batches b ON b.batch_id = e.batch_id
    WHERE e.student_id = NEW.student_id
      AND b.course_id = NEW.course_id
      AND b.term_id = NEW.term_id;

    IF v_enrolled = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Academic report requires a matching student course/term enrollment';
    END IF;
END$$

DELIMITER ;

-- ============================================================
-- 11A. ADMINISTRATIVE PROCEDURES
-- ============================================================
DELIMITER $$

CREATE PROCEDURE sp_review_teacher_registration(
    IN p_admin_id INT UNSIGNED,
    IN p_teacher_id INT UNSIGNED,
    IN p_decision VARCHAR(20),
    IN p_remarks TEXT
)
BEGIN
    DECLARE v_admin_ok INT DEFAULT 0;
    DECLARE v_teacher_ok INT DEFAULT 0;
    SELECT COUNT(*) INTO v_admin_ok FROM system_administrators WHERE admin_id=p_admin_id;
    SELECT COUNT(*) INTO v_teacher_ok FROM teachers WHERE teacher_id=p_teacher_id;
    IF v_admin_ok=0 OR v_teacher_ok=0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid administrator or teacher';
    END IF;
    IF p_decision NOT IN ('approved','rejected') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Decision must be approved or rejected';
    END IF;
    INSERT INTO teacher_approvals(admin_id,teacher_id,decision,remarks)
    VALUES(p_admin_id,p_teacher_id,p_decision,p_remarks);
    UPDATE user_accounts
    SET status = CASE WHEN p_decision='approved' THEN 'active' ELSE 'inactive' END
    WHERE user_id=p_teacher_id;
END$$

CREATE PROCEDURE sp_create_privileged_user(
    IN p_fullname VARCHAR(150),
    IN p_email VARCHAR(190),
    IN p_password_hash VARCHAR(255),
    IN p_role VARCHAR(40)
)
BEGIN
    IF p_role NOT IN ('Admin','AcademicCoordinator') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Privileged procedure supports only Admin or AcademicCoordinator';
    END IF;
    SET @learnflow_allow_privileged_user_create = 1;
    INSERT INTO user_accounts(fullname,email,password,status,role)
    VALUES(p_fullname,p_email,p_password_hash,'active',p_role);
    SET @learnflow_allow_privileged_user_create = 0;
END$$

DELIMITER ;

-- ============================================================
-- 12. USEFUL VIEWS
-- ============================================================
CREATE OR REPLACE VIEW vw_student_grades AS
SELECT
    s.student_id,
    b.course_id,
    a.assessment_id,
    a.title AS assessment_title,
    'assignment' AS assessment_type,
    sub.marks AS score,
    a.total_marks,
    sub.feedback,
    sub.submission_date AS completed_at
FROM students s
JOIN assignment_submissions sub ON sub.student_id = s.student_id
JOIN assignments ass ON ass.assignment_id = sub.assignment_id
JOIN assessments a ON a.assessment_id = ass.assignment_id
JOIN batches b ON b.batch_id = a.batch_id
UNION ALL
SELECT
    s.student_id,
    b.course_id,
    a.assessment_id,
    a.title AS assessment_title,
    a.assessment_type,
    aa.score,
    a.total_marks,
    NULL AS feedback,
    aa.submitted_at AS completed_at
FROM students s
JOIN assessment_attempts aa ON aa.student_id = s.student_id
JOIN assessments a ON a.assessment_id = aa.assessment_id
JOIN batches b ON b.batch_id = a.batch_id
WHERE a.assessment_type IN ('quiz','exam')
  AND aa.attempt_status IN ('submitted','graded');

CREATE OR REPLACE VIEW vw_student_academic_reports AS
SELECT
    ar.report_id,
    ar.student_id,
    ar.course_id,
    c.course_name,
    ar.term_id,
    at.term_name,
    ar.generated_date,
    ar.overall_grade,
    ar.overall_progress,
    ar.attendance_percentage,
    ar.assessment_performance,
    ar.course_completion_status,
    ar.remarks,
    ar.report_status
FROM academic_reports ar
JOIN courses c ON c.course_id = ar.course_id
JOIN academic_terms at ON at.term_id = ar.term_id;


CREATE OR REPLACE VIEW vw_student_attendance_summary AS
SELECT
    e.student_id,
    b.course_id,
    cs.batch_id,
    COUNT(a.attendance_id) AS marked_sessions,
    SUM(CASE WHEN a.attendance_status IN ('present','late') THEN 1 ELSE 0 END) AS attended_sessions,
    ROUND(
        100.0 * SUM(CASE WHEN a.attendance_status IN ('present','late') THEN 1 ELSE 0 END)
        / NULLIF(COUNT(a.attendance_id), 0),
        2
    ) AS attendance_percentage
FROM enrollments e
JOIN batches b ON b.batch_id = e.batch_id
JOIN class_sessions cs ON cs.batch_id = e.batch_id
LEFT JOIN attendance a
    ON a.session_id = cs.session_id
   AND a.student_id = e.student_id
GROUP BY e.student_id, b.course_id, cs.batch_id;


CREATE OR REPLACE VIEW vw_student_module_progress AS
SELECT
    smp.progress_id,
    smp.student_id,
    smp.module_id,
    m.course_id,
    m.module_name,
    smp.progress_percentage,
    smp.progress_status,
    smp.last_accessed_at,
    smp.completed_at,
    smp.updated_at
FROM student_module_progress smp
JOIN modules m ON m.module_id = smp.module_id;



-- ------------------------------------------------------------
-- Requirement-completion reporting / monitoring views
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW vw_student_course_progress AS
SELECT
    e.student_id,
    b.course_id,
    c.course_name,
    COUNT(DISTINCT m.module_id) AS total_modules,
    COUNT(DISTINCT CASE WHEN smp.progress_status='completed' THEN m.module_id END) AS completed_modules,
    ROUND(AVG(COALESCE(smp.progress_percentage,0)),2) AS progress_percentage
FROM enrollments e
JOIN batches b ON b.batch_id=e.batch_id
JOIN courses c ON c.course_id=b.course_id
LEFT JOIN modules m ON m.course_id=c.course_id
LEFT JOIN student_module_progress smp ON smp.module_id=m.module_id AND smp.student_id=e.student_id
GROUP BY e.student_id,b.course_id,c.course_name;

CREATE OR REPLACE VIEW vw_outstanding_course_fees AS
SELECT
    e.enrollment_id, e.student_id, b.course_id, c.course_name, c.course_fee,
    COALESCE(SUM(CASE WHEN p.payment_status='paid' THEN p.amount ELSE 0 END),0) AS amount_paid,
    GREATEST(c.course_fee - COALESCE(SUM(CASE WHEN p.payment_status='paid' THEN p.amount ELSE 0 END),0),0) AS outstanding_amount
FROM enrollments e
JOIN batches b ON b.batch_id=e.batch_id
JOIN courses c ON c.course_id=b.course_id
LEFT JOIN course_payments cp ON cp.enrollment_id=e.enrollment_id
LEFT JOIN payments p ON p.payment_id=cp.payment_id
GROUP BY e.enrollment_id,e.student_id,b.course_id,c.course_name,c.course_fee;

CREATE OR REPLACE VIEW vw_payment_receipts AS
SELECT p.payment_id,p.student_id,p.amount,p.payment_method,p.payment_date,p.receipt_no,p.reference_no,p.payment_type
FROM payments p
WHERE p.payment_status='paid' AND p.receipt_no IS NOT NULL;

CREATE OR REPLACE VIEW vw_student_engagement AS
SELECT
    e.student_id, b.course_id, c.course_name,
    (SELECT COUNT(*)
       FROM resource_access_logs ral
       JOIN learning_resources lr ON lr.resource_id=ral.resource_id
       JOIN modules m ON m.module_id=lr.module_id
      WHERE ral.student_id=e.student_id AND m.course_id=b.course_id) AS resource_interactions,
    (SELECT COUNT(*)
       FROM discussion_posts dp
       JOIN discussion_forums df ON df.forum_id=dp.forum_id
       JOIN batches db ON db.batch_id=df.batch_id
      WHERE dp.user_id=e.student_id AND db.course_id=b.course_id) AS discussion_posts,
    (SELECT COUNT(*)
       FROM discussion_replies dr
       JOIN discussion_posts dp2 ON dp2.post_id=dr.post_id
       JOIN discussion_forums df2 ON df2.forum_id=dp2.forum_id
       JOIN batches db2 ON db2.batch_id=df2.batch_id
      WHERE dr.user_id=e.student_id AND db2.course_id=b.course_id) AS discussion_replies,
    (SELECT MAX(ral2.accessed_at)
       FROM resource_access_logs ral2
       JOIN learning_resources lr2 ON lr2.resource_id=ral2.resource_id
       JOIN modules m2 ON m2.module_id=lr2.module_id
      WHERE ral2.student_id=e.student_id AND m2.course_id=b.course_id) AS last_resource_access
FROM enrollments e
JOIN batches b ON b.batch_id=e.batch_id
JOIN courses c ON c.course_id=b.course_id
GROUP BY e.student_id,b.course_id,c.course_name;

CREATE OR REPLACE VIEW vw_course_completion_rates AS
SELECT
    c.course_id,c.course_name,
    COUNT(DISTINCT e.student_id) AS enrolled_students,
    COUNT(DISTINCT CASE WHEN e.enrollment_status='Completed' THEN e.student_id END) AS completed_students,
    ROUND(100.0 * COUNT(DISTINCT CASE WHEN e.enrollment_status='Completed' THEN e.student_id END) / NULLIF(COUNT(DISTINCT e.student_id),0),2) AS completion_rate
FROM courses c
LEFT JOIN batches b ON b.course_id=c.course_id
LEFT JOIN enrollments e ON e.batch_id=b.batch_id
GROUP BY c.course_id,c.course_name;

CREATE OR REPLACE VIEW vw_teacher_performance AS
SELECT
    t.teacher_id,u.fullname AS teacher_name,tc.course_id,c.course_name,
    (SELECT COUNT(*) FROM class_sessions cs JOIN batches b1 ON b1.batch_id=cs.batch_id
      WHERE cs.teacher_id=t.teacher_id AND b1.course_id=tc.course_id) AS classes_scheduled,
    (SELECT COUNT(*) FROM class_sessions cs JOIN batches b2 ON b2.batch_id=cs.batch_id
      WHERE cs.teacher_id=t.teacher_id AND b2.course_id=tc.course_id AND cs.status='completed') AS classes_completed,
    (SELECT COUNT(*) FROM assessments a JOIN batches b3 ON b3.batch_id=a.batch_id
      WHERE a.teacher_id=t.teacher_id AND b3.course_id=tc.course_id) AS assessments_created,
    (SELECT COUNT(*) FROM assignment_submissions sub
      JOIN assignments ass ON ass.assignment_id=sub.assignment_id
      JOIN assessments a2 ON a2.assessment_id=ass.assignment_id
      JOIN batches b4 ON b4.batch_id=a2.batch_id
      WHERE sub.graded_by_teacher_id=t.teacher_id AND b4.course_id=tc.course_id) AS submissions_graded
FROM teachers t
JOIN user_accounts u ON u.user_id=t.teacher_id
JOIN teacher_courses tc ON tc.teacher_id=t.teacher_id
JOIN courses c ON c.course_id=tc.course_id;

CREATE OR REPLACE VIEW vw_parent_student_overview AS
SELECT
    ps.parent_id, ps.student_id, ps.relationship_type, u.fullname AS student_name,
    COUNT(DISTINCT e.enrollment_id) AS enrollments,
    ROUND(AVG(ar.overall_progress),2) AS average_progress,
    ROUND(AVG(ar.attendance_percentage),2) AS average_attendance
FROM parent_student ps
JOIN user_accounts u ON u.user_id=ps.student_id
LEFT JOIN enrollments e ON e.student_id=ps.student_id
LEFT JOIN academic_reports ar ON ar.student_id=ps.student_id AND ar.report_status='published'
GROUP BY ps.parent_id,ps.student_id,ps.relationship_type,u.fullname;

CREATE OR REPLACE VIEW vw_institute_dashboard AS
SELECT
    (SELECT COUNT(*) FROM students) AS total_students,
    (SELECT COUNT(*) FROM teachers) AS total_teachers,
    (SELECT COUNT(*) FROM courses WHERE status='active') AS active_courses,
    (SELECT COUNT(*) FROM enrollments WHERE enrollment_status='Active') AS active_enrollments,
    (SELECT COUNT(*) FROM payments WHERE payment_status='paid') AS successful_payments,
    (SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='paid') AS total_revenue;

-- ============================================================
-- 13. CLEAN CANONICAL SCHEMA
-- ============================================================
-- The old singular compatibility views were intentionally removed.
-- Only canonical physical tables and vw_* reporting views are kept.
-- Application code should use the canonical plural table names.

-- ============================================================
-- 14. REGISTRATION COMPATIBILITY
-- ============================================================
-- The current registration page creates only a USERS record.  The newer
-- schema requires a role subtype row as well, so this trigger creates the
-- matching subtype automatically.  Student registration numbers created
-- here are placeholders and can be replaced later by the administrator.
DELIMITER $$
CREATE TRIGGER trg_user_accounts_create_subtype
AFTER INSERT ON user_accounts
FOR EACH ROW
BEGIN
    IF NEW.role = 'Student' THEN
        INSERT INTO students (student_id, registration_no)
        VALUES (NEW.user_id, CONCAT('STU-', LPAD(NEW.user_id, 6, '0')));
    ELSEIF NEW.role = 'Teacher' THEN
        INSERT INTO teachers (teacher_id)
        VALUES (NEW.user_id);
    ELSEIF NEW.role = 'Parent' THEN
        INSERT INTO parents (parent_id)
        VALUES (NEW.user_id);
    ELSEIF NEW.role = 'Admin' THEN
        INSERT INTO system_administrators (admin_id)
        VALUES (NEW.user_id);
    ELSEIF NEW.role = 'AcademicCoordinator' THEN
        INSERT INTO course_coordinators (coordinator_id)
        VALUES (NEW.user_id);
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- END OF CURRENT PROJECT COMPATIBILITY LAYER
-- ============================================================

-- ============================================================
-- EER SPECIALIZATION SUMMARY
-- USER_ACCOUNT ISA STUDENT / TEACHER / PARENT / SYSTEM_ADMINISTRATOR / ACADEMIC_COORDINATOR (disjoint by role)
-- LEARNING_RESOURCE ISA STUDY_MATERIAL / RECORDING / TUTE (disjoint by resource_type)
-- ASSESSMENT ISA ASSIGNMENT / QUIZ / EXAM (disjoint by assessment_type)
-- PAYMENT ISA COURSE_PAYMENT / TUTE_PAYMENT (disjoint by payment_type)
-- END OF LEARNFLOW FINAL DATABASE SCHEMA
-- ============================================================
