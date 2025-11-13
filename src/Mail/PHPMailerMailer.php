<?php

declare(strict_types=1);

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use Radix\Config\Config;
use Radix\Mailer\MailerInterface;
use Radix\Viewer\TemplateViewerInterface;

class PHPMailerMailer implements MailerInterface
{
    private PHPMailer $mailer;
    private TemplateViewerInterface $templateViewer;
    private Config $config;
    private string $fromEmail; // Standard From-email
    private string $fromName;  // Standard From-namn

    public function __construct(TemplateViewerInterface $templateViewer, Config $config)
    {
        $this->mailer = new PHPMailer(true);
        $this->templateViewer = $templateViewer;
        $this->config = $config;

        // Hämta inställningarna från konfigurationen
        $mailConfig = $this->config->get('email');

        $this->mailer->isSMTP();

        if($this->config->get('email.debug') === '1') {
            $this->mailer->SMTPDebug = 2;
        }

        $this->mailer->CharSet = $mailConfig['charset'];
        $this->mailer->Host = $mailConfig['host'];
        $this->mailer->SMTPAuth = $mailConfig['auth'];
        $this->mailer->Username = $mailConfig['username'];
        $this->mailer->Password = $mailConfig['password'];
        $this->mailer->SMTPSecure = $mailConfig['secure'];
        $this->mailer->Port = $mailConfig['port'];

        // Definiera standard `From`-adress och namn från inställningarna
        $this->fromEmail = $mailConfig['email']; // Ex. noreply@example.com
        $this->fromName = $mailConfig['from'];  // Ex. No Reply
    }

    /**
     * @param array<string, mixed> $options
     */
    public function send(string $to, string $subject, string $body, array $options = []): bool
    {
        try {
            // Sätt `From` från standardvärden om inget skickas
            $fromEmail = $this->fromEmail;
            $fromName = $this->fromName;

            // Kontrollera om `From` skickas med som alternativ och uppdatera
            if (!empty($options['from']) && filter_var($options['from'], FILTER_VALIDATE_EMAIL)) {
                $fromEmail = $options['from'];
            }
            if (!empty($options['from_name'])) {
                $fromName = $options['from_name'];
            }

            // Validera `From`-adressen
            if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException("Invalid From email address: $fromEmail");
            }

            // Sätt avsändare och mottagare
            $this->mailer->setFrom($fromEmail, $fromName);
            $this->mailer->addAddress($to);

            // Lägg till Reply-To om det skickas
            if (!empty($options['reply_to']) && filter_var($options['reply_to'], FILTER_VALIDATE_EMAIL)) {
                $this->mailer->addReplyTo($options['reply_to']);
            }

            $this->mailer->isHTML($options['is_html'] ?? true);
            $this->mailer->Subject = $subject;

            // Rendera template om det specificeras
            if (!empty($options['template'])) {
                $body = $this->templateViewer->render($options['template'], $options['data'] ?? []);
            }

            $this->mailer->Body = $body;

            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Mail could not be sent. Error: " . $e->getMessage());
            return false;
        }
    }
}

