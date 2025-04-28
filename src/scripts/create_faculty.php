<!DOCTYPE html>
<html lang="en">
<head>
    <link href="../src/styles.css" rel="stylesheet" />
</head>
<body>
    <?php
    session_start();

    if (!isset($_SESSION['AccountID'])) {
        header("Location: ../index.php");
        exit();
    }

    $conn = new mysqli("localhost", "root", "", "CMS");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $facultyName = $_POST['faculty_name'];
    $facultyCode = $_POST['faculty_code'];
    $accountID = $_SESSION['AccountID'];


    $stmt = $conn->prepare("INSERT INTO faculties (Faculty, JoinCode) VALUES (?, ?)");
    $stmt->bind_param("ss", $facultyName, $facultyCode);

    if ($stmt->execute()) {
        $newFacultyID = $conn->insert_id;

 
        $updatePersonnel = "UPDATE personnel SET FacultyID = ?, Role = 'DN' WHERE AccountID = ?";
        $stmt2 = $conn->prepare($updatePersonnel);
        $stmt2->bind_param("ii", $newFacultyID, $accountID);
        $stmt2->execute();
        $stmt2->close();

   
        $_SESSION['Role'] = 'DN';

        $stmt->close();
        $conn->close();

        $dashboardURL = '';
        if ($_SESSION['Role'] === 'DN') {
            $dashboardURL = 'main/dashboard/dn-dash.php';
        } elseif ($_SESSION['Role'] === 'PH') {
            $dashboardURL = 'main/dashboard/ph-dash.php';
        }

        
        header("Location: ../../main/homepage.php?dashboard=$dashboardURL");
        exit();

    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    ?>
</body>
</html>