<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\Status;
use Radix\Controller\AbstractController;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class PasswordResetController extends AbstractController
{
    public function index(string $token): Response
    {
        $token =  new Token($token);
        $hashedToken = $token->hashHmac();

        $status = Status::where('password_reset', '=', $hashedToken)->first();


        if (!$status || strtotime($status->reset_expires_at) < time()) {
            $this->request->session()->setFlashMessage('Återställningslänken är inte giltig, begär en ny.','enlightenment');

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        return $this->view('auth.password-reset.index', [
            'token' => $token->value()
        ]);
    }

    public function create(string $token): Response
    {
        $this->before();
        $data = $this->request->post;

        $validator = new Validator($data, [
            'password' => 'required|min:8|max:15',
            'password_confirmation' => 'required|confirmed:password',
        ]);

        if (!$validator->validate()) {
            return $this->view('auth.password-reset.index', [
                'token' => $token,
                'errors' => $validator->errors(),
            ]);
        }

        $token = new Token($data['token']);
        $hashedToken = $token->hashHmac();

        $status = Status::where('password_reset', '=', $hashedToken)->first();

        if (!$status || strtotime($status->reset_expires_at) < time()) {
            $this->request->session()->setFlashMessage('Återställningslänken är inte giltig, begär en ny.','enlightenment');

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        $user = $status->user()->first();

        if (!$user) {
            $this->request->session()->setFlashMessage('Något gick fel. Försök igen senare.','error');

            return new RedirectResponse(route('auth.password-forgot.index'));
        }

        $status->fill(['password_reset' => null, 'reset_expires_at' => null]);
        $status->save();

        $user->password = $data['password'];
        $user->save();

        $this->request->session()->setFlashMessage('Ditt lösenord har återställts, du kan nu logga in.', 'enlightenment');

        return new RedirectResponse(route('auth.login.index'));
    }
}