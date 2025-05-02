-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: cms
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `AccountID` int(11) NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `reset_token_hash` varchar(64) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`AccountID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `reset_token_hash` (`reset_token_hash`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'edrianvm','$2y$10$JAigA/rQbL4rHzrzNOrnuOakeBSEvRLF9pe4nvRdZKTg2gYeb27O.','edrianmartinez400@gmail.com','2025-04-22 22:29:33',NULL,NULL),(2,'edrianvm2','$2y$10$qORCuh8zgQQoTfMjAjanB.4vuIHHydUJywafQEJzn5QL8aYQKwDOS','sample@gmail.com','2025-04-22 23:31:50','ae7f22022b5b394f895fd6fb681ec717f740ad027f0e50421a8f0386d4e80771','2025-04-27 11:43:56'),(4,'diesel','$2y$10$Hi5TLnAZY0Kxo4mo7hUN.uhz1WYPhc.5KqVGORQQ2nWOlWgvs/qae','test@gmail.com','2025-04-23 00:33:14',NULL,NULL),(5,'lyanah','$2y$10$ixo5LvN14knW4K1Wb4oM7OUH5Y7ouleo7mhFoU5BqJ6UlGR7RXk1e','lyana','2025-04-26 23:56:58',NULL,NULL),(6,'din','$2y$10$Py7K0jmFyNiA5d2eWH8F7.WA4ylgeroQ9199gWa6eH360WKuqB3.q','chan@gmail.com','2025-04-27 00:33:29',NULL,NULL),(7,'lyanahp','$2y$10$3Iu6Yuy9L8jNHrgHwAFVrOf9nQvS/v6iYQIyNYLWF3sWCwapdx./u','lyanah09paula@gmail.com','2025-04-27 18:54:14','4e613de655f171c32b284bdca084cbec4b28d1a18bf1b43aaa95cacb6d516d2e','2025-04-27 13:24:51'),(8,'richelle','$2y$10$K9br3XyD3EaocV/zirLX0.7rdlrPXQXOMc0Mwxf1QNWm1Psy4d8pq','richellebenitez03@gmail.com','2025-05-01 03:44:46',NULL,NULL),(9,'bal','$2y$10$M2A/UELW1qDbjJiLSzt7FuXVBEQit3FvzQF0Rf.BIS9.UuJc1KJ52','darling@gmail.com','2025-05-01 13:00:51',NULL,NULL),(10,'milan','$2y$10$AcKvARjHRpBBE3yCMjzCoOIKQ8BQ9uTnnmgallmuzaZ229X8oQtaK','milanfranco@gmail.com','2025-05-01 13:01:44',NULL,NULL),(11,'rexnavarro','$2y$10$TlCrToKxKGy6YrK26oz6AuOyCA71epIzEDU3L/xw3ZpabfloCG2He','rexnavarro@gmail.com','2025-05-01 13:02:39',NULL,NULL),(12,'redge','$2y$10$Sd34UJmMNoL4XBfsESSKXu7Y.eahVgNFtcW5oZgIAXQaK.viTBNbe','rtan@gmail.com','2025-05-01 23:35:52',NULL,NULL),(13,'cait','$2y$10$csygpE8TTCD9jvjiTe8IOe1yfIeOdynA2yDO3lKfDoJbmHjh2GZHi','csorbito@gmail.com','2025-05-01 23:37:20',NULL,NULL),(14,'jason','$2y$10$dov3bbwg7.zM3qR.95OaQe2nSWpq8971dBL2XS.J/SJP/BpHQ4YEG','jdaluyon@gmail.com','2025-05-01 23:40:23',NULL,NULL);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `courses` (
  `CourseCode` varchar(10) NOT NULL,
  `Title` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`CourseCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
