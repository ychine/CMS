<?php
session_start();
if (!isset($_SESSION['AccountID'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['program_code'])) {
    $programCode = $_POST['program_code'];
    $accountID = $_SESSION['AccountID'];

    $conn = new mysqli("localhost", "root", "", "cms");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $facultyID = null;
    $stmt = $conn->prepare("SELECT FacultyID FROM personnel WHERE AccountID = ?");
    $stmt->bind_param("i", $accountID);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $facultyID = $row['FacultyID'];
    }
    $stmt->close();

    if ($facultyID) {
  
        $stmt = $conn->prepare("UPDATE curricula SET FacultyID = NULL WHERE ProgramID = (SELECT ProgramID FROM programs WHERE ProgramCode = ?) AND FacultyID = ?");
        $stmt->bind_param("si", $programCode, $facultyID);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
}


header("Location: ../curriculum/curriculum_frame.php");
exit();
?>
