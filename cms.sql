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
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES (1,'edrianvm','$2y$10$JAigA/rQbL4rHzrzNOrnuOakeBSEvRLF9pe4nvRdZKTg2gYeb27O.','edrianmartinez400@gmail.com','2025-04-22 22:29:33',NULL,NULL),(2,'edrianvm2','$2y$10$qORCuh8zgQQoTfMjAjanB.4vuIHHydUJywafQEJzn5QL8aYQKwDOS','sample@gmail.com','2025-04-22 23:31:50','ae7f22022b5b394f895fd6fb681ec717f740ad027f0e50421a8f0386d4e80771','2025-04-27 11:43:56'),(4,'diesel','$2y$10$Hi5TLnAZY0Kxo4mo7hUN.uhz1WYPhc.5KqVGORQQ2nWOlWgvs/qae','test@gmail.com','2025-04-23 00:33:14',NULL,NULL),(5,'lyanah','$2y$10$ixo5LvN14knW4K1Wb4oM7OUH5Y7ouleo7mhFoU5BqJ6UlGR7RXk1e','lyana','2025-04-26 23:56:58',NULL,NULL),(6,'din','$2y$10$Py7K0jmFyNiA5d2eWH8F7.WA4ylgeroQ9199gWa6eH360WKuqB3.q','chan@gmail.com','2025-04-27 00:33:29',NULL,NULL),(7,'lyanahp','$2y$10$3Iu6Yuy9L8jNHrgHwAFVrOf9nQvS/v6iYQIyNYLWF3sWCwapdx./u','lyanah09paula@gmail.com','2025-04-27 18:54:14','4e613de655f171c32b284bdca084cbec4b28d1a18bf1b43aaa95cacb6d516d2e','2025-04-27 13:24:51'),(8,'richelle','$2y$10$K9br3XyD3EaocV/zirLX0.7rdlrPXQXOMc0Mwxf1QNWm1Psy4d8pq','richellebenitez03@gmail.com','2025-05-01 03:44:46',NULL,NULL),(9,'bal','$2y$10$M2A/UELW1qDbjJiLSzt7FuXVBEQit3FvzQF0Rf.BIS9.UuJc1KJ52','darling@gmail.com','2025-05-01 13:00:51',NULL,NULL),(10,'milan','$2y$10$AcKvARjHRpBBE3yCMjzCoOIKQ8BQ9uTnnmgallmuzaZ229X8oQtaK','milanfranco@gmail.com','2025-05-01 13:01:44',NULL,NULL),(11,'rexnavarro','$2y$10$TlCrToKxKGy6YrK26oz6AuOyCA71epIzEDU3L/xw3ZpabfloCG2He','rexnavarro@gmail.com','2025-05-01 13:02:39',NULL,NULL),(12,'redge','$2y$10$Sd34UJmMNoL4XBfsESSKXu7Y.eahVgNFtcW5oZgIAXQaK.viTBNbe','rtan@gmail.com','2025-05-01 23:35:52',NULL,NULL),(13,'cait','$2y$10$csygpE8TTCD9jvjiTe8IOe1yfIeOdynA2yDO3lKfDoJbmHjh2GZHi','csorbito@gmail.com','2025-05-01 23:37:20',NULL,NULL),(14,'jason','$2y$10$dov3bbwg7.zM3qR.95OaQe2nSWpq8971dBL2XS.J/SJP/BpHQ4YEG','jdaluyon@gmail.com','2025-05-01 23:40:23',NULL,NULL),(17,'lau','$2y$10$uAFfOmR209Bv3PkzRQhyA.tKwjm59PYizuaL9hRJRFD729jyF2F82','laura@gmail.com','2025-05-07 20:07:17',NULL,NULL),(18,'becca','$2y$10$eZ.ULAPPK3BsCs3TL1gwieGgtAIKs4shcwjal1ELe7ugX2PjxG2xm','rebecca@gmail.com','2025-05-07 20:08:23',NULL,NULL),(19,'noreen','$2y$10$6W8SW/Kg.yM2BdeMdTHFa.II2cbNAVS4fX0CIgFSP.JpnhW18RJ9y','noreen@gmail.com','2025-05-07 20:09:23',NULL,NULL),(20,'fed','$2y$10$FDHxR3sWJsdmQu78DC7/xu5slpwWfviouyavzXrWUDlWpYsXv3CYK','federico@gmail.com','2025-05-07 20:09:54',NULL,NULL),(21,'ruby','$2y$10$Yu8k3eNirVY8qVI9QoHvYeHjeeEF6XhVNci1tAKIaZATYy/zL7XzK','rubyjane@gmail.com','2025-05-07 20:11:13',NULL,NULL),(22,'rac','$2y$10$uXOdZxoxVRQmVJIBokDLaOJRilmlWjl/Xa04jatBvFIli9ztwMVIK','racquel@gmail.com','2025-05-07 20:11:53',NULL,NULL),(23,'red','$2y$10$pl7uc5vM8raCSAD0kRsbmuyaGbNr8v./Z.cDMzpPmK3JrHf652ACq','rodolfo@gmail.com','2025-05-07 20:12:44',NULL,NULL),(24,'jhun','$2y$10$pseThMrIBwF8yghU9xQtVeDIFq1MtDFvjvYZ8IUoZiu07HR8Qj93e','jhun@gmail.com','2025-05-07 20:13:45',NULL,NULL),(25,'greta','$2y$10$j2vmMWlTQ1u9EIQTR1P0X.DcmssjakHrNe7lAZ2Warv.ZK7bh.ENu','greta@gmail.com','2025-05-07 20:14:23',NULL,NULL),(26,'randz','$2y$10$4UOp8HBLWB/b2cSxiUokFeJ6J1BsRM4JoRcfpTOd3b9TG89c3OX9S','randy@gmail.com','2025-05-07 20:15:44',NULL,NULL),(27,'berilnne','$2y$10$jO8nn.J0Yx5dMCosP9va7uESjxnoTWWaRPmg0N0x0KiseSrmuVf.2','berilnne@gmail.com','2025-05-07 20:17:14',NULL,NULL),(28,'martha','$2y$10$BvSWHjj4FZaQCDX79HCebOtARmOPaDNRKTFRxZkoGhj1LsHKczBU6','martha@gmail.com','2025-05-07 20:18:15',NULL,NULL),(29,'joseph','$2y$10$mXI6U1bGDklvSd3Xvi/XjuFLdUZ8zoyIV37mZFtDqGn0DRIY8uOfy','joseph@gmail.com','2025-05-07 20:19:07',NULL,NULL),(30,'alexen','$2y$10$LbqO8vc3/0O0kMS6SI4LsOd3j/AfYIYF1zvCE7yN0o/zbFYM.dqAi','alexen@gmail.com','2025-05-07 20:20:05',NULL,NULL),(31,'norman','$2y$10$lt7dwXyPtfrpUI7pwxl1setRrTPja2NL/Y1vgT7nPRJCHTtAmMPBO','norman@gmail.com','2025-05-07 20:21:07',NULL,NULL),(32,'mike','$2y$10$wqkC8Xjsoz2oPMDQQo2jzOHkIgMiVjN2lX6rIpa1Sn33ak3/BjccW','michael@gmail.com','2025-05-07 20:23:19',NULL,NULL),(33,'ramil','$2y$10$evBCwZs7znv2WRZ6tU/cXuL0wAqfYK//8ryuRPEH5raQd0rNgm6Ge','ramil@gmail.com','2025-05-07 20:24:15',NULL,NULL),(34,'dawn','$2y$10$SvcMePX35Ph4gaSyf7Yiceyi1MQ/bXj3LT./gjPBWsQg5lf9tTJTq','dawn@gmail.com','2025-05-07 20:25:47',NULL,NULL),(35,'sam','$2y$10$Ha2a7ONO156h2c.ac5POoOAWwz78co8WLSD25lG2MMuDKbSf.poJi','samantha@gmail.com','2025-05-07 20:26:39',NULL,NULL),(36,'jdelacruz','$2y$10$MhkTgT2aQJrQekeWu/Wwmu2o/Sncnje.Jcfv0SJgoQuhljVPf9oT.','juandelacruz@email.com','2025-05-09 14:32:53',NULL,NULL);
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auditlog`
--

DROP TABLE IF EXISTS `auditlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auditlog` (
  `AuditLogID` int(11) NOT NULL AUTO_INCREMENT,
  `FacultyID` int(11) DEFAULT NULL,
  `PersonnelID` int(11) DEFAULT NULL,
  `FullName` varchar(100) NOT NULL,
  `Description` text NOT NULL,
  `LogDateTime` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`AuditLogID`),
  KEY `FacultyID` (`FacultyID`),
  KEY `PersonnelID` (`PersonnelID`),
  CONSTRAINT `fk_auditlog_faculty` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`) ON DELETE SET NULL,
  CONSTRAINT `fk_auditlog_personnel` FOREIGN KEY (`PersonnelID`) REFERENCES `personnel` (`PersonnelID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auditlog`
--

LOCK TABLES `auditlog` WRITE;
/*!40000 ALTER TABLE `auditlog` DISABLE KEYS */;
INSERT INTO `auditlog` VALUES (4,2,10,'MILAN FRANCO SANTOS','Joined the faculty','2025-05-02 19:50:18'),(5,2,12,'REDGIE TAN','Assigned new role \'COR\' to MILAN FRANCO SANTOS','2025-05-02 23:56:23'),(6,2,12,'REDGIE TAN','Assigned new role \'FM\' to MILAN FRANCO SANTOS','2025-05-02 23:56:43'),(7,2,12,'REDGIE TAN','Removed MILAN FRANCO SANTOS from faculty','2025-05-02 23:56:48'),(8,2,12,'REDGIE TAN','Transferred deanship to JAYSON DALUYON','2025-05-03 15:26:03'),(9,2,14,'JAYSON DALUYON','Transferred deanship to REDGIE TAN','2025-05-03 15:26:37'),(10,2,12,'REDGIE TAN','Assigned new role \'COR\' to CATHERINE SORBITO','2025-05-03 17:42:57'),(11,2,12,'RIEGIE TAN','Assigned new role \'PH\' to Rebecca  Fajardo','2025-05-07 20:50:22'),(12,2,12,'RIEGIE TAN','Assigned new role \'PH\' to Noreen Perez','2025-05-07 20:50:32'),(13,2,12,'RIEGIE TAN','Assigned new role \'PH\' to REBECCA FAJARDO','2025-05-08 02:56:03'),(14,2,2,'ED RIAN','Joined the faculty','2025-05-08 03:03:36'),(15,2,12,'RIEGIE TAN','Removed ED RIAN from faculty','2025-05-08 03:12:45'),(16,2,12,'RIEGIE TAN','Assigned new role \'PH\' to NOREEN PEREZ','2025-05-09 19:12:28');
/*!40000 ALTER TABLE `auditlog` ENABLE KEYS */;
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
INSERT INTO `courses` VALUES ('COMP 101','Introduction to Computing'),('COMP 102','Fundamentals of Programming (C++)'),('COMP 103','Intermediate Programming (Java)'),('COMP 104','Data Structures and Algorithms'),('COMP 105','Information Management'),('COMP 106','Applications Development and Emerging Technologies'),('CS 101','Discrete Structures I'),('CS 102','Discrete Structures II'),('CS 103','Object-Oriented Programming (VB.Net with Database)'),('CS 104','Algorithms and Complexity'),('CS 105','Software Engineering 1'),('CS 106','Automata Theory and Formal Languages'),('CS 107','Architecture and Organization'),('CS 108','Information Assurance and Security'),('CS 109','Software Engineering II'),('CS 110','Operating Systems'),('CS 111','Programming Languages'),('CS 112','Social Issues and Professional Practice'),('CS 113','On-the-Job Training Program (162 hours)'),('CS 114','Human Computer Interaction'),('CS 115','CS Thesis 1'),('CS 116','Networks and Communications'),('CS 117','CS Thesis 2'),('CS 201','CS Elective: Intelligent Systems'),('CS 202','CS Elective: Parallel and Distributed Programming'),('CS 203','CS Elective: Graphics and Visual Computing'),('CS 301','Math Elective: Linear Algebra'),('CS 401','Digital Design'),('CS 402','Web Programming Development'),('CS 403','Open Source Programming with Database'),('CS 404','Multimedia Systems'),('CS 405','Open Source Programming with Framework'),('CS 406','Robotics'),('IT 101','Discrete Mathematics'),('IT 102','Quantitative Methods'),('IT 103','Advanced Database Systems'),('IT 104','Integrative Programming and Technologies I'),('IT 105','Networking I'),('IT 106','Systems Integration and Architecture 1'),('IT 107','Networking II'),('IT 108','Information Assurance and Security I'),('IT 109','Introduction to Human Computer Interaction'),('IT 110','Social and Professional Issues'),('IT 111','IT Capstone Project I'),('IT 112','Information Assurance and Security II'),('IT 113','System Administration and Maintenance'),('IT 114','IT Capstone Project II'),('IT 115','On-the-Job Training'),('IT 201','IT Elective: Platform Technologies'),('IT 202','IT Elective: Object-Oriented Programming (VB.Net)'),('IT 203','IT Elective: Integrative Programming and Technologies II'),('IT 204','IT Elective: Systems Integration and Architecture II'),('IT 301','Web Programming'),('IT 302','Software Engineering'),('IT 303','Technopreneurship'),('IT 304','IT Professional Ethics'),('IT 305','Web Development'),('IT 306','Multimedia and Technologies'),('IT103','Advanced Database Systems');
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curricula`
--

LOCK TABLES `curricula` WRITE;
/*!40000 ALTER TABLE `curricula` DISABLE KEYS */;
INSERT INTO `curricula` VALUES (1,'BSIT Curriculum 2022','2025-05-01 13:03:51',2,1),(2,'BSIT Curriculum 2020','2025-05-02 16:15:04',2,1),(3,'BSCS Curriculum 2020','2025-05-02 16:15:04',2,2),(6,'BSA Curriculum 2025','2025-05-04 17:53:51',3,12);
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faculties`
--

LOCK TABLES `faculties` WRITE;
/*!40000 ALTER TABLE `faculties` DISABLE KEYS */;
INSERT INTO `faculties` VALUES (1,'College of Hospitality Management','6Q61C'),(2,'College of Computer Studies','8FUNF'),(3,'ccs','IKWAS');
/*!40000 ALTER TABLE `faculties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL AUTO_INCREMENT,
  `AccountID` int(11) NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`NotificationID`),
  KEY `AccountID` (`AccountID`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`AccountID`) REFERENCES `personnel` (`AccountID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personnel`
--

LOCK TABLES `personnel` WRITE;
/*!40000 ALTER TABLE `personnel` DISABLE KEYS */;
INSERT INTO `personnel` VALUES (1,'EDRIAN','MARTINEZ','Male','USER',NULL,1),(2,'ED','RIAN','Male','FM',NULL,2),(4,'TEST','TEST','Male','USER',NULL,4),(5,'LYANAH','PAULA','Female','DN',1,5),(10,'MILAN FRANCO','SANTOS','Male','DN',3,10),(11,'REX','NAVARRO JR.','Male','FM',1,11),(12,'RIEGIE','TAN','Male','DN',2,12),(13,'CATHERINE','SORBITO','Female','COR',2,13),(14,'JAYSON','DALUYON','Male','FM',2,14),(16,'','',NULL,'USER',NULL,NULL),(17,'LAURA','ALTEA','Female','FM',2,17),(18,'REBECCA','FAJARDO','Female','PH',2,18),(19,'NOREEN','PEREZ','Female','PH',2,19),(20,'FEDERICO','NUEVA','Male','FM',2,20),(21,'RUBY JANE','DIOSA','Female','FM',2,21),(22,'RACQUEL','CORTEZ','Female','FM',2,22),(23,'RODOLFO','MIRABEL','Male','FM',2,23),(24,'JUANITO','ALVAREZ','Male','FM',2,24),(25,'GRETA','ROSARIO','Female','FM',2,25),(26,'RANDY','OTERO','Male','FM',2,26),(27,'BERILNNE','BOBIS','Female','FM',2,27),(28,'MARTHA ANDREA','DALUYON','Female','FM',2,28),(29,'JOSEPH WILFRED','DELA CRUZ','Male','FM',2,29),(30,'ALEXEN','ELACIO','Male','FM',2,30),(31,'NORMAN','ESPIRITU','Male','FM',2,31),(32,'MICHAEL','FERNANDEZ','Male','FM',2,32),(33,'RAMIL','MADRIAGA','Male','FM',2,33),(34,'DAWN BERNADETTE','MENOR','Female','FM',2,34),(35,'SAMANTHA','SIAO','Female','FM',2,35),(36,'JUAN','DELA CRUZ','Male','user',NULL,36);
/*!40000 ALTER TABLE `personnel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `program_courses`
--

DROP TABLE IF EXISTS `program_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `program_courses` (
  `ProgramCourseID` int(11) NOT NULL AUTO_INCREMENT,
  `ProgramID` int(11) NOT NULL,
  `CourseCode` varchar(10) NOT NULL,
  `CurriculumID` int(11) DEFAULT NULL,
  `FacultyID` int(11) DEFAULT NULL,
  `PersonnelID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ProgramCourseID`),
  UNIQUE KEY `uq_program_course` (`ProgramID`,`CourseCode`),
  UNIQUE KEY `unique_program_course_faculty` (`ProgramID`,`CourseCode`,`FacultyID`),
  KEY `CourseCode` (`CourseCode`),
  KEY `fk_program_courses_curriculum` (`CurriculumID`),
  KEY `fk_program_courses_personnel` (`PersonnelID`),
  CONSTRAINT `fk_program_courses_curriculum` FOREIGN KEY (`CurriculumID`) REFERENCES `curricula` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_program_courses_personnel` FOREIGN KEY (`PersonnelID`) REFERENCES `personnel` (`PersonnelID`),
  CONSTRAINT `program_courses_ibfk_1` FOREIGN KEY (`ProgramID`) REFERENCES `programs` (`ProgramID`) ON DELETE CASCADE,
  CONSTRAINT `program_courses_ibfk_2` FOREIGN KEY (`CourseCode`) REFERENCES `courses` (`CourseCode`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `program_courses`
--

LOCK TABLES `program_courses` WRITE;
/*!40000 ALTER TABLE `program_courses` DISABLE KEYS */;
INSERT INTO `program_courses` VALUES (1,1,'COMP 101',2,2,NULL),(2,1,'COMP 102',2,2,NULL),(3,1,'COMP 103',2,2,NULL),(4,1,'COMP 104',2,2,17),(5,1,'COMP 105',2,2,NULL),(6,1,'COMP 106',2,2,22),(7,1,'IT 101',2,2,17),(8,1,'IT 102',2,2,NULL),(9,1,'IT 103',2,2,18),(10,1,'IT 104',2,2,14),(11,1,'IT 105',2,2,NULL),(12,1,'IT 106',2,2,NULL),(13,1,'IT 107',2,2,NULL),(14,1,'IT 108',2,2,NULL),(15,1,'IT 109',2,2,NULL),(16,1,'IT 110',2,2,NULL),(17,1,'IT 111',2,2,NULL),(18,1,'IT 112',2,2,NULL),(19,1,'IT 113',2,2,NULL),(20,1,'IT 114',2,2,NULL),(21,1,'IT 115',2,2,NULL),(22,1,'IT 201',2,2,NULL),(23,1,'IT 202',2,2,NULL),(24,1,'IT 203',2,2,NULL),(25,1,'IT 204',2,2,NULL),(26,1,'IT 301',2,2,NULL),(27,1,'IT 302',2,2,NULL),(28,1,'IT 303',2,2,NULL),(29,1,'IT 304',2,2,NULL),(30,1,'IT 305',2,2,NULL),(31,1,'IT 306',2,2,NULL),(32,2,'COMP 101',3,2,NULL),(33,2,'COMP 102',3,2,NULL),(34,2,'COMP 103',3,2,NULL),(35,2,'COMP 104',3,2,NULL),(36,2,'COMP 105',3,2,NULL),(37,2,'CS 101',3,2,NULL),(38,2,'CS 102',3,2,NULL),(39,2,'CS 103',3,2,NULL),(40,2,'CS 104',3,2,NULL),(41,2,'CS 105',3,2,NULL),(42,2,'CS 106',3,2,NULL),(43,2,'CS 107',3,2,NULL),(44,2,'CS 108',3,2,NULL),(45,2,'CS 109',3,2,NULL),(46,2,'CS 110',3,2,NULL),(47,2,'CS 111',3,2,NULL),(48,2,'CS 112',3,2,NULL),(49,2,'CS 113',3,2,NULL),(50,2,'CS 114',3,2,NULL),(51,2,'CS 115',3,2,NULL),(52,2,'CS 116',3,2,NULL),(53,2,'CS 117',3,2,NULL),(54,2,'CS 201',3,2,NULL),(55,2,'CS 202',3,2,NULL),(56,2,'CS 203',3,2,NULL),(57,2,'CS 301',3,2,NULL),(58,2,'CS 401',3,2,NULL),(59,2,'CS 402',3,2,NULL),(60,2,'CS 403',3,2,NULL),(61,2,'CS 404',3,2,NULL),(62,2,'CS 405',3,2,NULL),(63,2,'CS 406',3,2,NULL);
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
INSERT INTO `programs` VALUES (1,'BSIT','Bachelor of Science in Information Technology'),(2,'BSCS','Bachelor of Science in Computer Science'),(12,'BSA','Bachelor of Science in Accountancy');
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
  `CurriculumID` int(11) DEFAULT NULL,
  PRIMARY KEY (`SubmissionID`,`CourseCode`),
  KEY `CourseCode` (`CourseCode`),
  KEY `LeadID` (`LeadID`),
  KEY `fk_submissioncourses_curriculum` (`CurriculumID`),
  CONSTRAINT `fk_submissioncourses_curriculum` FOREIGN KEY (`CurriculumID`) REFERENCES `curricula` (`id`) ON DELETE CASCADE,
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
  `SubmissionPath` varchar(255) DEFAULT NULL,
  `SubmittedBy` int(11) DEFAULT NULL,
  `SubmissionDate` datetime DEFAULT NULL,
  `CourseCode` varchar(10) DEFAULT NULL,
  `ProgramID` int(11) DEFAULT NULL,
  PRIMARY KEY (`SubmissionID`),
  KEY `FacultyID` (`FacultyID`),
  KEY `TaskID` (`TaskID`),
  KEY `fk_submissions_coursecode` (`CourseCode`),
  KEY `fk_submissions_programid` (`ProgramID`),
  CONSTRAINT `fk_submissions_coursecode` FOREIGN KEY (`CourseCode`) REFERENCES `courses` (`CourseCode`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_submissions_programid` FOREIGN KEY (`ProgramID`) REFERENCES `programs` (`ProgramID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`),
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`TaskID`) REFERENCES `tasks` (`TaskID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `submissions`
--

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
INSERT INTO `submissions` VALUES (1,2,1,NULL,NULL,'2024-2025','1st','uploads/tasks/1/2D-Group3-Courseware-Monitoring-Progress-Part1 (1).pdf',22,'2025-05-09 00:34:30','COMP 106',1),(2,2,2,NULL,NULL,'2024-2025','2nd','uploads/tasks/2/22619 -  Web Based Application development with PHP.pdf',14,'2025-05-09 01:16:38','IT 104',1),(9,2,5,NULL,NULL,'2024-2025','1st','uploads/tasks/5/DATABASE MANAGEMENT SYSTEMS.pdf',18,'2025-05-09 03:04:24','IT 103',1),(10,2,6,NULL,NULL,'2024-2025','2nd','uploads/tasks/6/COMP304 - Data Structures and Algorithms.pdf',17,'2025-05-09 03:16:02','COMP 104',1),(11,2,8,NULL,NULL,'2025-2026','1st','uploads/tasks/8/DATABASE MANAGEMENT SYSTEMS.pdf',22,'2025-05-09 14:48:44','COMP 106',1);
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_assignments`
--

DROP TABLE IF EXISTS `task_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_assignments` (
  `TaskAssignmentID` int(11) NOT NULL AUTO_INCREMENT,
  `TaskID` int(11) NOT NULL,
  `ProgramID` int(11) NOT NULL,
  `CourseCode` varchar(10) NOT NULL,
  `FacultyID` int(11) NOT NULL,
  `Status` enum('Pending','Submitted','Completed') DEFAULT 'Pending',
  `ReviewStatus` enum('Not Reviewed','Approved','Rejected') DEFAULT 'Not Reviewed',
  `SubmissionPath` varchar(255) DEFAULT NULL,
  `SubmissionDate` datetime DEFAULT NULL,
  `ApprovedBy` int(11) DEFAULT NULL,
  `ApprovalDate` datetime DEFAULT NULL,
  `RevisionReason` text DEFAULT NULL,
  PRIMARY KEY (`TaskAssignmentID`),
  UNIQUE KEY `task_assignment_unique` (`TaskID`,`ProgramID`,`CourseCode`,`FacultyID`),
  KEY `fk_task_assignments_tasks` (`TaskID`),
  KEY `fk_task_assignments_program_courses` (`ProgramID`,`CourseCode`,`FacultyID`),
  KEY `fk_task_assignments_approver` (`ApprovedBy`),
  CONSTRAINT `fk_task_assignments_approver` FOREIGN KEY (`ApprovedBy`) REFERENCES `personnel` (`PersonnelID`) ON DELETE SET NULL,
  CONSTRAINT `fk_task_assignments_program_courses` FOREIGN KEY (`ProgramID`, `CourseCode`, `FacultyID`) REFERENCES `program_courses` (`ProgramID`, `CourseCode`, `FacultyID`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_assignments_tasks` FOREIGN KEY (`TaskID`) REFERENCES `tasks` (`TaskID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_assignments`
--

LOCK TABLES `task_assignments` WRITE;
/*!40000 ALTER TABLE `task_assignments` DISABLE KEYS */;
INSERT INTO `task_assignments` VALUES (1,1,1,'COMP 106',2,'Completed','Approved','uploads/tasks/1/2D-Group3-Courseware-Monitoring-Progress-Part1 (1).pdf','2025-05-09 00:34:30',12,'2025-05-09 00:34:43','E DI KA MARUNONG MAAM'),(2,2,1,'IT 104',2,'Completed','Approved','uploads/tasks/2/22619 -  Web Based Application development with PHP.pdf','2025-05-09 01:16:38',12,'2025-05-09 01:17:36',NULL),(5,5,1,'IT 103',2,'Completed','Approved','uploads/tasks/5/DATABASE MANAGEMENT SYSTEMS.pdf','2025-05-09 03:04:24',12,'2025-05-09 03:04:58',NULL),(6,6,1,'COMP 104',2,'Completed','Approved','uploads/tasks/6/COMP304 - Data Structures and Algorithms.pdf','2025-05-09 03:16:02',12,'2025-05-09 03:16:39',NULL),(7,7,2,'COMP 101',2,'Pending','Not Reviewed',NULL,NULL,NULL,NULL,NULL),(8,7,1,'IT 101',2,'Pending','Not Reviewed',NULL,NULL,NULL,NULL,NULL),(9,7,1,'IT 104',2,'Pending','Not Reviewed',NULL,NULL,NULL,NULL,NULL),(10,8,1,'COMP 106',2,'Completed','Approved','uploads/tasks/8/DATABASE MANAGEMENT SYSTEMS.pdf','2025-05-09 14:48:44',12,'2025-05-09 14:49:17',NULL);
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
  `SchoolYear` varchar(30) NOT NULL,
  `Term` varchar(30) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`TaskID`),
  KEY `CreatedBy` (`CreatedBy`),
  KEY `FacultyID` (`FacultyID`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`CreatedBy`) REFERENCES `personnel` (`PersonnelID`),
  CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`FacultyID`) REFERENCES `faculties` (`FacultyID`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES (1,'SAMPLE','SAMPLE SAMPLE',12,2,'2025-05-11','Completed','2024-2025','1st','2025-05-08 14:59:11'),(2,'TEST 1','Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',12,2,'2025-05-11','Completed','2024-2025','2nd','2025-05-09 01:11:53'),(5,'sample 2','Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',12,2,'2025-05-10','Completed','2024-2025','1st','2025-05-09 02:57:14'),(6,'FOR MAAM LAU','Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',12,2,'2025-05-12','Completed','2024-2025','2nd','2025-05-09 03:14:27'),(7,'2526 2ND SEM SYLLABUS','Kindly Accomplish your syllabus by tomorrow! Thank you and God bless.',12,2,'2025-05-10','Pending','2025-2026','1st','2025-05-09 13:17:07'),(8,'daiowdawiodjaw','wdadawdaw',12,2,'2025-05-10','Completed','2025-2026','1st','2025-05-09 14:48:24');
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

-- Dump completed on 2025-05-09 20:59:03
