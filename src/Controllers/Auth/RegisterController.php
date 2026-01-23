<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Auth\Concerns\AuthFormHelpers;
use App\Events\UserRegisteredEvent;
use App\Models\Status;
use App\Models\SystemEvent;
use App\Models\User;
use App\Requests\Auth\RegisterRequest;
use Radix\Controller\AbstractController;
use Radix\Enums\UserActivationContext;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Response;
use Radix\Support\Token;

class RegisterController extends AbstractController
{
    use AuthFormHelpers;

    public function __construct(private readonly EventDispatcher $eventDispatcher) {}

    public function index(): Response
    {
        return $this->view('auth.register.index', $this->beginAuthForm());
    }

    public function create(): Response
    {
        $this->before();

        $form = new RegisterRequest($this->request);

        if (!$form->validate()) {
            $this->request->session()->set('old', [
                'first_name' => $form->firstName(),
                'last_name'  => $form->lastName(),
                'email'      => $form->email(),
            ]);

            return $this->authFormErrorView('auth.register.index', [], $form->errors());
        }

        // Success: städa upp form-state
        $this->endAuthFormSuccess();

        $user = new User();
        $user->fill([
            'first_name' => $form->firstName(),
            'last_name'  => $form->lastName(),
            'email'      => $form->email(),
        ]);

        $user->password = $form->password();
        $user->save();

        $token = new Token();
        $tokenValue = $token->value();

        $status = new Status();
        $status->fill([
            'activation' => $token->hashHmac(),
        ]);

        $status->user_id = $user->id;
        $status->save();

        \App\Models\Token::createToken(
            (int) $user->id,
            'Default Personal Token',
            365
        );

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

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

        return $this->authRedirect('auth.login.index');
    }

    public function activate(string $token): Response
    {
        $token = new Token($token);
        $hashedToken = $token->hashHmac();

        $status = Status::where('activation', '=', $hashedToken)->first();

        if (!$status) {
            return $this->authRedirectWithError(
                'auth.login.index',
                'Aktiveringslänken är ogiltig eller så har du redan aktiverat ditt konto'
            );
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

        return $this->authRedirectWithFlash(
            'auth.login.index',
            'Ditt konto har aktiverats, du kan nu logga in.',
            'info'
        );
    }
}
