<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['Username'])) {
    $_SESSION['error'] = 'Not logged in';
    header('Location: curriculum_frame.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("POST data: " . print_r($_POST, true));

    $isNewProgram = isset($_POST['is_new_program']) && $_POST['is_new_program'] === '1';
    $curriculumYear = isset($_POST['curriculum_year']) ? $_POST['curriculum_year'] : '';
    $curriculumName = isset($_POST['curriculum_name']) ? $_POST['curriculum_name'] : '';

    if (!$curriculumYear || !$curriculumName) {
        $_SESSION['error'] = 'All fields are required';
        header('Location: curriculum_frame.php');
        exit();
    }

    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        $_SESSION['error'] = 'Database connection failed: ' . $conn->connect_error;
        header('Location: curriculum_frame.php');
        exit();
    }

    $username = $_SESSION['Username'];
    
    // Get FacultyID based on username
    $stmt = $conn->prepare("SELECT p.FacultyID FROM personnel p 
                            JOIN accounts a ON p.AccountID = a.AccountID 
                            WHERE a.Username = ?");
    if (!$stmt) {
        $_SESSION['error'] = 'Prepare failed: ' . $conn->error;
        header('Location: curriculum_frame.php');
        exit();
    }

    $stmt->bind_param("s", $username);
    if (!$stmt->execute()) {
        $_SESSION['error'] = 'Execute failed: ' . $stmt->error;
        header('Location: curriculum_frame.php');
        exit();
    }

    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Faculty not found for this user';
        header('Location: curriculum_frame.php');
        exit();
    }
    
    $facultyId = $result->fetch_assoc()['FacultyID'];
    $stmt->close();

    if ($isNewProgram) {
        // Handle new program creation
        $programCode = isset($_POST['program_code']) ? $_POST['program_code'] : '';
        $programName = isset($_POST['program_name']) ? $_POST['program_name'] : '';

        if (!$programCode || !$programName) {
            $_SESSION['error'] = 'Program code and name are required';
            header('Location: curriculum_frame.php');
            exit();
        }

        // Check if program already exists for this faculty
        $checkStmt = $conn->prepare("
            SELECT p.ProgramID 
            FROM programs p
            JOIN curricula c ON p.ProgramID = c.ProgramID
            WHERE p.ProgramCode = ? AND c.FacultyID = ?
        ");
        if (!$checkStmt) {
            $_SESSION['error'] = 'Prepare failed: ' . $conn->error;
            header('Location: curriculum_frame.php');
            exit();
        }

        $checkStmt->bind_param("si", $programCode, $facultyId);
        if (!$checkStmt->execute()) {
            $_SESSION['error'] = 'Execute failed: ' . $checkStmt->error;
            header('Location: curriculum_frame.php');
            exit();
        }

        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['error'] = 'Program already exists in your faculty';
            header('Location: curriculum_frame.php');
            exit();
        }
        $checkStmt->close();

        // Insert new program
        $programStmt = $conn->prepare("INSERT INTO programs (ProgramCode, ProgramName) VALUES (?, ?)");
        if (!$programStmt) {
            $_SESSION['error'] = 'Prepare failed: ' . $conn->error;
            header('Location: curriculum_frame.php');
            exit();
        }

        $programStmt->bind_param("ss", $programCode, $programName);
        
        if (!$programStmt->execute()) {
            $_SESSION['error'] = 'Error creating program: ' . $programStmt->error;
            header('Location: curriculum_frame.php');
            exit();
        }

        $programId = $programStmt->insert_id;
        $programStmt->close();
    } else {
        // Use existing program
        $programId = isset($_POST['existing_program']) ? $_POST['existing_program'] : '';
        
        if (!$programId) {
            $_SESSION['error'] = 'Please select a program';
            header('Location: curriculum_frame.php');
            exit();
        }
    }

    // Create curriculum - removed CurriculumYear from the query since it doesn't exist in the table
    $curriculumStmt = $conn->prepare("INSERT INTO curricula (ProgramID, name, FacultyID) VALUES (?, ?, ?)");
    if (!$curriculumStmt) {
        $_SESSION['error'] = 'Prepare failed: ' . $conn->error;
        header('Location: curriculum_frame.php');
        exit();
    }

    $curriculumStmt->bind_param("isi", $programId, $curriculumName, $facultyId);
    
    if (!$curriculumStmt->execute()) {
        $_SESSION['error'] = 'Error creating curriculum: ' . $curriculumStmt->error;
        header('Location: curriculum_frame.php');
        exit();
    }

    $_SESSION['success'] = 'Curriculum created successfully';
    header('Location: curriculum_frame.php');
    exit();

    $curriculumStmt->close();
    $conn->close();
} else {
    $_SESSION['error'] = 'Invalid request method';
    header('Location: curriculum_frame.php');
    exit();
}
?>
