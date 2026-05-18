<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\FormHelpers;
use App\Events\ContactFormEvent;
use App\Requests\ContactRequest;
use Radix\Controller\AbstractController;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Response;
use Throwable;

class ContactController extends AbstractController
{
    use FormHelpers;

    public function __construct(private readonly EventDispatcher $eventDispatcher) {}

    public function index(): Response
    {
        return $this->view('contact.index', $this->beginForm());
    }

    public function create(): Response
    {
        $this->before();

        $form = new ContactRequest($this->request);

        if (!$form->validate()) {
            $this->request->session()->set('old', [
                'first_name' => $form->firstName(),
                'last_name'  => $form->lastName(),
                'email'      => $form->email(),
                'message'    => $form->message(),
            ]);

            return $this->formErrorView('contact.index', [], $form->errors());
        }

        if ($this->isBlockedEmail($form->email())) {
            return $this->formRedirectWithFlash(
                'home.index',
                'Ditt meddelande har skickats!',
                'info'
            );
        }

        $this->eventDispatcher->dispatch(new ContactFormEvent(
            email: $form->email(),
            message: $form->message(),
            firstName: human_name($form->firstName()),
            lastName: human_name($form->lastName()),
        ));

        return $this->formRedirectWithFlash(
            'home.index',
            'Ditt meddelande har skickats!',
            'info'
        );
    }

    private function isBlockedEmail(string $email): bool
    {
        try {
            $blockedEmailClass = 'App\\Models\\BlockedEmail';

            if (!class_exists($blockedEmailClass)) {
                return false;
            }

            $isBlocked = [$blockedEmailClass, 'isBlocked'];

            if (!is_callable($isBlocked)) {
                return false;
            }

            return $isBlocked($email) === true;
        } catch (Throwable) {
            return false;
        }
    }
}
