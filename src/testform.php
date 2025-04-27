<?php
$host = 'localhost';
$user = 'root';
$password = ''; 
$database = 'CMS';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$username = $_POST['username'];
$raw_password = $_POST['password'];
$hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);
$email = $_POST['email'];

$fname = $_POST['fname'];
$lname = $_POST['lname'];
$gender = $_POST['gender'];

$conn->begin_transaction();

try {
    $stmt1 = $conn->prepare("INSERT INTO accounts (Username, Password, Email) VALUES (?, ?, ?)");
    $stmt1->bind_param("sss", $username, $hashed_password, $email);
    $stmt1->execute();
    $accountId = $stmt1->insert_id;
    $stmt1->close();

    $role = "user"; // or inull nalang?
    $stmt2 = $conn->prepare("INSERT INTO personnel (FirstName, LastName, Gender, Role, AccountID) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("ssssi", $fname, $lname, $gender, $role, $accountId);
    $stmt2->execute();
    $stmt2->close();

    $conn->commit();


    header("Location: ../index.php?registersuccess=1");
    exit();

    
} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
