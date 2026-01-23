<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Concerns\FormHelpers;
use App\Events\UserRegisteredEvent;
use App\Models\Status;
use App\Models\SystemEvent;
use App\Models\User;
use App\Requests\Admin\CreateUserRequest;
use Radix\Controller\AbstractController;
use Radix\Enums\Role;
use Radix\Enums\UserActivationContext;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Session\Session;
use Radix\Support\Token;
use RuntimeException;

class UserController extends AbstractController
{
    use FormHelpers;

    public function __construct(
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $page = (int) $rawPage;

        $users = User::with('status')->paginate(10, $page);

        return $this->view('admin.user.index', ['users' => $users]);
    }

    public function create(): Response
    {
        return $this->view('admin.user.create', $this->beginForm());
    }

    public function store(): Response
    {
        $this->before();

        $form = new CreateUserRequest($this->request);

        if (!$form->validate()) {
            $this->request->session()->set('old', [
                'first_name' => $form->firstName(),
                'last_name'  => $form->lastName(),
                'email'      => $form->email(),
            ]);

            return $this->formErrorView('admin.user.create', [], $form->errors());
        }

        $user = new User();
        $user->fill([
            'first_name' => $form->firstName(),
            'last_name'  => $form->lastName(),
            'email'      => $form->email(),
        ]);

        $password = generate_password();

        $user->password = $password;
        $user->save();

        \App\Models\Token::createToken(
            (int) $user->id,
            'Admin Created Token',
            365
        );

        $token = new Token();
        $tokenValue = $token->value();

        $status = new Status();
        $status->fill([
            'activation' => $token->hashHmac(),
        ]);

        $status->user_id = $user->id;
        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $form->email(),
            firstName: $form->firstName(),
            lastName: $form->lastName(),
            activationLink: $activationLink,
            password: $password,
            context: UserActivationContext::Admin,
        ));

        $adminId = $this->request->session()->get(Session::AUTH_KEY);

        SystemEvent::log(
            message: "Admin skapade nytt konto för: {$form->firstName()} {$form->lastName()}",
            type: 'info',
            userId: is_numeric($adminId) ? (int) $adminId : null
        );

        return $this->formRedirectWithFlash(
            'admin.user.index',
            "Konto har skapats för {$form->firstName()} {$form->lastName()} och aktiveringslänk skickad.",
            'info'
        );
    }

    public function sendActivation(string $id): Response
    {
        $this->before();

        $user = User::find($id);

        if (!$user) {
            $this->request->session()->setFlashMessage(
                "Användare kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.index'));
        }

        $token = new Token();
        $tokenValue = $token->value();

        $user->loadMissing('status');

        /** @var Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill([
            'activation' => $token->hashHmac(),
            'status' => 'activate',
        ]);

        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            activationLink: $activationLink,
            context: UserActivationContext::Resend,
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "Aktiveringslänk skickad till $user->email."
        );

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $currentPage = (int) $rawPage;

        return new RedirectResponse(route('admin.user.index') . '?page=' . $currentPage);
    }

    public function block(string $id): Response
    {
        $this->before();

        $user = User::find($id);

        if (!$user) {
            $this->request->session()->setFlashMessage(
                "Användare kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.index'));
        }

        $user->loadMissing('status');

        /** @var Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill([
            'status' => 'blocked',
            'active' => 'offline',
        ]);

        $status->save();

        $this->request->session()->setFlashMessage(
            "onto för $user->first_name $user->last_name har blockerats."
        );

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $currentPage = (int) $rawPage;

        $adminId = $this->request->session()->get(Session::AUTH_KEY);

        SystemEvent::log(
            message: "Konto blockerades: {$user->email}",
            type: 'warning',
            userId: is_numeric($adminId) ? (int) $adminId : null
        );

        return new RedirectResponse(route('admin.user.index') . '?page=' . $currentPage);
    }

    public function closed(): Response
    {
        $this->before();

        $rawPage = $this->request->get['page'] ?? 1;

        if (!is_int($rawPage) && !is_string($rawPage)) {
            // Fallback om någon skickar något knasigt
            $rawPage = 1;
        }

        /** @var int|string $rawPage */
        $page = (int) $rawPage;

        $users = User::with('status')->getOnlySoftDeleted()->paginate(10, $page);

        return $this->view('admin.user.closed', ['users' => $users]);
    }

    public function restore(string $id): Response
    {
        $this->before();

        $user = User::find($id, true);

        if (!$user) {
            $rawPage = $this->request->get['page'] ?? 1;

            if (!is_int($rawPage) && !is_string($rawPage)) {
                // Fallback om någon skickar något knasigt
                $rawPage = 1;
            }

            /** @var int|string $rawPage */
            $currentPage = (int) $rawPage;

            $this->request->session()->setFlashMessage(
                "Användare kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.closed') . '?page=' . $currentPage);
        }

        $user->restore();

        $token = new Token();
        $tokenValue = $token->value();

        $user->loadMissing('status');

        /** @var Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill([
            'activation' => $token->hashHmac(),
            'status' => 'activate',
        ]);

        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            activationLink: $activationLink,
            context: UserActivationContext::Resend
        ));

        $this->request->session()->setFlashMessage(
            "Konto för $user->first_name $user->last_name har återställts, aktiveringslänk skickad."
        );

        return new RedirectResponse(route('admin.user.index'));
    }

    public function role(string $id): Response
    {
        $this->before();

        $rawRoleInput = $this->request->post['role'] ?? null;

        if (!is_string($rawRoleInput)) {
            $this->request->session()->setFlashMessage('Något blev fel, prova igen.', 'error');

            return new RedirectResponse(route('user.show', ['id' => $id]));
        }

        $roleInput = $rawRoleInput;
        $roleEnum = Role::tryFromName($roleInput);

        if ($roleEnum === null) {
            $this->request->session()->setFlashMessage('Ogiltig behörighetsnivå angiven.', 'error');

            return new RedirectResponse(route('user.show', ['id' => $id]));
        }

        $user = User::find($id);

        if ($user === null) {
            $this->request->session()->setFlashMessage('Användare saknas.', 'error');

            return new RedirectResponse(route('user.show', ['id' => $id]));
        }

        if ($user->isAdmin()) {
            $this->request->session()->setFlashMessage('Du kan inte ändra en admin.', 'error');

            return new RedirectResponse(route('admin.user.index'));
        }

        $user->setRole($roleEnum);
        $user->save();

        $roleName = $roleEnum->value;

        $this->request->session()->setFlashMessage(
            "$user->first_name $user->last_name har tilldelats behörighet {$roleName}"
        );

        return new RedirectResponse(route('user.show', ['id' => $user->id]));
    }
}
