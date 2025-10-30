<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Events\UserRegisteredEvent;
use Radix\Mailer\MailManager;

readonly class SendActivationEmailListener
{
    public function __construct(private MailManager $mailManager) {}

    public function __invoke(UserRegisteredEvent $event): void
    {
        $introText = match ($event->context) {
            'user' => 'Tack för att du registrerat dig. Klicka på knappen nedan för att aktivera ditt konto.',
            'admin' => 'Ett konto har skapats åt dig. Klicka på knappen nedan för att aktivera ditt konto.',
            'resend' => 'Här kommer en ny aktiveringslänk till ditt konto.',
            default => 'Aktivera ditt konto via länken nedan.',
        };

        $bodySuffix = $event->password
            ? ", sen kan du logga in med din e-postadress: $event->email och lösenord: $event->password"
            : '.';

        // Skicka aktiverings mail
        $this->mailManager->send(
            $event->email,
            'Aktivera ditt konto',
            '',
            [
                'template' => 'emails.activate',
                'data' => [
                    'title' => 'Välkommen',
                    'firstName' => $event->firstName,
                    'lastName' => $event->lastName,
                    'introText' => $introText,
                    'body' => "Du måste aktivera ditt konto, klicka på följande aktiveringslänk$bodySuffix",
                    'url' => $event->activationLink,
                ],
                'reply_to' => getenv('MAIL_EMAIL'),
            ]
        );
    }
}