<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}


$isAjax = isset($_POST['ajax']) && $_POST['ajax'] === 'true';

$programId = isset($_POST['program_id']) ? $_POST['program_id'] : null;
$curriculumYear = isset($_POST['curriculum_year']) ? $_POST['curriculum_year'] : null;
$courseCode = isset($_POST['course_code']) ? $_POST['course_code'] : null;

if (!$programId || !$curriculumYear || !$courseCode) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
    } else {
        $_SESSION['error'] = 'Missing required data';
        header("Location: curriculum_frame.php");
    }
    exit();
}

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

try {
    
    $conn->begin_transaction();

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


    $deleteQuery = "DELETE FROM program_courses WHERE CurriculumID = ? AND CourseCode = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("is", $curriculumId, $courseCode);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to remove course from curriculum");
    }
    
    $stmt->close();

   
    $conn->commit();

    // --- AUDIT LOG ---
    $facultyID = null;
    $personnelID = $_SESSION['AccountID'];
    $fullName = getFullName($conn, $personnelID);
   
    $getFaculty = $conn->prepare("SELECT FacultyID FROM curricula WHERE id = ?");
    $getFaculty->bind_param("i", $curriculumId);
    $getFaculty->execute();
    $getFacultyResult = $getFaculty->get_result();
    if ($getFacultyResult && $getFacultyResult->num_rows > 0) {
        $facultyID = $getFacultyResult->fetch_assoc()['FacultyID'];
    }
    $getFaculty->close();
    $description = "Deleted course: $courseCode from curriculum $curriculumYear";
    if ($facultyID) {
        $logSql = "INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description, LogDateTime)
                   VALUES (?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("iiss", $facultyID, $personnelID, $fullName, $description);
        $logStmt->execute();
        $logStmt->close();
    }

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Course removed successfully']);
    } else {
        $_SESSION['success'] = 'Course removed successfully';
        header("Location: curriculum_frame.php");
    }

} catch (Exception $e) {
    
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