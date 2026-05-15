<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/autoload.php';

function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);

    $mailHost       = getenv('MAIL_HOST')       ?: 'smtp.gmail.com';
    $mailPort       = getenv('MAIL_PORT')       ?: 465;
    $mailUsername   = getenv('MAIL_USERNAME')   ?: 'your_email@gmail.com';
    $mailPassword   = getenv('MAIL_PASSWORD')   ?: 'your_app_password';
    $mailEncryption = getenv('MAIL_ENCRYPTION') ?: 'ssl';
    $mailFrom       = getenv('MAIL_FROM')       ?: $mailUsername;
    $mailFromName   = getenv('MAIL_FROM_NAME')  ?: 'PennyWise';

    try {
        $mail->isSMTP();
        $mail->Host       = $mailHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUsername;
        $mail->Password   = $mailPassword;
        $mail->Port       = (int) $mailPort;

        // Use SMTPS (implicit SSL on 465) or STARTTLS (explicit TLS on 587)
        if ($mailEncryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($mailFrom, $mailFromName);
        $mail->addAddress($email);

        $appUrl = getenv('APP_URL') ?: 'http://localhost';
        $link   = "$appUrl/verify?token=$token";

        $mail->isHTML(true);
        $mail->Subject = 'Verify your PennyWise account';
        $mail->Body    = "
            <p>Thanks for signing up for PennyWise!</p>
            <p>Click the link below to verify your email address:</p>
            <p><a href='$link'>$link</a></p>
            <p>If you didn't create an account, you can ignore this email.</p>
        ";
        $mail->AltBody = "Verify your email: $link";

        $mail->send();

    } catch (Exception $e) {
        throw new Exception("Email could not be sent: " . $mail->ErrorInfo);
    }
}