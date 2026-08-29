<?php

if (!class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    require_once ROOT_PATH . '/lib/phpmailer/src/Exception.php';
    require_once ROOT_PATH . '/lib/phpmailer/src/PHPMailer.php';
    require_once ROOT_PATH . '/lib/phpmailer/src/SMTP.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer
{
    public static function send(string $to, string $subject, string $message, ?string $replyTo = null): bool
    {
        return self::sendWithAttachment($to, $subject, $message, $replyTo);
    }

    public static function sendWithAttachment(
        string $to,
        string $subject,
        string $message,
        ?string $replyTo = null,
        ?string $attachmentContent = null,
        ?string $attachmentName = null,
        string $attachmentMime = 'application/pdf'
    ): bool {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $smtpHost = trim((string) ($_ENV['SMTP_HOST'] ?? ''));

        return $smtpHost !== ''
            ? self::sendViaSmtp($smtpHost, $to, $subject, $message, $replyTo, $attachmentContent, $attachmentName, $attachmentMime)
            : self::sendViaMailFunction($to, $subject, $message, $replyTo, $attachmentContent, $attachmentName, $attachmentMime);
    }

    private static function sendViaSmtp(
        string $smtpHost,
        string $to,
        string $subject,
        string $message,
        ?string $replyTo,
        ?string $attachmentContent,
        ?string $attachmentName,
        string $attachmentMime
    ): bool {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->Port = (int) ($_ENV['SMTP_PORT'] ?? 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string) ($_ENV['SMTP_USERNAME'] ?? '');
            $mail->Password = (string) ($_ENV['SMTP_PASSWORD'] ?? '');
            $mail->SMTPSecure = (string) ($_ENV['SMTP_SECURE'] ?? PHPMailer::ENCRYPTION_STARTTLS);

            $fromEmail = (string) ($_ENV['SMTP_FROM_EMAIL'] ?? company_email());
            $fromName = (string) ($_ENV['SMTP_FROM_NAME'] ?? company_name());
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyTo);
            }

            if ($attachmentContent !== null && $attachmentName !== null) {
                $mail->addStringAttachment($attachmentContent, $attachmentName, PHPMailer::ENCODING_BASE64, $attachmentMime);
            }

            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->isHTML(false);

            return $mail->send();
        } catch (PHPMailerException) {
            return false;
        }
    }

    private static function sendViaMailFunction(
        string $to,
        string $subject,
        string $message,
        ?string $replyTo,
        ?string $attachmentContent,
        ?string $attachmentName,
        string $attachmentMime
    ): bool {
        if ($attachmentContent === null || $attachmentName === null) {
            $headers = ['From: ' . company_name() . ' <' . company_email() . '>'];

            if ($replyTo) {
                $headers[] = 'Reply-To: ' . $replyTo;
            }

            return mail($to, $subject, $message, implode("\r\n", $headers));
        }

        $boundary = '=_Mail_' . bin2hex(random_bytes(12));
        $headers = [
            'From: ' . company_name() . ' <' . company_email() . '>',
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
        ];

        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $body .= $message . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$attachmentMime}; name=\"{$attachmentName}\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"{$attachmentName}\"\r\n\r\n";
        $body .= chunk_split(base64_encode($attachmentContent)) . "\r\n";
        $body .= "--{$boundary}--";

        return mail($to, $subject, $body, implode("\r\n", $headers));
    }
}
