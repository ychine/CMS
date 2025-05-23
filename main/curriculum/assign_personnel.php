<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_log("assign_personnel.php called");


header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "cms");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

$personnelId = $_POST['personnel_id'] ?? null;
$courseTitle = $_POST['course_title'] ?? null;
$curriculumName = $_POST['curriculum'] ?? null;
$programId = $_POST['program_id'] ?? null;

if (!$personnelId || !$courseTitle || !$curriculumName || !$programId) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

$stmt = $conn->prepare("
    SELECT co.CourseCode, c.id AS CurriculumID
    FROM courses co
    JOIN program_courses pc ON pc.CourseCode = co.CourseCode
    JOIN curricula c ON c.id = pc.CurriculumID
    WHERE co.Title = ? AND c.name = ? AND c.ProgramID = ?
    LIMIT 1
");
$stmt->bind_param("ssi", $courseTitle, $curriculumName, $programId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $courseCode = $row['CourseCode'];
    $curriculumId = $row['CurriculumID'];
} else {
    echo json_encode(["success" => false, "message" => "Course not found"]);
    exit;
}
$stmt->close();

// Check for pending tasks
$checkTasksStmt = $conn->prepare("
    SELECT COUNT(*) as pending_count 
    FROM task_assignments ta 
    JOIN tasks t ON ta.TaskID = t.TaskID 
    WHERE ta.CourseCode = ? 
    AND ta.ProgramID = ? 
    AND ta.Status IN ('Pending', 'In Progress')
");
$checkTasksStmt->bind_param("si", $courseCode, $programId);
$checkTasksStmt->execute();
$tasksResult = $checkTasksStmt->get_result();
$pendingTasks = $tasksResult->fetch_assoc()['pending_count'];
$checkTasksStmt->close();

if ($pendingTasks > 0) {
    echo json_encode([
        "success" => false, 
        "message" => "Cannot reassign professor. There are pending or ongoing tasks for this course."
    ]);
    exit;
}

$stmt = $conn->prepare("UPDATE program_courses SET PersonnelID = ? WHERE CurriculumID = ? AND CourseCode = ?");          // Update assignment for course
$stmt->bind_param("iis", $personnelId, $curriculumId, $courseCode);
$success = $stmt->execute();
$stmt->close();

echo json_encode(["success" => $success]);
