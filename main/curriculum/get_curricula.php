<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_GET['program_id']) || empty($_GET['program_id'])) {
    echo json_encode(['success' => false, 'message' => 'Program ID is required']);
    exit();
}

$programId = $_GET['program_id'];


$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]);
    exit();
}


$accountID = $_SESSION['AccountID'];
$facultyIdStmt = $conn->prepare("SELECT FacultyID FROM personnel WHERE AccountID = ?");
$facultyIdStmt->bind_param("i", $accountID);
$facultyIdStmt->execute();
$facultyResult = $facultyIdStmt->get_result();

if ($facultyRow = $facultyResult->fetch_assoc()) {
    $facultyID = $facultyRow['FacultyID'];
} else {
    echo json_encode(['success' => false, 'message' => "Faculty ID not found"]);
    exit();
}
$facultyIdStmt->close();

// Get curricula for the selected program where the faculty has access
$curriculaStmt = $conn->prepare("
    SELECT id, name 
    FROM curricula 
    WHERE ProgramID = ? AND FacultyID = ?
    ORDER BY name
");
$curriculaStmt->bind_param("ii", $programId, $facultyID);
$curriculaStmt->execute();
$result = $curriculaStmt->get_result();

$curricula = [];
while ($row = $result->fetch_assoc()) {
    $curricula[] = [
        'id' => $row['id'],
        'name' => $row['name']
    ];
}
$curriculaStmt->close();

$conn->close();

// Return the curricula as JSON
header('Content-Type: application/json');
echo json_encode(['success' => true, 'curricula' => $curricula]);
?>