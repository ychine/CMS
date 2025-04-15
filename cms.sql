-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2025 at 03:07 PM
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
-- Database: `cms`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `CourseCode` varchar(10) NOT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `CurriculumYear` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `FacultyID` int(11) NOT NULL,
  `Faculty` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `personnel`
--

CREATE TABLE `personnel` (
  `PersonnelID` int(11) NOT NULL,
  `PersonnelName` varchar(100) DEFAULT NULL,
  `Role` varchar(10) DEFAULT NULL,
  `FacultyID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissioncourses`
--

CREATE TABLE `submissioncourses` (
  `SubmissionID` int(11) NOT NULL,
  `CourseCode` varchar(10) NOT NULL,
  `LeadID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `SubmissionID` int(11) NOT NULL,
  `FacultyID` int(11) DEFAULT NULL,
  `Printed` varchar(20) DEFAULT NULL,
  `Esign` varchar(100) DEFAULT NULL,
  `SchoolYear` varchar(20) DEFAULT NULL,
  `Term` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teammembers`
--

CREATE TABLE `teammembers` (
  `SubmissionID` int(11) NOT NULL,
  `MembersID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`CourseCode`);

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`FacultyID`);

--
-- Indexes for table `personnel`
--
ALTER TABLE `personnel`
  ADD PRIMARY KEY (`PersonnelID`),
  ADD KEY `FacultyID` (`FacultyID`);

--
-- Indexes for table `submissioncourses`
--
ALTER TABLE `submissioncourses`
  ADD PRIMARY KEY (`SubmissionID`,`CourseCode`),
  ADD KEY `CourseCode` (`CourseCode`),
  ADD KEY `LeadID` (`LeadID`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`SubmissionID`),
  ADD KEY `FacultyID` (`FacultyID`);

--
-- Indexes for table `teammembers`
--
ALTER TABLE `teammembers`
  ADD PRIMARY KEY (`SubmissionID`,`MembersID`),
  ADD KEY `MembersID` (`MembersID`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `personnel`
--
ALTER TABLE `personnel`
  ADD CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`);

--
-- Constraints for table `submissioncourses`
--
ALTER TABLE `submissioncourses`
  ADD CONSTRAINT `submissioncourses_ibfk_1` FOREIGN KEY (`SubmissionID`) REFERENCES `submissions` (`SubmissionID`),
  ADD CONSTRAINT `submissioncourses_ibfk_2` FOREIGN KEY (`CourseCode`) REFERENCES `courses` (`CourseCode`),
  ADD CONSTRAINT `submissioncourses_ibfk_3` FOREIGN KEY (`LeadID`) REFERENCES `personnel` (`PersonnelID`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`);

--
-- Constraints for table `teammembers`
--
ALTER TABLE `teammembers`
  ADD CONSTRAINT `teammembers_ibfk_1` FOREIGN KEY (`SubmissionID`) REFERENCES `submissions` (`SubmissionID`),
  ADD CONSTRAINT `teammembers_ibfk_2` FOREIGN KEY (`MembersID`) REFERENCES `personnel` (`PersonnelID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
