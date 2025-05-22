<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . "/../../vendor/autoload.php";

function getMailer() {
    $mail = new PHPMailer(true);

    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;

    $mail->isSMTP();
    $mail->SMTPAuth = true;

    $mail->Host = "smtp.gmail.com";
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->Username = "coursedock@gmail.com";
    $mail->Password = "gnsjvuuhcgknnbxi";

    $mail->isHtml(true);

    return $mail;
}

// For backward compatibility
$mail = getMailer();
return $mail;