INSERT INTO `courses` VALUES ('IT103','Advanced Database Systems');
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curricula`
--

DROP TABLE IF EXISTS `curricula`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curricula` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `FacultyID` int(11) DEFAULT NULL,
  `ProgramID` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_curricula_faculty` (`FacultyID`),
  KEY `fk_curricula_program` (`ProgramID`),
  CONSTRAINT `fk_curricula_faculty` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`) ON DELETE CASCADE,
  CONSTRAINT `fk_curricula_program` FOREIGN KEY (`ProgramID`) REFERENCES `programs` (`ProgramID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curricula`
--

LOCK TABLES `curricula` WRITE;
/*!40000 ALTER TABLE `curricula` DISABLE KEYS */;
INSERT INTO `curricula` VALUES (1,'BSIT Curriculum 2022','2025-05-01 13:03:51',2,1);
/*!40000 ALTER TABLE `curricula` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculties`
--

DROP TABLE IF EXISTS `faculties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculties` (
  `FacultyID` int(11) NOT NULL AUTO_INCREMENT,
  `Faculty` varchar(100) DEFAULT NULL,
  `JoinCode` varchar(5) NOT NULL,
  PRIMARY KEY (`FacultyID`),
  UNIQUE KEY `JoinCode` (`JoinCode`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculties`
--

LOCK TABLES `faculties` WRITE;
/*!40000 ALTER TABLE `faculties` DISABLE KEYS */;
INSERT INTO `faculties` VALUES (1,'College of Hospitality Management','6Q61C'),(2,'College of Computer Studies','8FUNF');
/*!40000 ALTER TABLE `faculties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personnel`
--

DROP TABLE IF EXISTS `personnel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personnel` (
  `PersonnelID` int(11) NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `Role` varchar(10) DEFAULT NULL,
  `FacultyID` int(11) DEFAULT NULL,
  `AccountID` int(11) DEFAULT NULL,
  PRIMARY KEY (`PersonnelID`),
  KEY `FacultyID` (`FacultyID`),
  KEY `AccountID` (`AccountID`),
  CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`),
  CONSTRAINT `personnel_ibfk_2` FOREIGN KEY (`AccountID`) REFERENCES `accounts` (`AccountID`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel`
--

LOCK TABLES `personnel` WRITE;
/*!40000 ALTER TABLE `personnel` DISABLE KEYS */;
INSERT INTO `personnel` VALUES (1,'Edrian','Martinez','Male','user',NULL,1),(2,'ed','rian','Male','user',NULL,2),(4,'test','test','Male','user',NULL,4),(5,'lyanah','paula','Female','DN',1,5),(6,'din','chan','Male','user',NULL,6),(7,'Lyanah','Hernandez','Female','user',NULL,7),(8,'RICHELLE','BENITEZ','Female','PH',1,8),(9,'BACHING','CHING','Female','user',NULL,9),(10,'MILAN FRANCO','SANTOS','Male','user',NULL,10),(11,'REX','NAVARRO JR.','Male','FM',1,11),(12,'REDGIE','TAN','Male','DN',2,12),(13,'CATHERINE','SORBITO','Female','PH',2,13),(14,'JAYSON','DALUYON','Male','FM',2,14);
/*!40000 ALTER TABLE `personnel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_courses`
--

DROP TABLE IF EXISTS `program_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `program_courses` (
  `ProgramID` int(11) NOT NULL,
  `CourseCode` varchar(10) NOT NULL,
  `CurriculumID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ProgramID`,`CourseCode`),
  KEY `CourseCode` (`CourseCode`),
  KEY `fk_program_courses_curriculum` (`CurriculumID`),
  CONSTRAINT `fk_program_courses_curriculum` FOREIGN KEY (`CurriculumID`) REFERENCES `curricula` (`id`) ON DELETE SET NULL,
  CONSTRAINT `program_courses_ibfk_1` FOREIGN KEY (`ProgramID`) REFERENCES `programs` (`ProgramID`) ON DELETE CASCADE,
  CONSTRAINT `program_courses_ibfk_2` FOREIGN KEY (`CourseCode`) REFERENCES `courses` (`CourseCode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_courses`
--

LOCK TABLES `program_courses` WRITE;
/*!40000 ALTER TABLE `program_courses` DISABLE KEYS */;
INSERT INTO `program_courses` VALUES (1,'IT103',1);
/*!40000 ALTER TABLE `program_courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `programs` (
  `ProgramID` int(11) NOT NULL AUTO_INCREMENT,
  `ProgramCode` varchar(10) NOT NULL,
  `ProgramName` varchar(100) NOT NULL,
  PRIMARY KEY (`ProgramID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'BSIT','Bachelor of Science in Information Technology');
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissioncourses`
--

DROP TABLE IF EXISTS `submissioncourses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submissioncourses` (
  `SubmissionID` int(11) NOT NULL,
  `CourseCode` varchar(10) NOT NULL,
  `LeadID` int(11) DEFAULT NULL,
  PRIMARY KEY (`SubmissionID`,`CourseCode`),
  KEY `CourseCode` (`CourseCode`),
  KEY `LeadID` (`LeadID`),
  CONSTRAINT `submissioncourses_ibfk_1` FOREIGN KEY (`SubmissionID`) REFERENCES `submissions` (`SubmissionID`),
  CONSTRAINT `submissioncourses_ibfk_2` FOREIGN KEY (`CourseCode`) REFERENCES `courses` (`CourseCode`),
  CONSTRAINT `submissioncourses_ibfk_3` FOREIGN KEY (`LeadID`) REFERENCES `personnel` (`PersonnelID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissioncourses`
--

LOCK TABLES `submissioncourses` WRITE;
/*!40000 ALTER TABLE `submissioncourses` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissioncourses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `submissions`
--

DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submissions` (
  `SubmissionID` int(11) NOT NULL AUTO_INCREMENT,
  `FacultyID` int(11) DEFAULT NULL,
  `TaskID` int(11) DEFAULT NULL,
  `Printed` varchar(20) DEFAULT NULL,
  `Esign` varchar(100) DEFAULT NULL,
  `SchoolYear` varchar(20) DEFAULT NULL,
  `Term` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`SubmissionID`),
  KEY `FacultyID` (`FacultyID`),
  KEY `TaskID` (`TaskID`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`),
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`TaskID`) REFERENCES `tasks` (`TaskID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_assignments`
--

DROP TABLE IF EXISTS `task_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_assignments` (
  `TaskID` int(11) NOT NULL,
  `PersonnelID` int(11) NOT NULL,
  `CourseCode` varchar(10) NOT NULL,
  `Status` enum('Pending','Submitted') DEFAULT 'Pending',
  `ReviewStatus` enum('Not Reviewed','Approved','Rejected') DEFAULT 'Not Reviewed',
  PRIMARY KEY (`TaskID`,`PersonnelID`,`CourseCode`),
  KEY `PersonnelID` (`PersonnelID`),
  KEY `CourseCode` (`CourseCode`),
  CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`TaskID`) REFERENCES `tasks` (`TaskID`),
  CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`PersonnelID`) REFERENCES `personnel` (`PersonnelID`),
  CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`CourseCode`) REFERENCES `courses` (`CourseCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_assignments`
--

LOCK TABLES `task_assignments` WRITE;
/*!40000 ALTER TABLE `task_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `TaskID` int(11) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) NOT NULL,
  `Description` text DEFAULT NULL,
  `CreatedBy` int(11) DEFAULT NULL,
  `FacultyID` int(11) DEFAULT NULL,
  `DueDate` date DEFAULT NULL,
  `Status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`TaskID`),
  KEY `CreatedBy` (`CreatedBy`),
  KEY `FacultyID` (`FacultyID`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `personnel` (`PersonnelID`),
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teammembers`
--

DROP TABLE IF EXISTS `teammembers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teammembers` (
  `SubmissionID` int(11) NOT NULL,
  `MembersID` int(11) NOT NULL,
  PRIMARY KEY (`SubmissionID`,`MembersID`),
  KEY `MembersID` (`MembersID`),
  CONSTRAINT `teammembers_ibfk_1` FOREIGN KEY (`SubmissionID`) REFERENCES `submissions` (`SubmissionID`),
  CONSTRAINT `teammembers_ibfk_2` FOREIGN KEY (`MembersID`) REFERENCES `personnel` (`PersonnelID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teammembers`
--

LOCK TABLES `teammembers` WRITE;
/*!40000 ALTER TABLE `teammembers` DISABLE KEYS */;
/*!40000 ALTER TABLE `teammembers` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-02  3:07:27
