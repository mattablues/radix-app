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
        $body = $event->password
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
                    'body' => "Du måste aktivera ditt konto, klicka på följande aktiveringslänk$body",
                    'url' => $event->activationLink,
                ],
                'reply_to' => getenv('MAIL_EMAIL'),
            ]
        );
    }
}