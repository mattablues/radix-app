<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\Auth\Concerns\AuthFormHelpers;
use App\Models\Status;
use App\Models\User;
use App\Requests\Auth\PasswordResetRequest;
use Radix\Controller\AbstractController;
use Radix\Http\Response;
use Radix\Support\Token;

class PasswordResetController extends AbstractController
{
    use AuthFormHelpers;

    public function index(string $token): Response
    {
        $data = $this->beginAuthForm();

        $tokenObj = new Token($token);
        $hashedToken = $tokenObj->hashHmac();

        $status = Status::where('password_reset', '=', $hashedToken)->first();

        if (!$status) {
            return $this->authRedirectWithError(
                'auth.password-forgot.index',
                'Återställningslänken är inte giltig, begär en ny.'
            );
        }

        /** @var Status $status */
        if (strtotime((string) $status->reset_expires_at) < time()) {
            return $this->authRedirectWithError(
                'auth.password-forgot.index',
                'Återställningslänken är inte giltig, begär en ny.'
            );
        }

        return $this->view('auth.password-reset.index', [
            'token' => $tokenObj->value(),
            'honeypotId' => $data['honeypotId'],
        ]);
    }

    public function create(string $token): Response
    {
        $this->before();

        $form = new PasswordResetRequest($this->request);

        if (!$form->validate()) {
            // Spara INTE old här (innehåller lösenord / confirmations)
            $this->request->session()->remove('old');

            return $this->authFormErrorView('auth.password-reset.index', [
                'token' => $token,
            ], $form->errors());
        }

        $rawToken = $form->token();
        if ($rawToken === '') {
            return $this->authRedirectWithError(
                'auth.password-forgot.index',
                'Återställningslänken är inte giltig, begär en ny.'
            );
        }

        $tokenObj = new Token($rawToken);
        $hashedToken = $tokenObj->hashHmac();

        $status = Status::where('password_reset', '=', $hashedToken)->first();

        if (!$status) {
            return $this->authRedirectWithError(
                'auth.password-forgot.index',
                'Återställningslänken är inte giltig, begär en ny.'
            );
        }

        /** @var Status $status */
        if (strtotime((string) $status->reset_expires_at) < time()) {
            return $this->authRedirectWithError(
                'auth.password-forgot.index',
                'Återställningslänken är inte giltig, begär en ny.'
            );
        }

        $status->loadMissing('user');

        $user = $status->getRelation('user');

        if (!$user instanceof User) {
            return $this->authRedirectWithError(
                'auth.password-forgot.index',
                'Något gick fel. Försök igen senare.'
            );
        }

        $status->fill(['password_reset' => null, 'reset_expires_at' => null]);
        $status->save();

        $user->password = $form->password();
        $user->save();

        return $this->authRedirectWithFlash(
            'auth.login.index',
            'Ditt lösenord har återställts, du kan nu logga in.',
            'info'
        );
    }
}
