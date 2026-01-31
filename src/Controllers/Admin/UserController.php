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
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        $rawQ = $this->request->get['q'] ?? '';
        $q = is_string($rawQ) ? trim($rawQ) : '';

        if ($q !== '') {
            $results = User::with('status')
                ->search($q, ['first_name', 'last_name', 'email'], 10, $page);

            $users = [
                'data' => $results['data'] ?? [],
                'pagination' => $results['search'] ?? [
                    'term' => $q,
                    'total' => 0,
                    'per_page' => 10,
                    'current_page' => $page,
                    'last_page' => 0,
                    'first_page' => 1,
                ],
            ];
        } else {
            $users = User::with('status')
                ->paginate(10, $page);
        }

        return $this->view('admin.user.index', [
            'users' => $users,
            'q' => $q,
        ]);
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

        //        \App\Models\Token::createToken(
        //            (int) $user->id,
        //            'Admin Created Token',
        //            365
        //        );

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
            return $this->formRedirectWithFlashAndQuery(
                'admin.user.index',
                'Användare kunde inte hittas.',
                'error',
                [],
                $this->currentListQuery()
            );
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

        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            activationLink: $activationLink,
            context: UserActivationContext::Resend,
        ));

        return $this->formRedirectWithFlashAndQuery(
            'admin.user.index',
            "Aktiveringslänk skickad till {$user->email}.",
            'info',
            [],
            $this->currentListQuery()
        );
    }

    public function block(string $id): Response
    {
        $this->before();

        $user = User::find($id);

        if (!$user) {
            return $this->formRedirectWithFlashAndQuery(
                'admin.user.index',
                'Användare kunde inte hittas.',
                'error',
                [],
                $this->currentListQuery()
            );
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

        $adminId = $this->request->session()->get(Session::AUTH_KEY);

        SystemEvent::log(
            message: "Konto blockerades: {$user->email}",
            type: 'warning',
            userId: is_numeric($adminId) ? (int) $adminId : null
        );

        return $this->formRedirectWithFlashAndQuery(
            'admin.user.index',
            "Konto för {$user->first_name} {$user->last_name} har blockerats.",
            'info',
            [],
            $this->currentListQuery()
        );
    }

    public function closed(): Response
    {
        $this->before();

        $rawPage = $this->request->get['page'] ?? 1;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        $rawQ = $this->request->get['q'] ?? '';
        $q = is_string($rawQ) ? trim($rawQ) : '';

        if ($q !== '') {
            $results = User::with('status')
                ->getOnlySoftDeleted()
                ->orderBy('deleted_at', 'DESC')
                ->search($q, ['first_name', 'last_name', 'email'], 10, $page);

            $users = [
                'data' => $results['data'] ?? [],
                'pagination' => $results['search'] ?? [
                    'term' => $q,
                    'total' => 0,
                    'per_page' => 10,
                    'current_page' => $page,
                    'last_page' => 0,
                    'first_page' => 1,
                ],
            ];
        } else {
            $users = User::with('status')
                ->getOnlySoftDeleted()
                ->orderBy('deleted_at', 'DESC')
                ->paginate(10, $page);
        }

        return $this->view('admin.user.closed', [
            'users' => $users,
            'q' => $q,
        ]);
    }

    public function restore(string $id): Response
    {
        $this->before();

        $user = User::find($id, true);

        if (!$user) {
            return $this->formRedirectWithFlashAndQuery(
                'admin.user.closed',
                'Användare kunde inte hittas.',
                'error',
                [],
                $this->currentListQuery()
            );
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

        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            firstName: $user->first_name,
            lastName: $user->last_name,
            activationLink: $activationLink,
            context: UserActivationContext::Resend
        ));

        return $this->formRedirectWithFlashAndQuery(
            'admin.user.closed',
            "Konto för $user->first_name $user->last_name har återställts, aktiveringslänk skickad.",
            'info',
            [],
            $this->currentListQuery()
        );
    }

    public function role(string $id): Response
    {
        $this->before();

        $rawRoleInput = $this->request->post['role'] ?? null;

        if (!is_string($rawRoleInput)) {
            return $this->formRedirectWithFlash(
                'user.show',
                'Något blev fel, prova igen.',
                'error',
                ['id' => $id]
            );
        }

        $roleEnum = Role::tryFromName($rawRoleInput);

        if ($roleEnum === null) {
            return $this->formRedirectWithFlash(
                'user.show',
                'Ogiltig behörighetsnivå angiven.',
                'error',
                ['id' => $id]
            );
        }

        $user = User::find($id);

        if ($user === null) {
            return $this->formRedirectWithFlash(
                'user.show',
                'Användare saknas.',
                'error',
                ['id' => $id]
            );
        }

        if ($user->isAdmin()) {
            return $this->formRedirectWithFlash(
                'user.show',
                'Du kan inte ändra en admin.',
                'error',
                ['id' => (string) $user->id]
            );
        }

        $user->setRole($roleEnum);
        $user->save();

        return $this->formRedirectWithFlash(
            'user.show',
            "{$user->first_name} {$user->last_name} har tilldelats behörighet {$roleEnum->value}",
            'info',
            ['id' => (string) $user->id]
        );
    }
}
