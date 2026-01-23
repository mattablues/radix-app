<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Auth\Concerns\AuthFormHelpers;
use App\Events\UserPasswordEvent;
use App\Models\Status;
use App\Models\SystemEvent;
use App\Models\User;
use App\Requests\Auth\PasswordForgotRequest;
use App\Services\AuthService;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Response;
use Radix\Support\Token;

class PasswordForgotController extends AbstractController
{
    use AuthFormHelpers;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
        private readonly AuthService $authService,
    ) {}

    public function index(): Response
    {
        return $this->view('auth.password-forgot.index', $this->beginAuthForm());
    }

    public function create(): Response
    {
        $this->before();

        $form = new PasswordForgotRequest($this->request);

        if (!$form->validate()) {
            $this->request->session()->set('old', [
                'email' => $form->email(),
            ]);

            return $this->authFormErrorView('auth.password-forgot.index', [], $form->errors());
        }

        $email = $form->email();
        $ip = $this->request->ip();

        $this->request->session()->set('old', ['email' => $email]);


        if ($this->authService->isBlocked($email)) {
            $blockedUntil = $this->authService->getBlockedUntil($email);
            $remainingTime = $blockedUntil !== null ? $blockedUntil - time() : 0;

            $minutes = $remainingTime > 0 ? intdiv($remainingTime, 60) : 0;
            $seconds = $remainingTime > 0 ? $remainingTime % 60 : 0;

            $errorMessage = "För många försök. Försök igen om $minutes minuter och $seconds sekunder.";

            SystemEvent::log(
                message: "Säkerhet: IP {$ip} blockerad pga för många lösenordsförsök",
                type: 'error'
            );

            return $this->authFormErrorView('auth.password-forgot.index', [], [
                'form-error' => [$errorMessage],
            ]);
        }

        if ($this->authService->isIpBlocked($ip)) {
            $blockedUntil = $this->authService->getBlockedIpUntil($ip);
            $remainingTime = $blockedUntil !== null ? $blockedUntil - time() : 0;

            $minutes = $remainingTime > 0 ? intdiv($remainingTime, 60) : 0;
            $seconds = $remainingTime > 0 ? $remainingTime % 60 : 0;

            $errorMessage = "För många förfrågningar från denna IP. Försök igen om $minutes minuter och $seconds sekunder.";

            return $this->authFormErrorView('auth.password-forgot.index', [], [
                'form-error' => [$errorMessage],
            ]);
        }

        $user = User::where('email', '=', $email)->first();

        $status = null;

        if ($user instanceof User) {
            $user->loadMissing('status');

            /** @var Status|null $status */
            $status = $user->getRelation('status');

            if ($status instanceof Status && $status->getAttribute('status') === 'activated') {
                $token = new Token();
                $tokenValue = $token->value();
                $tokenHash = $token->hashHmac();
                $resetExpiresAt = time() + 60 * 60 * 2;

                $status->fill([
                    'password_reset' => $tokenHash,
                    'reset_expires_at' => date('Y-m-d H:i:s', $resetExpiresAt),
                ]);

                $status->save();

                $resetLink = getenv('APP_URL') . route('auth.password-reset.index', ['token' => $tokenValue]);

                $this->eventDispatcher->dispatch(new UserPasswordEvent(
                    firstName: $user->first_name,
                    lastName: $user->last_name,
                    email: $email,
                    resetLink: $resetLink
                ));

                $this->authService->clearFailedAttempts($email);
                $this->authService->clearFailedIpAttempt($ip);
            }
        }

        if (!$user instanceof User || !$status instanceof Status || $status->getAttribute('status') !== 'activated') {
            $this->authService->trackFailedAttempt($email);
            $this->authService->trackFailedIpAttempt($ip);
        }

        // Success: städa upp form-state
        $this->endAuthFormSuccess();

        $this->request->session()->setFlashMessage(
            'Ett e-postmeddelande med återställningsinformation har skickats till din e-postadress.'
        );

        return $this->authRedirect('auth.login.index');
    }
}
