<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Events\UserRegisteredEvent;
use App\Models\Status;
use App\Models\SystemEvent;
use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Enums\UserActivationContext;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;

class RegisterController extends AbstractController
{
    public function __construct(private readonly EventDispatcher $eventDispatcher) {}

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

        $form = new \App\Requests\Auth\RegisterRequest($this->request);

        if (!$form->validate()) {
            $this->request->session()->set('old', $this->request->post);

            // Generera ny honeypot vid fel
            $this->request->session()->set('honeypot_id', generate_honeypot_id());

            return $this->view('auth.register.index', [
                'honeypotId' => $this->request->session()->get('honeypot_id'),
                'errors' => $form->errors(),
            ]);
        }

        $this->request->session()->remove('honeypot_id');
        $this->request->session()->remove('old');

        // Skapa en ny användare
        $user = new User();
        $user->fill([
            'first_name' => $form->firstName(),
            'last_name'  => $form->lastName(),
            'email'      => $form->email(),
        ]);

        // Sätt lösenordet direkt från form-objektet
        $user->password = $form->password();
        $user->save();

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

        // Skapa en personlig API-token för användaren (giltig i t.ex. 365 dagar)
        \App\Models\Token::createToken(
            (int) $user->id,
            'Default Personal Token',
            365
        );

        // Skapa aktiverings-token för e-post
        $token = new Token();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $form->email(),
            firstName: $form->firstName(),
            lastName: $form->lastName(),
            activationLink: $activationLink,
            context: UserActivationContext::User,
        ));

        $firstName = $form->firstName();
        $lastName = $form->lastName();
        $this->request->session()->setFlashMessage(
            "$firstName $lastName ditt konto har registrerats. Kolla din email för aktiveringslänken."
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

        /** @var Status $status */
        $status->fill(['status' => 'activated', 'activation' => null]);
        $status->save();               // Spara modellen

        // Hämta användaren för att få namnet till loggen
        $user = $status->user()->first();

        if ($user instanceof User) {
            SystemEvent::log(
                message: "Konto aktiverat: {$user->first_name} {$user->last_name}",
                type: 'info',
                userId: $user->id
            );
        }

        // Ställ in flashmeddelande och omdirigera
        $this->request->session()->setFlashMessage('Ditt konto har aktiverats, du kan nu logga in.');
        return new RedirectResponse(route('auth.login.index'));
    }
}
