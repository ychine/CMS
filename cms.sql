CREATE VIEW vPendingTasks AS
SELECT DISTINCT
    sc.SubmissionID,
    c.CourseCode,
    c.Title,
    c.Curriculum,
    s.SchoolYear,
    s.Term,
    s.Printed,
    s.Esign,
    p.Personnel AS LeadName,
    f.Faculty AS College
FROM SubmissionCourses sc
JOIN Submissions s ON sc.SubmissionID = s.SubmissionID
JOIN Courses c ON sc.CourseCode = c.CourseCode
JOIN Personnel p ON sc.LeadID = p.PersonnelID
JOIN Faculties f ON p.Faculty = f.FacultyID
WHERE (sc.LeadID = CURRENT_USER_ID
       OR sc.SubmissionID IN (
           SELECT tm.SubmissionID
           FROM TeamMembers tm
           WHERE tm.MembersID = CURRENT_USER_ID
       ))
  AND (s.Printed <> 'DONE' OR s.Esign <> 'DONE');

CREATE VIEW vFacultyNotSubmitted AS
SELECT p.PersonnelID, p.Personnel, p.Role, f.Faculty
FROM Personnel p
JOIN Faculties f ON p.Faculty = f.FacultyID
WHERE p.Role = 'FM'
AND p.PersonnelID NOT IN (
    SELECT DISTINCT LeadID
    FROM SubmissionCourses
);


CREATE DATABASE CMS;

CREATE TABLE Faculties (
    FacultyID INT PRIMARY KEY,
    FacultyName VARCHAR(100)
);


CREATE TABLE Personnel (
    PersonnelID INT PRIMARY KEY,
    PersonnelName VARCHAR(100),
    Role VARCHAR(10),
    FacultyID INT,
    FOREIGN KEY (FacultyID) REFERENCES Faculties(FacultyID)
);

CREATE TABLE Courses (
    CourseCode VARCHAR(10) PRIMARY KEY,
    Title VARCHAR(100),
    CurriculumYear INT
);

CREATE TABLE Submissions (
    SubmissionID INT PRIMARY KEY,
    FacultyID INT,
    Printed VARCHAR(20),
    Esign VARCHAR(100),
    SchoolYear VARCHAR(20),
    Term VARCHAR(10),
    FOREIGN KEY (FacultyID) REFERENCES Faculties(FacultyID)
);

CREATE TABLE SubmissionCourses (
    SubmissionID INT,
    CourseCode VARCHAR(10),
    LeadID INT,
    PRIMARY KEY (SubmissionID, CourseCode),
    FOREIGN KEY (SubmissionID) REFERENCES Submissions(SubmissionID),
    FOREIGN KEY (CourseCode) REFERENCES Courses(CourseCode),
    FOREIGN KEY (LeadID) REFERENCES Personnel(PersonnelID)
);

CREATE TABLE TeamMembers (
    SubmissionID INT,
    MembersID INT,
    PRIMARY KEY (SubmissionID, MembersID),
    FOREIGN KEY (SubmissionID) REFERENCES Submissions(SubmissionID),
    FOREIGN KEY (MembersID) REFERENCES Personnel(PersonnelID)
);
