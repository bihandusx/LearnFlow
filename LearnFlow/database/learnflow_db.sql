-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 09, 2026 at 05:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `learnflow_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_coordinator`
--

CREATE TABLE `academic_coordinator` (
  `CoordinatorID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `AdminID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `AnnouncementID` int(11) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `Content` text DEFAULT NULL,
  `PublishDate` date DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `BatchID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment`
--

CREATE TABLE `assignment` (
  `TestID` int(11) NOT NULL,
  `DueDate` date DEFAULT NULL,
  `SubmissionType` varchar(50) DEFAULT NULL,
  `AllowLateSubmission` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignment_submission`
--

CREATE TABLE `assignment_submission` (
  `SubmissionID` int(11) NOT NULL,
  `SubmittedDate` date DEFAULT NULL,
  `FileURL` varchar(255) DEFAULT NULL,
  `Marks` int(11) DEFAULT NULL,
  `Feedback` text DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `TestID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `SessionID` int(11) DEFAULT NULL,
  `AttendanceDate` date DEFAULT NULL,
  `Status` enum('Present','Absent') DEFAULT NULL,
  `MarkedBy` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `batch`
--

CREATE TABLE `batch` (
  `BatchID` int(11) NOT NULL,
  `BatchName` varchar(100) DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `Status` varchar(30) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `batch`
--

INSERT INTO `batch` (`BatchID`, `BatchName`, `StartDate`, `EndDate`, `Status`, `CourseID`) VALUES
(1, '2026 Batch', '2026-01-01', '2026-12-31', 'Active', 1);

-- --------------------------------------------------------

--
-- Table structure for table `contact_message`
--

CREATE TABLE `contact_message` (
  `MessageID` int(11) NOT NULL,
  `SenderID` int(11) DEFAULT NULL,
  `RecipientID` int(11) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `Subject` varchar(150) DEFAULT NULL,
  `Body` text DEFAULT NULL,
  `SentDate` datetime DEFAULT NULL,
  `InReplyToMessageID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `CourseID` int(11) NOT NULL,
  `CourseName` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `CourseFee` decimal(10,2) DEFAULT NULL,
  `Stream` varchar(50) DEFAULT NULL,
  `TeacherID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`CourseID`, `CourseName`, `Description`, `CourseFee`) VALUES
(1, 'Web Development', 'HTML CSS PHP and MySQL', 50000.00);

-- --------------------------------------------------------

--
-- Table structure for table `course_session`
--

CREATE TABLE `course_session` (
  `SessionID` int(11) NOT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `TeacherID` int(11) DEFAULT NULL,
  `SessionDate` date DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `digital_tute`
--

CREATE TABLE `digital_tute` (
  `TuteID` int(11) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `FileURL` varchar(255) DEFAULT NULL,
  `Status` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussion_forum`
--

CREATE TABLE `discussion_forum` (
  `ForumID` int(11) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `CreatedDate` date DEFAULT NULL,
  `BatchID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussion_post`
--

CREATE TABLE `discussion_post` (
  `PostID` int(11) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `Content` text DEFAULT NULL,
  `PostDate` date DEFAULT NULL,
  `ForumID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `discussion_reply`
--

CREATE TABLE `discussion_reply` (
  `ReplyID` int(11) NOT NULL,
  `Content` text DEFAULT NULL,
  `ReplyDate` date DEFAULT NULL,
  `PostID` int(11) DEFAULT NULL,
  `UserID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `EnrollmentID` int(11) NOT NULL,
  `EnrollmentDate` date DEFAULT NULL,
  `EnrollmentStatus` varchar(30) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `BatchID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam`
--

CREATE TABLE `exam` (
  `TestID` int(11) NOT NULL,
  `ExamDate` date DEFAULT NULL,
  `StartTime` time DEFAULT NULL,
  `EndTime` time DEFAULT NULL,
  `Venue` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_result`
--

CREATE TABLE `exam_result` (
  `ResultID` int(11) NOT NULL,
  `Marks` int(11) DEFAULT NULL,
  `Grade` varchar(10) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `TestID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learning_resource`
--

CREATE TABLE `learning_resource` (
  `ResourceID` int(11) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `ResourceType` enum('Recording','Note','Tute','Reference') DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `FileURL` varchar(255) DEFAULT NULL,
  `UploadDate` date DEFAULT NULL,
  `ApprovalStatus` varchar(30) DEFAULT NULL,
  `ModuleID` int(11) DEFAULT NULL,
  `TeacherID` int(11) DEFAULT NULL,
  `CoordinatorID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_request`
--

CREATE TABLE `meeting_request` (
  `MeetingRequestID` int(11) NOT NULL,
  `ParentID` int(11) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `RecipientID` int(11) DEFAULT NULL,
  `Subject` varchar(150) DEFAULT NULL,
  `Reason` text DEFAULT NULL,
  `PreferredDate` date DEFAULT NULL,
  `PreferredTime` time DEFAULT NULL,
  `Mode` enum('Online','In-Person') DEFAULT NULL,
  `Status` enum('Pending','Confirmed','Declined') DEFAULT 'Pending',
  `RequestedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

CREATE TABLE `module` (
  `ModuleID` int(11) NOT NULL,
  `ModuleName` varchar(100) DEFAULT NULL,
  `ModuleOrder` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `BatchID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `module`
--

INSERT INTO `module` (`ModuleID`, `ModuleName`, `ModuleOrder`, `Description`, `BatchID`) VALUES
(2, 'Database Basic', 1, 'Introduction to Database', 1);

-- --------------------------------------------------------

--
-- Table structure for table `parent`
--

CREATE TABLE `parent` (
  `ParentID` int(11) NOT NULL,
  `NIC` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent_student`
--

CREATE TABLE `parent_student` (
  `ParentID` int(11) NOT NULL,
  `StudentID` int(11) NOT NULL,
  `RelationshipType` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `PaymentID` int(11) NOT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `PaymentDate` date DEFAULT NULL,
  `PaymentMethod` varchar(50) DEFAULT NULL,
  `Status` varchar(30) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `CourseID` int(11) DEFAULT NULL,
  `ItemTitle` varchar(150) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `ReceiptNumber` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz`
--

CREATE TABLE `quiz` (
  `TestID` int(11) NOT NULL,
  `Duration` int(11) DEFAULT NULL,
  `AttemptLimit` int(11) DEFAULT NULL,
  `PassingMarks` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempt`
--

CREATE TABLE `quiz_attempt` (
  `AttemptID` int(11) NOT NULL,
  `AttemptDate` date DEFAULT NULL,
  `Score` int(11) DEFAULT NULL,
  `StudentID` int(11) DEFAULT NULL,
  `TestID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `StudentID` int(11) NOT NULL,
  `RegistrationNo` varchar(50) DEFAULT NULL,
  `NIC` varchar(20) DEFAULT NULL,
  `DateOfBirth` date DEFAULT NULL,
  `Gender` varchar(20) DEFAULT NULL,
  `EnrollmentDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_emergency`
--

CREATE TABLE `student_emergency` (
  `StudentID` int(11) NOT NULL,
  `PrimaryName` varchar(100) DEFAULT NULL,
  `PrimaryRelationship` varchar(50) DEFAULT NULL,
  `PrimaryPhone` varchar(20) DEFAULT NULL,
  `PrimaryAltPhone` varchar(20) DEFAULT NULL,
  `SecondaryName` varchar(100) DEFAULT NULL,
  `SecondaryRelationship` varchar(50) DEFAULT NULL,
  `SecondaryPhone` varchar(20) DEFAULT NULL,
  `BloodGroup` varchar(10) DEFAULT NULL,
  `FamilyDoctorContact` varchar(20) DEFAULT NULL,
  `Allergies` text DEFAULT NULL,
  `MedicalConditions` text DEFAULT NULL,
  `UpdatedAt` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teacher`
--

CREATE TABLE `teacher` (
  `TeacherID` int(11) NOT NULL,
  `Qualification` varchar(200) DEFAULT NULL,
  `Specialization` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test`
--

CREATE TABLE `test` (
  `TestID` int(11) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `OpenDate` date DEFAULT NULL,
  `TotalMarks` int(11) DEFAULT NULL,
  `Instructions` text DEFAULT NULL,
  `TeacherID` int(11) DEFAULT NULL,
  `BatchID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tute_purchase`
--

CREATE TABLE `tute_purchase` (
  `PurchaseID` int(11) NOT NULL,
  `PaymentID` int(11) DEFAULT NULL,
  `TuteID` int(11) DEFAULT NULL,
  `PurchaseDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Status` varchar(20) DEFAULT NULL,
  `Role` enum('Student','Teacher','Parent','Admin','Academic Coordinator') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_coordinator`
--
ALTER TABLE `academic_coordinator`
  ADD PRIMARY KEY (`CoordinatorID`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`AdminID`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`AnnouncementID`),
  ADD KEY `CreatedBy` (`CreatedBy`),
  ADD KEY `BatchID` (`BatchID`);

--
-- Indexes for table `assignment`
--
ALTER TABLE `assignment`
  ADD PRIMARY KEY (`TestID`);

--
-- Indexes for table `assignment_submission`
--
ALTER TABLE `assignment_submission`
  ADD PRIMARY KEY (`SubmissionID`),
  ADD UNIQUE KEY `student_assignment` (`StudentID`,`TestID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `TestID` (`TestID`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD UNIQUE KEY `student_session` (`StudentID`,`SessionID`),
  ADD KEY `CourseID` (`CourseID`),
  ADD KEY `SessionID` (`SessionID`),
  ADD KEY `MarkedBy` (`MarkedBy`);

--
-- Indexes for table `batch`
--
ALTER TABLE `batch`
  ADD PRIMARY KEY (`BatchID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `contact_message`
--
ALTER TABLE `contact_message`
  ADD PRIMARY KEY (`MessageID`),
  ADD KEY `SenderID` (`SenderID`),
  ADD KEY `RecipientID` (`RecipientID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `InReplyToMessageID` (`InReplyToMessageID`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`CourseID`),
  ADD KEY `TeacherID` (`TeacherID`);

--
-- Indexes for table `course_session`
--
ALTER TABLE `course_session`
  ADD PRIMARY KEY (`SessionID`),
  ADD KEY `CourseID` (`CourseID`),
  ADD KEY `TeacherID` (`TeacherID`);

--
-- Indexes for table `digital_tute`
--
ALTER TABLE `digital_tute`
  ADD PRIMARY KEY (`TuteID`);

--
-- Indexes for table `discussion_forum`
--
ALTER TABLE `discussion_forum`
  ADD PRIMARY KEY (`ForumID`),
  ADD KEY `BatchID` (`BatchID`);

--
-- Indexes for table `discussion_post`
--
ALTER TABLE `discussion_post`
  ADD PRIMARY KEY (`PostID`),
  ADD KEY `ForumID` (`ForumID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `discussion_reply`
--
ALTER TABLE `discussion_reply`
  ADD PRIMARY KEY (`ReplyID`),
  ADD KEY `PostID` (`PostID`),
  ADD KEY `UserID` (`UserID`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `BatchID` (`BatchID`);

--
-- Indexes for table `exam`
--
ALTER TABLE `exam`
  ADD PRIMARY KEY (`TestID`);

--
-- Indexes for table `exam_result`
--
ALTER TABLE `exam_result`
  ADD PRIMARY KEY (`ResultID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `TestID` (`TestID`);

--
-- Indexes for table `learning_resource`
--
ALTER TABLE `learning_resource`
  ADD PRIMARY KEY (`ResourceID`),
  ADD KEY `ModuleID` (`ModuleID`),
  ADD KEY `TeacherID` (`TeacherID`),
  ADD KEY `CoordinatorID` (`CoordinatorID`);

--
-- Indexes for table `meeting_request`
--
ALTER TABLE `meeting_request`
  ADD PRIMARY KEY (`MeetingRequestID`),
  ADD KEY `ParentID` (`ParentID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `RecipientID` (`RecipientID`);

--
-- Indexes for table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`ModuleID`),
  ADD KEY `BatchID` (`BatchID`);

--
-- Indexes for table `parent`
--
ALTER TABLE `parent`
  ADD PRIMARY KEY (`ParentID`);

--
-- Indexes for table `parent_student`
--
ALTER TABLE `parent_student`
  ADD PRIMARY KEY (`ParentID`,`StudentID`),
  ADD KEY `StudentID` (`StudentID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`PaymentID`),
  ADD UNIQUE KEY `ReceiptNumber` (`ReceiptNumber`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `CourseID` (`CourseID`);

--
-- Indexes for table `quiz`
--
ALTER TABLE `quiz`
  ADD PRIMARY KEY (`TestID`);

--
-- Indexes for table `quiz_attempt`
--
ALTER TABLE `quiz_attempt`
  ADD PRIMARY KEY (`AttemptID`),
  ADD KEY `StudentID` (`StudentID`),
  ADD KEY `TestID` (`TestID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`StudentID`),
  ADD UNIQUE KEY `RegistrationNo` (`RegistrationNo`),
  ADD UNIQUE KEY `NIC` (`NIC`);

--
-- Indexes for table `student_emergency`
--
ALTER TABLE `student_emergency`
  ADD PRIMARY KEY (`StudentID`);

--
-- Indexes for table `teacher`
--
ALTER TABLE `teacher`
  ADD PRIMARY KEY (`TeacherID`);

--
-- Indexes for table `test`
--
ALTER TABLE `test`
  ADD PRIMARY KEY (`TestID`),
  ADD KEY `TeacherID` (`TeacherID`),
  ADD KEY `BatchID` (`BatchID`);

--
-- Indexes for table `tute_purchase`
--
ALTER TABLE `tute_purchase`
  ADD PRIMARY KEY (`PurchaseID`),
  ADD KEY `PaymentID` (`PaymentID`),
  ADD KEY `TuteID` (`TuteID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `AnnouncementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assignment_submission`
--
ALTER TABLE `assignment_submission`
  MODIFY `SubmissionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `batch`
--
ALTER TABLE `batch`
  MODIFY `BatchID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_message`
--
ALTER TABLE `contact_message`
  MODIFY `MessageID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course`
--
ALTER TABLE `course`
  MODIFY `CourseID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `course_session`
--
ALTER TABLE `course_session`
  MODIFY `SessionID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `digital_tute`
--
ALTER TABLE `digital_tute`
  MODIFY `TuteID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussion_forum`
--
ALTER TABLE `discussion_forum`
  MODIFY `ForumID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussion_post`
--
ALTER TABLE `discussion_post`
  MODIFY `PostID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `discussion_reply`
--
ALTER TABLE `discussion_reply`
  MODIFY `ReplyID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `EnrollmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_result`
--
ALTER TABLE `exam_result`
  MODIFY `ResultID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learning_resource`
--
ALTER TABLE `learning_resource`
  MODIFY `ResourceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_request`
--
ALTER TABLE `meeting_request`
  MODIFY `MeetingRequestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module`
--
ALTER TABLE `module`
  MODIFY `ModuleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quiz_attempt`
--
ALTER TABLE `quiz_attempt`
  MODIFY `AttemptID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test`
--
ALTER TABLE `test`
  MODIFY `TestID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tute_purchase`
--
ALTER TABLE `tute_purchase`
  MODIFY `PurchaseID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_coordinator`
--
ALTER TABLE `academic_coordinator`
  ADD CONSTRAINT `academic_coordinator_ibfk_1` FOREIGN KEY (`CoordinatorID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`AdminID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `announcement`
--
ALTER TABLE `announcement`
  ADD CONSTRAINT `announcement_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `announcement_ibfk_2` FOREIGN KEY (`BatchID`) REFERENCES `batch` (`BatchID`);

--
-- Constraints for table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `assignment_ibfk_1` FOREIGN KEY (`TestID`) REFERENCES `test` (`TestID`) ON DELETE CASCADE;

--
-- Constraints for table `assignment_submission`
--
ALTER TABLE `assignment_submission`
  ADD CONSTRAINT `assignment_submission_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `assignment_submission_ibfk_2` FOREIGN KEY (`TestID`) REFERENCES `assignment` (`TestID`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`SessionID`) REFERENCES `course_session` (`SessionID`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_4` FOREIGN KEY (`MarkedBy`) REFERENCES `teacher` (`TeacherID`);

--
-- Constraints for table `batch`
--
ALTER TABLE `batch`
  ADD CONSTRAINT `batch_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`) ON DELETE CASCADE;

--
-- Constraints for table `contact_message`
--
ALTER TABLE `contact_message`
  ADD CONSTRAINT `contact_message_ibfk_1` FOREIGN KEY (`SenderID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `contact_message_ibfk_2` FOREIGN KEY (`RecipientID`) REFERENCES `users` (`UserID`),
  ADD CONSTRAINT `contact_message_ibfk_3` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `contact_message_ibfk_4` FOREIGN KEY (`InReplyToMessageID`) REFERENCES `contact_message` (`MessageID`);

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `course_ibfk_1` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`);

--
-- Constraints for table `course_session`
--
ALTER TABLE `course_session`
  ADD CONSTRAINT `course_session_ibfk_1` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_session_ibfk_2` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`);

--
-- Constraints for table `discussion_forum`
--
ALTER TABLE `discussion_forum`
  ADD CONSTRAINT `discussion_forum_ibfk_1` FOREIGN KEY (`BatchID`) REFERENCES `batch` (`BatchID`);

--
-- Constraints for table `discussion_post`
--
ALTER TABLE `discussion_post`
  ADD CONSTRAINT `discussion_post_ibfk_1` FOREIGN KEY (`ForumID`) REFERENCES `discussion_forum` (`ForumID`),
  ADD CONSTRAINT `discussion_post_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `discussion_reply`
--
ALTER TABLE `discussion_reply`
  ADD CONSTRAINT `discussion_reply_ibfk_1` FOREIGN KEY (`PostID`) REFERENCES `discussion_post` (`PostID`),
  ADD CONSTRAINT `discussion_reply_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`BatchID`) REFERENCES `batch` (`BatchID`);

--
-- Constraints for table `exam`
--
ALTER TABLE `exam`
  ADD CONSTRAINT `exam_ibfk_1` FOREIGN KEY (`TestID`) REFERENCES `test` (`TestID`) ON DELETE CASCADE;

--
-- Constraints for table `exam_result`
--
ALTER TABLE `exam_result`
  ADD CONSTRAINT `exam_result_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `exam_result_ibfk_2` FOREIGN KEY (`TestID`) REFERENCES `exam` (`TestID`);

--
-- Constraints for table `learning_resource`
--
ALTER TABLE `learning_resource`
  ADD CONSTRAINT `learning_resource_ibfk_1` FOREIGN KEY (`ModuleID`) REFERENCES `module` (`ModuleID`),
  ADD CONSTRAINT `learning_resource_ibfk_2` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`),
  ADD CONSTRAINT `learning_resource_ibfk_3` FOREIGN KEY (`CoordinatorID`) REFERENCES `academic_coordinator` (`CoordinatorID`);

--
-- Constraints for table `meeting_request`
--
ALTER TABLE `meeting_request`
  ADD CONSTRAINT `meeting_request_ibfk_1` FOREIGN KEY (`ParentID`) REFERENCES `parent` (`ParentID`),
  ADD CONSTRAINT `meeting_request_ibfk_2` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `meeting_request_ibfk_3` FOREIGN KEY (`RecipientID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `module`
--
ALTER TABLE `module`
  ADD CONSTRAINT `module_ibfk_1` FOREIGN KEY (`BatchID`) REFERENCES `batch` (`BatchID`) ON DELETE CASCADE;

--
-- Constraints for table `parent`
--
ALTER TABLE `parent`
  ADD CONSTRAINT `parent_ibfk_1` FOREIGN KEY (`ParentID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `parent_student`
--
ALTER TABLE `parent_student`
  ADD CONSTRAINT `parent_student_ibfk_1` FOREIGN KEY (`ParentID`) REFERENCES `parent` (`ParentID`) ON DELETE CASCADE,
  ADD CONSTRAINT `parent_student_ibfk_2` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`CourseID`) REFERENCES `course` (`CourseID`);

--
-- Constraints for table `quiz`
--
ALTER TABLE `quiz`
  ADD CONSTRAINT `quiz_ibfk_1` FOREIGN KEY (`TestID`) REFERENCES `test` (`TestID`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_attempt`
--
ALTER TABLE `quiz_attempt`
  ADD CONSTRAINT `quiz_attempt_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`),
  ADD CONSTRAINT `quiz_attempt_ibfk_2` FOREIGN KEY (`TestID`) REFERENCES `quiz` (`TestID`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `student_emergency`
--
ALTER TABLE `student_emergency`
  ADD CONSTRAINT `student_emergency_ibfk_1` FOREIGN KEY (`StudentID`) REFERENCES `student` (`StudentID`) ON DELETE CASCADE;

--
-- Constraints for table `teacher`
--
ALTER TABLE `teacher`
  ADD CONSTRAINT `teacher_ibfk_1` FOREIGN KEY (`TeacherID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE;

--
-- Constraints for table `test`
--
ALTER TABLE `test`
  ADD CONSTRAINT `test_ibfk_1` FOREIGN KEY (`TeacherID`) REFERENCES `teacher` (`TeacherID`),
  ADD CONSTRAINT `test_ibfk_2` FOREIGN KEY (`BatchID`) REFERENCES `batch` (`BatchID`);

--
-- Constraints for table `tute_purchase`
--
ALTER TABLE `tute_purchase`
  ADD CONSTRAINT `tute_purchase_ibfk_1` FOREIGN KEY (`PaymentID`) REFERENCES `payment` (`PaymentID`),
  ADD CONSTRAINT `tute_purchase_ibfk_2` FOREIGN KEY (`TuteID`) REFERENCES `digital_tute` (`TuteID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
