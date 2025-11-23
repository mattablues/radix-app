<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\ContactFormEvent;
use Radix\Mailer\MailManager;
use RuntimeException;

readonly class SendContactEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(ContactFormEvent $event): void
    {
        $to = getenv('MAIL_EMAIL');

        if ($to === false || $to === '') {
            throw new RuntimeException('MAIL_EMAIL env-variabeln är inte satt.');
        }

        $senderName = trim($event->firstName . ' ' . $event->lastName) ?: 'Kontaktformulär';

        $this->mailManager->send(
            $to, // Mottagarens e-postadress
            'Förfrågan', // E-postämne
            '', // Tom body eftersom template används
            [
                'template' => 'emails.contact', // Skicka template att rendera
                'data' => [
                    'heading' => 'Message from contact form',
                    'body'  => $event->message,
                    'name' => $senderName,
                    'email' => $event->email,
                ], // Template-data
                'from' => $to, // Behåll supportens adress för leverans
                'from_name' => $senderName, // Visa avsändarens namn i "From"
                'reply_to' => $event->email, // Så att "Svara" går till avsändaren
            ]
        );
    }
}
