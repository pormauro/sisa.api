<?php
namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        // Configuración SMTP
        $this->mailer->isSMTP();
        $this->mailer->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.example.com';
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $_ENV['SMTP_USER'] ?? 'user@example.com';
        $this->mailer->Password   = $_ENV['SMTP_PASS'] ?? 'password';
        $this->mailer->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls';
        $this->mailer->Port       = $_ENV['SMTP_PORT'] ?? 587;
        $this->mailer->CharSet    = 'UTF-8';
    }

    public function sendEmail($to, $subject, $body) {
        try {
            $this->mailer->setFrom($_ENV['SMTP_FROM'] ?? 'noreply@example.com', $_ENV['SMTP_FROM_NAME'] ?? 'Sistema');
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            $this->mailer->isHTML(true);
            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            return 'Mailer Error: ' . $this->mailer->ErrorInfo;
        }
    }
}
