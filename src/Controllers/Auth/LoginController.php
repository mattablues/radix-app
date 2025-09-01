<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Services\AuthService;
use Radix\Auth\Auth;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Validator;

class LoginController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly Auth $auth,
    ) {}

    public function index(): Response
    {
        return $this->view('auth.login.index');
    }

    public function create(): Response
    {
        $data = $this->request->post;

        // Validera inskickade data
        $validator = new Validator($data, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!$validator->validate()) {
            return $this->view('auth.login.index', [
                'errors' => $validator->errors(),
            ]);
        }

        $email = $data['email'];

        // Kontrollera om användaren är blockerad
        if ($this->authService->isBlocked($email)) {
            $blockedUntil = $this->authService->getBlockedUntil($email);
            $remainingTime = $blockedUntil - time();

            // Räkna ut minuter och sekunder kvar
            $minutes = intdiv($remainingTime, 60);
            $seconds = $remainingTime % 60;

            $errorMessage = "För många misslyckade försök. Försök igen om $minutes minuter och $seconds sekunder.";

            return $this->view('auth.login.index', [
                'errors' => [
                    'form-error' => [$errorMessage],
                ],
            ]);
        }

        $user = $this->authService->login($data);

        // Kontrollera statusfel eller misslyckad inloggning
        $statusError = $this->authService->getStatusError($user);

        if ($statusError || !$user) {
            return $this->view('auth.login.index', [
                'errors' => ['form-error' => [$statusError ?: 'Inloggning misslyckades.']],
            ]);
        }

        // Logga in användaren och markera som online
        $this->auth->login($user->id);
        $this->request->session()->setFlashMessage("Välkommen tillbaka, $user->first_name $user->last_name!");

        return new RedirectResponse(route('dashboard.index'));
    }
}