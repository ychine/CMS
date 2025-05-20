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

$search = isset($_GET['search']) ? $_GET['search'] : '';
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

if (empty($search)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Search term is required']);
    exit();
}

$sql = "SELECT DISTINCT c.CourseCode, c.Title 
        FROM courses c
        JOIN program_courses pc ON c.CourseCode = pc.CourseCode
        JOIN curricula cu ON pc.CurriculumID = cu.id
        WHERE cu.FacultyID = ? 
        AND (c.CourseCode LIKE ? OR c.Title LIKE ?)
        ORDER BY c.CourseCode
        LIMIT 10";

$stmt = $conn->prepare($sql);
$searchTerm = "%$search%";
$stmt->bind_param("iss", $facultyID, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$courses = [];
while ($row = $result->fetch_assoc()) {
    $courses[] = [
        'CourseCode' => $row['CourseCode'],
        'Title' => $row['Title']
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'courses' => $courses
]);

$stmt->close();
$conn->close();
?> 