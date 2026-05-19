<?php

declare(strict_types=1);

namespace App\Core;

use App\Config\Smtp;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email sending service via PHPMailer and SMTP.
 *
 * Supports both direct sending (explicit HTML body) and template-based sending
 * with {{key}} variable substitution.
 * SMTP configuration is loaded from config/Smtp.php.
 */
class Mailer
{
    private string $host;
    private int    $port;
    private bool   $useAuth;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private bool   $isDebug;

    public function __construct()
    {
        $config = Smtp::getConfig();

        $this->host      = $config['host'];
        $this->port      = $config['port'];
        $this->useAuth   = $config['useAuth'];
        $this->username  = $config['user'];
        $this->password  = $config['password'];
        $this->fromEmail = $config['fromEmail'];
        $this->fromName  = $config['fromName'];
        $this->isDebug   = $config['debug'];
    }

    /**
     * Configures and returns a ready-to-use PHPMailer instance.
     */
    private function configureMail(): PHPMailer
    {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host     = $this->host;
        $mail->Port     = $this->port;
        $mail->SMTPAuth = $this->useAuth;

        if ($this->useAuth) {
            $mail->Username = $this->username;
            $mail->Password = $this->password;
        }

        $mail->isHTML(true);
        $mail->ContentType = 'text/html';
        $mail->CharSet     = 'UTF-8';
        $mail->Encoding    = 'base64';

        $mail->setFrom($this->fromEmail, $this->fromName);

        $mail->Debugoutput = 'error_log';
        if ($this->isDebug) {
            $mail->SMTPDebug = 1;
        }

        return $mail;
    }

    /**
     * Loads an email template, substitutes variables and wraps it in the layout.
     *
     * Variables are substituted using the {{key}} syntax in the template file.
     *
     * @param string $templateName Template name without extension (e.g. 'welcome')
     * @param array  $data         Substitution variables (keys without braces)
     * @throws \UnexpectedValueException If the template file is not found
     */
    private function loadTemplate(string $templateName, array $data): string
    {
        $templatePath = dirname(__DIR__, 2) . '/templates/emails/' . $templateName . '.php';

        if (!file_exists($templatePath)) {
            throw new \UnexpectedValueException("Email template not found: $templateName");
        }

        $content = file_get_contents($templatePath);

        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $content);
        }

        $layoutPath = __DIR__ . '/../../templates/emails/layout.php';
        $layout     = file_get_contents($layoutPath);

        $html = str_replace('{{content}}', $content, $layout);
        $html = str_replace('{{subject}}', $data['subject'] ?? 'Vite & Gourmand', $html);

        return $html;
    }

    /**
     * Sends an email with explicit HTML and plain-text bodies.
     *
     * @param string $toMail  Recipient email address
     * @param string $toName  Recipient name
     * @param string $subject Email subject
     * @param string $body    HTML body
     * @param string $text    Plain-text alternative body
     */
    public function send(string $toMail, string $toName, string $subject, string $body, string $text): bool
    {
        try {
            $mail = $this->configureMail();
            $mail->addAddress($toMail, $toName);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $text;

            if (!$mail->send()) {
                return false;
            }
            return true;

        } catch (Exception) {
            return false;
        }
    }

    /**
     * Sends an email built from a template.
     *
     * The plain-text alternative body is generated automatically from the HTML.
     *
     * @param string $toMail   Recipient email address
     * @param string $toName   Recipient name
     * @param string $subject  Email subject
     * @param string $template Template name without extension (e.g. 'order_confirmation')
     * @param array  $data     Substitution variables
     */
    public function sendWithTemplate(string $toMail, string $toName, string $subject, string $template, array $data): bool
    {
        try {
            $data['subject'] = $subject;
            $htmlBody        = $this->loadTemplate($template, $data);
            $textBody        = html_entity_decode(strip_tags($htmlBody), ENT_QUOTES, 'UTF-8');

            $mail = $this->configureMail();
            $mail->addAddress($toMail, $toName);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            return $mail->send();

        } catch (\Exception $e) {
            Session::setFlash($e->getMessage(), 'error');
            return false;
        }
    }
}
