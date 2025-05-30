<?php
session_start();

if (!isset($_SESSION['Username'])) {
    header("Location: ../../index.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$accountID = $_SESSION['AccountID'];

$userQuery = "SELECT p.FacultyID, p.Role 
              FROM personnel p 
              WHERE p.AccountID = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $accountID);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult && $userResult->num_rows > 0) {
    $userData = $userResult->fetch_assoc();
    $facultyID = $userData['FacultyID'];
    $userRole = $userData['Role'];

    if ($userRole === 'DN') {
        $_SESSION['error'] = "As the Dean of this faculty, you cannot leave until you transfer your deanship to another member.";
        header("Location: faculty_frame.php");
        exit();
    }

    $conn->begin_transaction();

    try {
     
        $updateQuery = "UPDATE personnel SET FacultyID = NULL WHERE AccountID = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("i", $accountID);
        $updateStmt->execute();

        $logQuery = "INSERT INTO auditlog (FacultyID, FullName, Description, LogDateTime) 
                    SELECT ?, 
                           CONCAT(FirstName, ' ', LastName),
                           'Left the faculty',
                           NOW()
                    FROM personnel 
                    WHERE AccountID = ?";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bind_param("ii", $facultyID, $accountID);
        $logStmt->execute();

        $conn->commit();

        $_SESSION['success'] = "You have successfully left the faculty.";
        
       
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <script>
               
                window.parent.location.href = '../homepage.php';
            </script>
        </head>
        <body>
            Redirecting...
        </body>
        </html>
        <?php
        exit();
    } catch (Exception $e) {
     
        $conn->rollback();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: faculty_frame.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Error: Could not find faculty information.";
    header("Location: faculty_frame.php");
    exit();
}

$conn->close();
?> 