<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['program_code']) && isset($_POST['program_name'])) {
    $programCode = trim(htmlspecialchars($_POST['program_code']));
    $programName = trim(htmlspecialchars($_POST['program_name']));

    if (empty($programCode) || empty($programName)) {
        $_SESSION['error'] = "Program code and name cannot be empty.";
        header("Location: curriculum_frame.php");
        exit();
    }

    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $username = $_SESSION['Username'];  
    // Corrected query to fetch FacultyID based on the Username from the accounts table
    $stmt = $conn->prepare("SELECT p.FacultyID FROM personnel p 
                            JOIN accounts a ON p.AccountID = a.AccountID 
                            WHERE a.Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($facultyID);
        $stmt->fetch();
    } else {
        $_SESSION['error'] = "Faculty not found for this user.";
        $stmt->close();
        $conn->close();
        header("Location: curriculum_frame.php");
        exit();
    }
    $stmt->close();

    // Check if program already exists
    $stmt = $conn->prepare("SELECT ProgramID FROM programs WHERE ProgramCode = ?");
    $stmt->bind_param("s", $programCode);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $_SESSION['error'] = "Program already exists.";
        $stmt->close();
        $conn->close();
        header("Location: curriculum_frame.php");
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO programs (ProgramCode, ProgramName) VALUES (?, ?)");
    $stmt->bind_param("ss", $programCode, $programName);

    if ($stmt->execute()) {
        $programID = $stmt->insert_id;
        $stmt->close();

        $yearStarted = date("Y");

        // Insert into curricula
        $curriculumName = $programCode . " Curriculum " . $yearStarted;

        $stmt = $conn->prepare("INSERT INTO curricula (ProgramID, name, FacultyID) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $programID, $curriculumName, $facultyID);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Program and default curriculum added successfully.";
        } else {
            $_SESSION['error'] = "Program added, but failed to create curriculum.";
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Failed to add program.";
        $stmt->close();
    }

    $conn->close();
    header("Location: curriculum_frame.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: curriculum_frame.php");
    exit();
}
?>
