-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: CMS
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
  `PersonnelID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`AccountID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `PersonnelID_UNIQUE` (`PersonnelID`),
  CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`PersonnelID`) REFERENCES `personnel` (`PersonnelID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
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
  `CurriculumYear` int(11) DEFAULT NULL,
  PRIMARY KEY (`CourseCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faculties`
--

DROP TABLE IF EXISTS `faculties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `faculties` (
  `FacultyID` int(11) NOT NULL,
  `Faculty` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`FacultyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculties`
--

LOCK TABLES `faculties` WRITE;
/*!40000 ALTER TABLE `faculties` DISABLE KEYS */;
/*!40000 ALTER TABLE `faculties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personnel`
--

DROP TABLE IF EXISTS `personnel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personnel` (
  `PersonnelID` int(11) NOT NULL,
  `PersonnelName` varchar(100) DEFAULT NULL,
  `Role` varchar(10) DEFAULT NULL,
  `FacultyID` int(11) DEFAULT NULL,
  PRIMARY KEY (`PersonnelID`),
  KEY `FacultyID` (`FacultyID`),
  CONSTRAINT `personnel_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel`
--

LOCK TABLES `personnel` WRITE;
/*!40000 ALTER TABLE `personnel` DISABLE KEYS */;
/*!40000 ALTER TABLE `personnel` ENABLE KEYS */;
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
  `SubmissionID` int(11) NOT NULL,
  `FacultyID` int(11) DEFAULT NULL,
  `Printed` varchar(20) DEFAULT NULL,
  `Esign` varchar(100) DEFAULT NULL,
  `SchoolYear` varchar(20) DEFAULT NULL,
  `Term` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`SubmissionID`),
  KEY `FacultyID` (`FacultyID`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`)
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

-- Dump completed on 2025-04-22 14:58:57
