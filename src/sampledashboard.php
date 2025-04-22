

<?php
session_start();


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userInput = $_POST['username'];
    $passInput = $_POST['password'];

    $conn = new mysqli("localhost", "root", "", "CMS");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

   
    $sql = "SELECT * FROM accounts WHERE Username = ? OR Email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $userInput, $userInput);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        if (password_verify($passInput, $row['Password'])) {
            $_SESSION['AccountID'] = $row['AccountID'];
            $_SESSION['Username'] = $row['Username'];
            header("Location: sampledashboard.php");
            exit();
        } else {
            echo "<script>alert('Invalid username or password.'); window.location.href='../index.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('Invalid username or password.'); window.location.href='../index.php';</script>";
        exit();//ibahin natin to ksi pangit ang alert
    }

    $conn->close();
}


if (!isset($_SESSION['Username'])) {
    header("Location: ../index.php");
    exit();
}



?>


<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sign In | CourseDock</title>
        <link href="styles.css?v=1.0" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

    </head>
    <body>

        <div class="header">
            <img src="../img/COURSEDOCK.svg" class="fade-in">
            <div class="cmstitle">Courseware Monitoring System</div>
        </div>
</body>
</html>
