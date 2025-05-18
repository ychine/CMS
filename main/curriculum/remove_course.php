<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

// Check if it's an AJAX request
$isAjax = isset($_POST['ajax']) && $_POST['ajax'] === 'true';

// Get POST data
$programId = isset($_POST['program_id']) ? $_POST['program_id'] : null;
$curriculumYear = isset($_POST['curriculum_year']) ? $_POST['curriculum_year'] : null;
$courseCode = isset($_POST['course_code']) ? $_POST['course_code'] : null;

// Validate required data
if (!$programId || !$curriculumYear || !$courseCode) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
    } else {
        $_SESSION['error'] = 'Missing required data';
        header("Location: curriculum_frame.php");
    }
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    } else {
        $_SESSION['error'] = 'Database connection failed';
        header("Location: curriculum_frame.php");
    }
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First, get the curriculum ID
    $curriculumQuery = "SELECT id FROM curricula WHERE ProgramID = ? AND name = ?";
    $stmt = $conn->prepare($curriculumQuery);
    $stmt->bind_param("is", $programId, $curriculumYear);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Curriculum not found");
    }
    
    $curriculumId = $result->fetch_assoc()['id'];
    $stmt->close();

    // Delete the course from program_courses
    $deleteQuery = "DELETE FROM program_courses WHERE CurriculumID = ? AND CourseCode = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("is", $curriculumId, $courseCode);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to remove course from curriculum");
    }
    
    $stmt->close();

    // Commit transaction
    $conn->commit();

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Course removed successfully']);
    } else {
        $_SESSION['success'] = 'Course removed successfully';
        header("Location: curriculum_frame.php");
    }

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } else {
        $_SESSION['error'] = $e->getMessage();
        header("Location: curriculum_frame.php");
    }
}

$conn->close();
?> 