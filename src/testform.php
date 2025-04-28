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

$checkStmt = $conn->prepare("SELECT Username, Email FROM accounts WHERE Username = ? OR Email = ?");
$checkStmt->bind_param("ss", $username, $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->bind_result($existingUsername, $existingEmail);
    $checkStmt->fetch();

    if ($existingUsername === $username) {
        header("Location: register.php?error=Username already exists");
        exit();
    } elseif ($existingEmail === $email) {
        header("Location: register.php?error=Email already exists");
        exit();
    } else {
        header("Location: register.php?error=Username or Email already exists");
        exit();
    }
}
$checkStmt->close();

$conn->begin_transaction();
try {
    $stmt1 = $conn->prepare("INSERT INTO accounts (Username, Password, Email) VALUES (?, ?, ?)");
    $stmt1->bind_param("sss", $username, $hashed_password, $email);
    $stmt1->execute();
    $accountId = $stmt1->insert_id;
    $stmt1->close();

    $role = "user";
    $stmt2 = $conn->prepare("INSERT INTO personnel (FirstName, LastName, Gender, Role, AccountID) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("ssssi", $fname, $lname, $gender, $role, $accountId);
    $stmt2->execute();
    $stmt2->close();

    $conn->commit();

    header("Location: register.php?success=Registration successful");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    header("Location: register.php?error=Something went wrong");
    exit();
}

$conn->close();
?>
