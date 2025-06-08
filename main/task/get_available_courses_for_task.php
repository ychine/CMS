<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if (!isset($_GET['task_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Task ID not provided']);
    exit();
}

$taskId = $_GET['task_id'];
$accountID = $_SESSION['AccountID'];

$facultyQuery = "SELECT FacultyID FROM personnel WHERE AccountID = ?";
$facultyStmt = $conn->prepare($facultyQuery);
$facultyStmt->bind_param("i", $accountID);
$facultyStmt->execute();
$facultyResult = $facultyStmt->get_result();
if ($facultyRow = $facultyResult->fetch_assoc()) {
    $facultyID = $facultyRow['FacultyID'];
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Faculty not found']);
    exit();
}
$facultyStmt->close();

$assigned = [];
$assignedSql = "SELECT ProgramID, CourseCode FROM task_assignments WHERE TaskID = ?";
$assignedStmt = $conn->prepare($assignedSql);
$assignedStmt->bind_param("i", $taskId);
$assignedStmt->execute();
$assignedResult = $assignedStmt->get_result();
while ($row = $assignedResult->fetch_assoc()) {
    $assigned[$row['ProgramID'] . '|' . $row['CourseCode']] = true;
}
$assignedStmt->close();

$courses = [];
$coursesSql = "SELECT pc.ProgramID, p.ProgramCode, p.ProgramName, pc.CourseCode, c.Title, cu.ID as CurriculumID, cu.Name AS CurriculumName, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo
    FROM program_courses pc
    JOIN courses c ON pc.CourseCode = c.CourseCode
    JOIN programs p ON pc.ProgramID = p.ProgramID
    LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
    LEFT JOIN curricula cu ON pc.CurriculumID = cu.ID
    WHERE pc.FacultyID = ?
    ORDER BY p.ProgramName, c.CourseCode";
$coursesStmt = $conn->prepare($coursesSql);
$coursesStmt->bind_param("i", $facultyID);
$coursesStmt->execute();
$coursesResult = $coursesStmt->get_result();
while ($row = $coursesResult->fetch_assoc()) {
    $key = $row['ProgramID'] . '|' . $row['CourseCode'];
    if (!isset($assigned[$key])) {
        $courses[] = [
            'ProgramID' => $row['ProgramID'],
            'ProgramCode' => $row['ProgramCode'],
            'ProgramName' => $row['ProgramName'],
            'CourseCode' => $row['CourseCode'],
            'Title' => $row['Title'],
            'CurriculumID' => $row['CurriculumID'],
            'CurriculumName' => $row['CurriculumName'],
            'AssignedTo' => $row['AssignedTo']
        ];
    }
}
$coursesStmt->close();

header('Content-Type: application/json');
echo json_encode(['success' => true, 'courses' => $courses]);
$conn->close(); 