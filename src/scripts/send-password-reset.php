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

$stmt->bind_param("sss", $token_hash, $expiry, $email);

$stmt->execute();

if ($mysqli->affected_rows) {
    
    $mail = require __DIR__ . "/mailer.php";

    $mail->setFrom("gradingsystemplp@gmail.com");
    $mail->addAddress($email);
    $mail->Subject = "Password Reset";
    $mail->Body = <<<END

    <p>Please click the link below to reset your password:</p>

    <p><a href="http://localhost/coursewarems/src/scripts/reset-password.php?token=$token">Reset Password</a></p>


    END;

    try {

        $mail->send();

    }catch (Exception $e) {
            echo "Message could not be sent. Mailer error: ($mail->ErrorInfo)";
        }
        
    }


echo "Message sent, please check your inbox."; 