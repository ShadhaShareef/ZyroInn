<?php
namespace App\Services;

class MailService
{
    private string $fromEmail;
    private string $fromName;
    private bool $enabled;

    public function __construct()
    {
        $env = [];
        $envPath = __DIR__ . '/../../config/env.php';
        if (file_exists($envPath)) {
            $env = require $envPath;
        }
        $this->fromEmail = 'noreply@zyroinn.com';
        $this->fromName = $env['app_name'] ?? 'ZyroInn';
        $this->enabled = !($env['dev_mode'] ?? true);
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if (!$this->enabled) {
            Logger::info('MailService (dev mode — mail suppressed)', [
                'to' => $to,
                'subject' => $subject,
            ]);
            return true;
        }

        $boundary = bin2hex(random_bytes(16));

        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'From: ' . $this->fromName . ' <' . $this->fromEmail . '>',
            'Reply-To: ' . $this->fromEmail,
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $body = "--$boundary\r\n"
              . "Content-Type: text/plain; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
              . quoted_printable_encode($plainText) . "\r\n\r\n"
              . "--$boundary\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
              . quoted_printable_encode($htmlBody) . "\r\n\r\n"
              . "--$boundary--";

        $success = mail($to, $subject, $body, implode("\r\n", $headers));

        if (!$success) {
            Logger::error('MailService — mail() returned false', [
                'to' => $to,
                'subject' => $subject,
            ]);
        }

        return $success;
    }
}
