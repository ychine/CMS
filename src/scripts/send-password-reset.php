<?php

$email = $_POST["email"];
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 60 * 30); 

$mysqli = require __DIR__ . "/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if email exists in database (USING mysqli NOT pdo)
    $stmt = $mysqli->prepare("SELECT * FROM accounts WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        // Email not found
        header('Location: ../resetpass.php?error=emailnotfound');
        exit();
    } else {
        // If email exists, now update the reset token
        $sql = "UPDATE accounts
                SET reset_token_hash = ?,
                    reset_token_expires_at = ?
                WHERE email = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sss", $token_hash, $expiry, $email);
        $stmt->execute();

        if ($stmt->affected_rows) {
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
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer error: {$mail->ErrorInfo}";
                exit();
            }

            header('Location: ../resetpass.php?success=emailsent');
            exit();
        } else {
            echo "Failed to set reset token.";
        }
    }
}
?>
