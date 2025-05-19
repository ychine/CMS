<?php

error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set JSON header
header('Content-Type: application/json');

if (!isset($_SESSION['Username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_POST['program_id']) || !isset($_POST['curriculum_year'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$programId = $_POST['program_id'];
$curriculumYear = $_POST['curriculum_year'];

try {
    // Direct database connection without requiring config file
    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // First, get the curriculum ID
        $stmt = $conn->prepare("SELECT id FROM curricula WHERE ProgramID = ? AND name LIKE ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $curriculumName = "%" . $curriculumYear . "%";
        $stmt->bind_param("is", $programId, $curriculumName);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Curriculum not found for program ID: $programId and year: $curriculumYear");
        }
        
        $curriculumId = $result->fetch_assoc()['id'];
        $stmt->close();
        
        // Before deleting anything, get faculty ID and curriculum name
        $getFaculty = $conn->prepare("SELECT FacultyID, name FROM curricula WHERE id = ?");
        $getFaculty->bind_param("i", $curriculumId);
        $getFaculty->execute();
        $getFacultyResult = $getFaculty->get_result();
        $facultyID = null;
        if ($getFacultyResult && $getFacultyResult->num_rows > 0) {
            $row = $getFacultyResult->fetch_assoc();
            $facultyID = $row['FacultyID'];
            $curriculumName = $row['name'];
        }
        $getFaculty->close();
        
        // Delete associated program courses
        $stmt = $conn->prepare("DELETE FROM program_courses WHERE CurriculumID = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $curriculumId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        
        // Delete the curriculum
        $stmt = $conn->prepare("DELETE FROM curricula WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $curriculumId);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        
        // Commit transaction
        if (!$conn->commit()) {
            throw new Exception("Commit failed: " . $conn->error);
        }

        // --- AUDIT LOG ---
        $personnelID = $_SESSION['AccountID'];
        $fullName = getFullName($conn, $personnelID);
        $description = "Deleted curriculum: $curriculumName";
        if ($facultyID) {
            $logSql = "INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description, LogDateTime)
                       VALUES (?, ?, ?, ?, NOW())";
            $logStmt = $conn->prepare($logSql);
            $logStmt->bind_param("iiss", $facultyID, $personnelID, $fullName, $description);
            $logStmt->execute();
            $logStmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Curriculum deleted successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw new Exception("Database operation failed: " . $e->getMessage());
    }
} catch (Exception $e) {
    error_log("Curriculum deletion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete curriculum: ' . $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
exit(); // Ensure script ends here

// Helper to get full name
function getFullName($conn, $accountId) {
    $stmt = $conn->prepare("SELECT FirstName, LastName FROM personnel WHERE AccountID = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $name = 'Unknown User';
    if ($row = $result->fetch_assoc()) {
        $name = trim($row['FirstName'] . ' ' . $row['LastName']);
    }
    $stmt->close();
    return $name;
}
?> 