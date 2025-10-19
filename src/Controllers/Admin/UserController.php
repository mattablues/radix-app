<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Events\UserRegisteredEvent;
use App\Models\Status;
use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Enums\Role;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Support\Token;
use Radix\Support\Validator;

class UserController extends AbstractController
{
    public function __construct(private readonly EventDispatcher $eventDispatcher)
    {
    }

    public function index(): Response
    {
        $page = $this->request->get['page'] ?? 1;

        $users = User::with('status')->paginate(10, (int)$page);

        return $this->view('admin.user.index', ['users' => $users]);
    }

    public function create(): Response
    {
        return $this->view('admin.user.create');
    }

    public function store(): Response
    {
        $this->before();

        $data = $this->request->post; // Hämta formulärdata

        // Validera data inklusive avatar
        $validator = new Validator($data, [
            'first_name' => 'required|min:2|max:15',
            'last_name' => 'required|min:2|max:15',
            'email' => 'required|email|unique:App\Models\User,email',
        ]);

        if (!$validator->validate()) {
            // Om validering misslyckas, lagra gamla indata och returnera vy med felmeddelanden
            $this->request->session()->set('old', $data);

            return $this->view('admin.user.create', [
                'errors' => $validator->errors(),
            ]);
        }

        // Rensa och filtrera data innan lagring
        $data = $this->request->filterFields($data);
        $this->request->session()->remove('old');

        // Skapa en ny användare
        $user = new User();
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        $password = generate_password();

        // Sätt lösenord unikt (handled)
        $user->password = $password;
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
            activationLink: $activationLink,
            password: $password,
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "Konto har skapats för {$data['first_name']} {$data['last_name']} och aktiveringslänk skickad."
        );

        return new RedirectResponse(route('admin.user.index'));
    }

    public function sendActivation(string $id): Response
    {
        $this->before();

        $user = User::find($id);

        if (!$user) {
            $this->request->session()->setFlashMessage(
                "Användare med email $user->email kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.index'));
        }

        $token = new Token();
        $tokenValue = $token->value();

        $status = $user->status()->first();

        $status->fill([
            'activation' => $token->hashHmac(),
            'status' => 'activate'
        ]);

        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            activationLink: $activationLink
        ));

        // Ställ in flash-meddelande och omdirigera
        $this->request->session()->setFlashMessage(
            "Aktiveringslänk skickad till $user->email."
        );

        $currentPage = $this->request->get['page'] ?? 1;

        return new RedirectResponse(route('admin.user.index') . '?page=' . $currentPage);
    }

    public function block(string $id): Response
    {
        $this->before();

        $user = User::find($id);

        if (!$user) {
            $this->request->session()->setFlashMessage(
                "Användare med id $user->id kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.index'));
        }

        $status = $user->status()->first();

        $status->fill([
            'status' => 'blocked',
            'active' => 'offline',
        ]);

        $status->save();

        $this->request->session()->setFlashMessage(
            "onto för $user->first_name $user->last_name har blockerats."
        );

        $currentPage = $this->request->get['page'] ?? 1;

        return new RedirectResponse(route('admin.user.index') . '?page=' . $currentPage);
    }

    public function closed(): Response
    {
        $this->before();

        $page = $this->request->get['page'] ?? 1;

        $users = User::with('status')->getOnlySoftDeleted()->paginate(10, (int)$page);

        return $this->view('admin.user.closed', ['users' => $users]);
    }

    public function restore(string $id): Response
    {
        $this->before();

        $user = User::find($id, true);

        if (!$user) {
            $currentPage = $this->request->get['page'] ?? 1;

            $this->request->session()->setFlashMessage(
                "Användare med id $user->id kunde inte hittas."
            );

            return new RedirectResponse(route('admin.user.closed'). '?page=' . $currentPage);
        }

        $user->restore();

        $token = new Token();
        $tokenValue = $token->value();

        $status = $user->status()->first();

        $status->fill([
            'activation' => $token->hashHmac(),
            'status' => 'activate',
        ]);

        $status->save();

        $activationLink = getenv('APP_URL') . route('auth.register.activate', ['token' => $tokenValue]);

        // Skicka e-postmeddelande
        $this->eventDispatcher->dispatch(new UserRegisteredEvent(
            email: $user->email,
            activationLink: $activationLink
        ));

        $this->request->session()->setFlashMessage(
            "Konto för $user->first_name $user->last_name har återställts, aktiveringslänk skickad."
        );

        return new RedirectResponse(route('admin.user.index'));
    }

    public function role(string $id): Response
    {
        $this->before();

        $roleInput = $this->request->post['role'] ?? null;
        $roleEnum = Role::tryFromName((string)$roleInput);

        if (!$roleEnum) {
            $this->request->session()->setFlashMessage("Något blev fel, prova igen", 'error');

            return new RedirectResponse(route('user.show', ['id' => $id]));
        }

        $user = User::find($id);

        if (!$user || $user->isAdmin()) {
            $this->request->session()->setFlashMessage('Du kan inte ändra en admin.', 'error');
            return new RedirectResponse(route('admin.user.index'));
        }

        if (!$user) {
            $this->request->session()->setFlashMessage('Användare saknas', 'error');
            return new RedirectResponse(route('user.show', ['id' => $user->id]));
        }

        $user->setRole($roleEnum); // använder din setter som validerar
        $user->save();

        $this->request->session()->setFlashMessage("$user->first_name $user->last_name har tilldelats behörighet $roleInput");

        return new RedirectResponse(route('user.show', ['id' => $user->id]));
    }
}