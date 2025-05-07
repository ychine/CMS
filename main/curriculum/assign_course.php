<?php
session_start();

if (!isset($_SESSION['Username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

$curriculumID = $_POST['curriculum_id'] ?? null;
$courseCode = $_POST['course_code'] ?? null;
$personnelID = $_POST['personnel_id'] ?? null;

if (!$curriculumID || !$courseCode || !$personnelID) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit();
}

$stmt = $conn->prepare("
    UPDATE program_courses 
    SET PersonnelID = ? 
    WHERE CurriculumID = ? AND CourseCode = ?
");
$stmt->bind_param("iis", $personnelID, $curriculumID, $courseCode);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$stmt->close();
$conn->close();
?>
