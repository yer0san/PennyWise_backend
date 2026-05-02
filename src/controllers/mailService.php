<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);

    $mailHost = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
    $mailPort = getenv('MAIL_PORT') ?: 587;
    $mailUsername = getenv('MAIL_USERNAME') ?: 'your_email@gmail.com';
    $mailPassword = getenv('MAIL_PASSWORD') ?: 'your_app_password';
    $mailEncryption = getenv('MAIL_ENCRYPTION') ?: 'tls';
    $mailFrom = getenv('MAIL_FROM') ?: $mailUsername;
    $mailFromName = getenv('MAIL_FROM_NAME') ?: 'PennyWise';

    try {
        $mail->isSMTP();
        $mail->Host = $mailHost;
        $mail->SMTPAuth = true;
        $mail->Username = $mailUsername;
        $mail->Password = $mailPassword;
        $mail->SMTPSecure = $mailEncryption;
        $mail->Port = $mailPort;

        $mail->setFrom($mailFrom, $mailFromName);
        $mail->addAddress($email);

        $link = "http://localhost:8000/verify?token=$token";

        $mail->isHTML(true);
        $mail->Subject = 'Verify your email';
        $mail->Body = "Click this link to verify: <a href='$link'>$link</a>";

        $mail->send();
    } catch (Exception $e) {
        $debug = isset($smtpDebug) ? "\nSMTP Debug:\n" . $smtpDebug : '';
        throw new Exception("Email could not be sent: " . $mail->ErrorInfo . $debug);
    }
}
?>