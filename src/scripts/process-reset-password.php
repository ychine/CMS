<?php

$token = $_POST["token"];

$token_hash = hash("sha256", $token);

$mysqli = require __DIR__ ."/database.php";

$sql = "SELECT * FROM accounts
        WHERE reset_token_hash = ?";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("s", $token_hash);

$stmt->execute();

$result = $stmt->get_result();

$user = $result->fetch_assoc();

if ($user === null) {
    die("token not found");
}

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    die("token has expired");
}

if (empty($_POST["password"]) || empty($_POST["password_confirm"])) {
    header('Location: reset-password.php?error=emptyfields&token=' . urlencode($token));
    exit();
}

if ($_POST["password"] !== $_POST["password_confirm"]) {
    header('Location: reset-password.php?error=passwordmismatch&token=' . urlencode($token));
    exit();
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$sql = "UPDATE accounts
        SET Password = ?,
        reset_token_hash = NULL,
        reset_token_expires_at = NULL
        WHERE AccountID =?";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("ss", $password_hash, $user["AccountID"]);

$stmt->execute();

header('Location: ../../index.php?success=passwordupdated');
exit();
?>
