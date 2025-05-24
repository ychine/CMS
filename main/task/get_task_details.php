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

// Get task details
$taskSql = "SELECT t.*, CONCAT(p.FirstName, ' ', p.LastName) as CreatorName 
            FROM tasks t 
            LEFT JOIN personnel p ON t.CreatedBy = p.PersonnelID 
            WHERE t.TaskID = ?";
$taskStmt = $conn->prepare($taskSql);
$taskStmt->bind_param("i", $taskId);
$taskStmt->execute();
$taskResult = $taskStmt->get_result();

if ($taskResult->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit();
}

$task = $taskResult->fetch_assoc();

// Get course assignments
$coursesSql = "SELECT ta.TaskAssignmentID, ta.ProgramID, ta.CourseCode, c.Title as CourseTitle, 
              p.ProgramName, p.ProgramCode, CONCAT(per.FirstName, ' ', per.LastName) as AssignedTo,
              ta.Status as AssignmentStatus, ta.SubmissionPath, ta.SubmissionDate, pc.PersonnelID as PersonnelID
              FROM task_assignments ta
              JOIN courses c ON ta.CourseCode = c.CourseCode
              JOIN programs p ON ta.ProgramID = p.ProgramID
              LEFT JOIN program_courses pc ON ta.CourseCode = pc.CourseCode AND ta.ProgramID = pc.ProgramID
              LEFT JOIN personnel per ON pc.PersonnelID = per.PersonnelID
              WHERE ta.TaskID = ?
              ORDER BY p.ProgramName, ta.CourseCode";
$coursesStmt = $conn->prepare($coursesSql);
$coursesStmt->bind_param("i", $taskId);
$coursesStmt->execute();
$coursesResult = $coursesStmt->get_result();

$courses = [];
while ($courseRow = $coursesResult->fetch_assoc()) {
    $courses[] = $courseRow;
}

$task['Courses'] = $courses;

header('Content-Type: application/json');
echo json_encode(['success' => true, 'task' => $task]);

$taskStmt->close();
$coursesStmt->close();
$conn->close();
?> 