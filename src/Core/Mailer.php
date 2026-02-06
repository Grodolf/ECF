<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Smtp;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private string $host;
    private int $port;
    private bool $useAuth;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private bool $isDebug;

    public function __construct()
    {
        $config = Smtp::getConfig();

        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->useAuth = $config['useAuth'];
        $this->username = $config['user'];
        $this->password = $config['password'];
        $this->fromEmail = $config['fromEmail'];
        $this->fromName = $config['fromName'];
        $this->isDebug = $config['debug'];
    }

    private function configureMail(): PHPMailer
    {
        $mail = new PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = $this->host;
        $mail->Port = $this->port;
        $mail->SMTPAuth = $this->useAuth;

        if ($this->useAuth) {
            $mail->Username = $this->username;
            $mail->Password = $this->password;
        }

        // Header and format
        $mail->isHTML(true);
        $mail->ContentType = 'text/html';
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Sender
        $mail->setFrom($this->fromEmail, $this->fromName);

        // Debug mode
        if ($this->isDebug) {
            $mail->SMTPDebug = 0;
        }

        return $mail;
    }

    private function loadTemplate(string $templateName, array $data): string
    {
        // Template loading
        $templatePath = dirname(__DIR__, 2) . '/templates/emails/' . $templateName . '.php';

        // DEBUG TEMPORAIRE
        error_log("Template path: " . $templatePath);
        error_log("Template exists: " . (file_exists($templatePath) ? 'OUI' : 'NON'));

        if (!file_exists($templatePath)) {
            throw new \UnexpectedValueException("Template email introuvable : $templateName");
        }

        $content = file_get_contents($templatePath);

        // Replace the variables {{variable}} with the data
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        // Layout loading
        $layoutPath = __DIR__ . '/../../templates/emails/layout.php';
        $layout = file_get_contents($layoutPath);

        // DEBUG TEMPORAIRE
        error_log("Content length: " . strlen($content));

        // Insert the content into the layout
        $html = str_replace('{{content}}', $content, $layout);
        $html = str_replace('{{subject}}', $data['subject'] ?? 'Vite & Gourmand', $html);

        return $html;
    }

    public function send(string $toMail, string $toName, string $subject, string $body, string $text): bool
    {
        try {
            $mail = $this->configureMail();

            // Recipient
            $mail->addAddress($toMail, $toName);

            // Content
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = $text;

            // Sending
            if (!$mail->send()) {
                return false;
            }
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public function sendWithTemplate(string $toMail, string $toName, string $subject, string $template, array $data): bool
    {
        try {
            // Create html body
            $data['subject'] = $subject;
            $htmlBody = $this->loadTemplate($template, $data);

            // Create text body
            $textBody = strip_tags($htmlBody);
            $textBody = html_entity_decode($textBody, ENT_QUOTES, 'UTF-8');

            // PHPMailer configuration
            $mail = $this->configureMail();
            $mail->addAddress($toMail, $toName);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;

            return $mail->send();

        } catch (Exception $e) {
            Session::setFlash('mail', $e->getMessage(), 'error');
            return false;
        }
    }

}
