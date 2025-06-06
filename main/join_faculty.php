<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "CMS");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['faculty_code'])) {
    $facultyCode = trim($_POST['faculty_code']);
    $accountID = $_SESSION['AccountID'];

    $query = "SELECT FacultyID FROM faculties WHERE JoinCode = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $facultyCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $facultyID = $row['FacultyID'];

        $updateQuery = "UPDATE personnel SET FacultyID = ?, Role = 'FM' WHERE AccountID = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("ii", $facultyID, $accountID);

        if ($updateStmt->execute()) {

            $userQuery = "SELECT PersonnelID, FirstName, LastName FROM personnel WHERE AccountID = ?";
            $userStmt = $conn->prepare($userQuery);
            $userStmt->bind_param("i", $accountID);
            $userStmt->execute();
            $userResult = $userStmt->get_result();

            if ($userResult->num_rows > 0) {
                $user = $userResult->fetch_assoc();
                $personnelID = $user['PersonnelID'];
                $fullName = $user['FirstName'] . ' ' . $user['LastName'];
                $description = "Joined the faculty";

                $logQuery = "INSERT INTO auditlog (FacultyID, PersonnelID, FullName, Description) VALUES (?, ?, ?, ?)";
                $logStmt = $conn->prepare($logQuery);
                $logStmt->bind_param("iiss", $facultyID, $personnelID, $fullName, $description);
                $logStmt->execute();
                $logStmt->close();
            }

            $userStmt->close();

            $_SESSION['joined_faculty_success'] = "Successfully joined faculty!";
            header("Location: homepage.php?joined=1");
            exit();
        } else {
            $_SESSION['joined_faculty_error'] = "Failed to update user information. Please try again.";
            $_SESSION['show_join_form'] = true;
            header("Location: homepage.php");
            exit();
        }

        $updateStmt->close();
    } else {
        $_SESSION['joined_faculty_error'] = "Invalid faculty code. Please try again.";
        $_SESSION['show_join_form'] = true;
        header("Location: homepage.php");
        exit();
    }

    $stmt->close();
}

$conn->close();
header("Location: homepage.php");
exit();
?>
