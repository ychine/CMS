<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Check if required fields are set
$requiredFields = ['program_id', 'curriculum_id', 'course_code', 'course_title'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Get form data
$programId = $_POST['program_id'];
$curriculumId = $_POST['curriculum_id'];
$courseCode = $_POST['course_code'];
$courseTitle = $_POST['course_title'];

// Connect to database
$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Check if the course already exists in this specific curriculum
    $checkCourseStmt = $conn->prepare("
        SELECT pc.CourseCode 
        FROM program_courses pc 
        WHERE pc.ProgramID = ? AND pc.CurriculumID = ? AND pc.CourseCode = ?
    ");
    $checkCourseStmt->bind_param("iis", $programId, $curriculumId, $courseCode);
    $checkCourseStmt->execute();
    $result = $checkCourseStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "Course $courseCode is already added to this curriculum"]);
        exit();
    }
    $checkCourseStmt->close();

    // Check if course exists in courses table
    $checkCourseStmt = $conn->prepare("SELECT CourseCode FROM courses WHERE CourseCode = ?");
    $checkCourseStmt->bind_param("s", $courseCode);
    $checkCourseStmt->execute();
    $result = $checkCourseStmt->get_result();
    
    if ($result->num_rows === 0) {
        // Course doesn't exist, so insert it
        $insertCourseStmt = $conn->prepare("INSERT INTO courses (CourseCode, Title) VALUES (?, ?)");
        $insertCourseStmt->bind_param("ss", $courseCode, $courseTitle);
        
        if (!$insertCourseStmt->execute()) {
            throw new Exception("Failed to insert course: " . $insertCourseStmt->error);
        }
        $insertCourseStmt->close();
    }
    $checkCourseStmt->close();

    // Get the faculty ID from the session
    $accountID = $_SESSION['AccountID'];
    $facultyIdStmt = $conn->prepare("SELECT FacultyID FROM personnel WHERE AccountID = ?");
    $facultyIdStmt->bind_param("i", $accountID);
    $facultyIdStmt->execute();
    $facultyResult = $facultyIdStmt->get_result();
    
    if ($facultyRow = $facultyResult->fetch_assoc()) {
        $facultyID = $facultyRow['FacultyID'];
    } else {
        throw new Exception("Faculty ID not found for account");
    }
    $facultyIdStmt->close();

    // Add the course to the program_courses table
    $insertProgramCourseStmt = $conn->prepare("
        INSERT INTO program_courses (ProgramID, CurriculumID, CourseCode, FacultyID) 
        VALUES (?, ?, ?, ?)
    ");
    $insertProgramCourseStmt->bind_param("iisi", $programId, $curriculumId, $courseCode, $facultyID);
    
    if (!$insertProgramCourseStmt->execute()) {
        throw new Exception("Failed to add course to curriculum: " . $insertProgramCourseStmt->error);
    }
    $insertProgramCourseStmt->close();

    // Commit transaction
    $conn->commit();
    
    // Success response
    echo json_encode(['success' => true, 'message' => "Course $courseCode - $courseTitle added successfully"]);

} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
}

$conn->close();
?>