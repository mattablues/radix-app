<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Mailer\MailManager;
use Radix\Support\Validator;

class ContactController extends AbstractController
{
    public function __construct(private readonly MailManager $mailManager)
    {
    }

    public function index(): Response
    {
        $honeypotId = generate_honeypot_id();

        // Spara id:t i sessionen
        $this->request->session()->set('honeypot_id', $honeypotId);

        return $this->view('contact.index', [
            'honeypotId' => $honeypotId, // Skicka också till vyn
        ]);
    }

    public function create(): Response
    {
        $this->before();

        $data = $this->request->post;

        // Hämta det förväntade honeypot-id:t från sessionen
        $expectedHoneypotId = $this->request->session()->get('honeypot_id');

        if (!$expectedHoneypotId) {
            return new RedirectResponse(route('contact.index')); // Tillbaka till formuläret
        }

        // Validering
        $validate = new Validator($data, [
            'first_name' => 'required|min:2|max:15',
            'last_name' => 'required|min:2|max:15',
            'email' => 'required|email',
            'message' => 'required|min:10|max:500',
            $expectedHoneypotId => 'honeypot', // Dynamisk validering
        ]);

    if (!$validate->validate()) {
        // Generera ett nytt honeypot-id för nästa inlämning
        $this->request->session()->set('old', $data);

        $newHoneypotId = generate_honeypot_id();
        $this->request->session()->set('honeypot_id', $newHoneypotId);

        // Kontrollera fel och hantera potentiella honeypot-fält.
        $errors = $validate->errors();

        // Hämta det förväntade honeypot-id:t från sessionen.
        $newHoneypotId = $this->request->session()->get('honeypot_id');

        // Om honeypot-felet är närvarande, lägg till ett specifikt fel.
        $honeypotErrors = preg_grep('/^hp_/', array_keys($errors));

        if (!empty($honeypotErrors)) {
            // Om det finns minst en nyckel som börjar med 'hp_', hantera felet.
            $validate->addError('form-error', 'Det verkar som att du försöker skicka spam. Försök igen.');
        }


        return $this->view('contact.index', [
            'honeypotId' => $newHoneypotId, // Skicka det nya id:t till vyn
            'errors' => $validate->errors(),
        ]);
    }


        $sent = $this->mailManager->send(
            getenv('MAIL_EMAIL'), // Mottagarens e-postadress
            'Förfrågan', // E-postämne
            '', // Tom body eftersom template används
            [
                'template' => 'emails.contact', // Skicka template att rendera
                'data' => [
                    'heading' => 'Message from contact form',
                    'body'  => $data['message'],
                    'name' => $data['first_name'] . ' ' . $data['last_name'],
                ], // Template-data
                'from' => getenv('MAIL_EMAIL'), // Statisk avsändaradress (din server kräver detta)
                'from_name' => 'Support Team', // Text för avsändare
                'reply_to' => $data['email'], // Registrerarens e-postadress (gör det möjligt att svara till avsändaren)
            ]
        );

        // Om valideringen passerar, ta bort honeypot-id:t
        $this->request->session()->remove('honeypot_id');
        $this->request->session()->remove('old');

        $this->request->session()->setFlashMessage('Ditt meddelande har skickats!');
        return new RedirectResponse(route('home.index'));
    }
}