<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Events\UserRegisteredEvent;
use App\Models\Status;
use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class RegisterController extends AbstractController
{
    public function __construct(private readonly EventDispatcher $eventDispatcher)
    {
    }

    public function index(): Response
    {
        $honeypotId = generate_honeypot_id();

        // Spara id:t i sessionen
        $this->request->session()->set('honeypot_id', $honeypotId);

        return $this->view('auth.register.index', [
            'honeypotId' => $honeypotId, // Skicka också till vyn
        ]);
    }

    public function create(): Response
    {
        $this->before();
        $data = $this->request->post;

        $expectedHoneypotId = $this->request->session()->get('honeypot_id');

        if (!$expectedHoneypotId) {
            return new RedirectResponse(route('auth.register.index')); // Tillbaka till formuläret
        }

        // Validera inkommande data
        $validator = new Validator($data, [
            'first_name' => 'required|min:2|max:15',
            'last_name' => 'required|min:2|max:15',
            'email' => 'required|email|unique:App\Models\User,email',
            'password' => 'required|min:8|max:15',
            'password_confirmation' => 'required|confirmed:password',
            $expectedHoneypotId => 'honeypot', // Dynamisk validering
        ]);

        // Om validering misslyckas
        if (!$validator->validate()) {
            $this->request->session()->set('old', $data);

            $newHoneypotId = generate_honeypot_id();
            $this->request->session()->set('honeypot_id', $newHoneypotId);

            // Kontrollera fel och hantera potentiella honeypot-fält.
            $errors = $validator->errors();

            // Hämta det förväntade honeypot-id:t från sessionen.
            $newHoneypotId = $this->request->session()->get('honeypot_id');

            // Om honeypot-felet är närvarande, lägg till ett specifikt fel.
            $honeypotErrors = preg_grep('/^hp_/', array_keys($errors));

            if (!empty($honeypotErrors)) {
                // Om det finns minst en nyckel som börjar med 'hp_', hantera felet.
                $validator->addError('form-error', 'Det verkar som att du försöker skicka spam. Försök igen.');
            }

            return $this->view('auth.register.index', [
                'honeypotId' => $newHoneypotId, // Skicka det nya id:t till vyn
                'errors' => $validator->errors(),
            ]);
        }

        // Rensa och filtrera data innan lagring
        $data = $this->request->filterFields($data);
        $this->request->session()->remove('honeypot_id');
        $this->request->session()->remove('old');

        // Skapa en ny användare
        $user = new User();
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        // Sätt lösenord unikt (handled)
        $user->password = $data['password'];
        $user->save();

        // Skapa en API-token i tokens-tabellen
        \App\Models\Token::createToken((int)$user->id, 'API Token for user registration');

        // Skapa token
        $token = new Token();
        $tokenValue = $token->value();

        // Skapa och fyll statusobjekt
        $status = new Status();
        $status->fill([
            'activation' => $token->hashHmac(),
        ]);

        // Explicit sätt guardat fält
        $status->user_id = $user->id;
        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $data['email'],
            activationLink: $activationLink
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "{$data['first_name']} {$data['last_name']} ditt konto har registrerats. Kolla din email för aktiveringslänken."
        );

        return new RedirectResponse(route('auth.login.index'));
    }

    public function activate(string $token): Response
    {
        $token = new Token($token);
        $hashedToken = $token->hashHmac();

        // Hämta statusposten som matchar
        $status = Status::where('activation', '=', $hashedToken)->first();

        if (!$status) {
            // Posten hittades inte, hantera felet
            $this->request->session()->setFlashMessage('Aktiveringslänken är ogiltig eller så har du redan aktiverat ditt konto', 'error');
            return new RedirectResponse(route('auth.login.index'));
        }

        $status->fill(['status' => 'activated', 'activation' => null]);
        $status->save();               // Spara modellen

        // Ställ in flashmeddelande och omdirigera
        $this->request->session()->setFlashMessage('Ditt konto har aktiverats, du kan nu logga in.');
        return new RedirectResponse(route('auth.login.index'));
    }
}