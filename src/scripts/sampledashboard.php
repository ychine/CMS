<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userInput = $_POST['username'];
    $passInput = $_POST['password'];

    $conn = new mysqli("localhost", "root", "", "CMS");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT a.*, p.Gender, p.LastName 
            FROM accounts a
            LEFT JOIN personnel p ON a.AccountID = p.AccountID
            WHERE a.Username = ? OR a.Email = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $userInput, $userInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($passInput, $row['Password'])) {
            $_SESSION['AccountID'] = $row['AccountID'];
            $_SESSION['Username'] = $row['Username'];
            $_SESSION['Role'] = $row['Role'];

           
            $gender = $row['Gender'];
            if ($gender == "Male") {
                $_SESSION['Salutation'] = "Mr.";
            } elseif ($gender == "Female") {
                $_SESSION['Salutation'] = "Ms.";
            } else {
                $_SESSION['Salutation'] = ""; 
            }

            $_SESSION['LastName'] = $row['LastName'];

            $conn->close();

        
            if (is_null($row['Role'])) {
                $_SESSION['ShowFacultyPopup'] = true;
                header("Location: ../../main/homepage.php");
            } elseif ($row['Role'] == "ProgHead") {
                header("Location: ../../dashboard/ph-dash.php");
            } else {
                header("Location: ../sampledashboard.php");
            }
            exit();

        } else {
            $conn->close();
            header("Location: ../../index.php?error=invalid");
            exit();
        }
    } else {
        $conn->close();
        header("Location: ../../index.php?error=invalid");
        exit();
    }
}

if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}
?>