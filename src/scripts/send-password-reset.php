<?php

$email = $_POST["email"];

$token = bin2hex(random_bytes(16));

$token_hash = hash("sha256", $token);

$expiry = date("Y-m-d H:i:s", time( ) + 60 * 30); 

$mysqli = require __DIR__ . "/database.php";


// MYSQL STATE MENT TO SET HASH TOKEN FOR RECOVERY 
$sql = "UPDATE accounts
        SET reset_token_hash = ?,
            reset_token_expires_at = ?
        WHERE Email = ?";

$stmt = $mysqli->prepare($sql);

$stmt->bind_param("sss", $token_hash, $expiry, $email, $token);

$stmt->execute();

if ($stmt->affected_rows) {
    
}