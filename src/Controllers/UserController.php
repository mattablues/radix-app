<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\FormHelpers;
use App\Models\Status;
use App\Models\User;
use App\Requests\User\ChangePasswordRequest;
use App\Requests\User\ConfirmPasswordRequest;
use App\Requests\User\UpdateProfileRequest;
use App\Services\ProfileAvatarService;
use Radix\Controller\AbstractController;
use Radix\Enums\Role;
use Radix\Http\Exception\NotAuthorizedException;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Session\Session;
use RuntimeException;

class UserController extends AbstractController
{
    use FormHelpers;

    public function __construct(
        private readonly ProfileAvatarService $avatarService,
    ) {}

    public function index(): Response
    {
        return $this->view('user.index');
    }

    public function show(string $id): Response
    {
        // Hämta inloggad användare för att avgöra behörighet
        $authId = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($authId) && !is_string($authId)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $authUser = User::find($authId);

        // $id här är route-parametern: vilken användare vi vill visa
        if (!$authUser || !$authUser->hasAtLeast('moderator')) {
            $user = User::with('status')->where('id', '=', $id)->first();
        } else {
            $user = User::with('status')->withSoftDeletes()->where('id', '=', $id)->first();
        }

        $roles = Role::cases();

        return $this->view('user.show', ['user' => $user, 'roles' => $roles]);
    }

    public function edit(): Response
    {
        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);

        return $this->view('user.edit', array_merge(
            ['user' => $user],
            $this->beginForm()
        ));
    }

    /**
     * @param UpdateProfileRequest $form
     */
    private function storeOldProfileFields(UpdateProfileRequest $form): void
    {
        // Spara endast icke-känsliga fält i old (INTE lösenord)
        $this->request->session()->set('old', [
            'first_name' => $form->firstName(),
            'last_name'  => $form->lastName(),
            'email'      => $form->email(),
        ]);
    }

    public function update(): Response
    {
        $this->before();

        $userId = $this->request->session()->get(Session::AUTH_KEY);
        if (!is_int($userId) && !is_string($userId)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($userId);
        if (!$user instanceof User) {
            throw new NotAuthorizedException('User not found.');
        }

        $form = new UpdateProfileRequest($this->request);

        if (!$form->validate()) {
            $this->storeOldProfileFields($form);

            return $this->formErrorView('user.edit', [
                'user' => $user,
            ], $form->errors());
        }

        $avatar = $form->avatar();

        $extraErrors = $form->extraErrorsForUpdate($userId, $avatar);
        if ($extraErrors !== []) {
            $this->storeOldProfileFields($form);

            return $this->formErrorView('user.edit', [
                'user' => $user,
            ], $extraErrors);
        }

        try {
            $this->avatarService->updateAvatar($user, $userId, $avatar);
        } catch (RuntimeException $e) {
            $this->storeOldProfileFields($form);

            return $this->formErrorView('user.edit', [
                'user' => $user,
            ], [
                'avatar' => [$e->getMessage()],
            ]);
        }

        $user->fill([
            'first_name' => $form->firstName(),
            'last_name'  => $form->lastName(),
            'email'      => $form->email(),
        ]);

        $user->save();

        return $this->formRedirectWithFlash(
            'user.index',
            'Ditt konto har uppdaterats.',
            'info'
        );
    }

    public function apiToken(): Response
    {
        return $this->view('user.api-token');
    }

    public function passwordEdit(): Response
    {
        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);

        return $this->view('user.password', array_merge(
            ['user' => $user],
            $this->beginForm()
        ));
    }

    public function passwordUpdate(): Response
    {
        $this->before();

        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);
        if (!$user instanceof User) {
            throw new NotAuthorizedException('User not found.');
        }

        $form = new ChangePasswordRequest($this->request);

        if (!$form->validate()) {
            // Spara INGET old (innehåller lösenordsfält)
            $this->request->session()->remove('old');

            return $this->formErrorView('user.password', [
                'user' => $user,
            ], $form->errors());
        }

        // Ladda det guardade lösenordet så isPasswordValid() kan jämföra korrekt
        $hashedPassword = $user->fetchGuardedAttribute('password');
        if ($hashedPassword !== '') {
            $user->forceFill(['password' => $hashedPassword]);
        }

        // Validera nuvarande lösenord mot user
        if (!$user->isPasswordValid($form->currentPassword())) {
            $this->request->session()->remove('old');

            return $this->formErrorView('user.password', [
                'user' => $user,
            ], [
                'current_password' => ['Nuvarande lösenord är fel.'],
            ]);
        }

        $user->password = $form->password();
        $user->save();

        return $this->formRedirectWithFlash(
            'user.index',
            'Ditt lösenord har uppdaterats.',
            'info'
        );
    }

    public function generateToken(): Response
    {
        $this->before();

        $userIdRaw = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_numeric($userIdRaw)) {
            return $this->formRedirectWithError(
                'auth.login.index',
                'Du måste vara inloggad för att generera en API-nyckel.'
            );
        }

        $userId = (int) $userIdRaw;

        $token = \App\Models\Token::where('user_id', '=', $userId)->first();

        if ($token instanceof \App\Models\Token) {
            $token->forceDelete();
        }

        \App\Models\Token::createToken($userId, 'Integration Token', 365);

        return $this->formRedirectWithFlash(
            'user.token.index',
            'En ny API-nyckel har genererats.',
            'info'
        );
    }

    public function close(): Response
    {
        $this->before();

        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);

        if ($user && $user->isAdmin()) {
            throw new NotAuthorizedException('You are not authorized to close this account.');
        }

        if (!$user instanceof User) {
            throw new NotAuthorizedException('User not found.');
        }

        $confirm = new ConfirmPasswordRequest($this->request);

        if (!$confirm->validate()) {
            $this->request->session()->remove('old');

            return $this->formErrorView('user.index', [
                'modal' => 'close',
            ], $confirm->errors());
        }

        // Ladda det guardade lösenordet så isPasswordValid() kan jämföra korrekt
        $hashedPassword = $user->fetchGuardedAttribute('password');
        if ($hashedPassword !== '') {
            $user->forceFill(['password' => $hashedPassword]);
        }

        if (!$user->isPasswordValid($confirm->currentPassword())) {
            $this->request->session()->remove('old');

            return $this->formErrorView('user.index', [
                'modal' => 'close',
            ], [
                'current_password' => ['Nuvarande lösenord är fel.'],
            ]);
        }

        $user->loadMissing('status');

        /** @var \App\Models\Status|null $status */
        $status = $user->getRelation('status');

        if (!$status instanceof Status) {
            throw new RuntimeException('Status relation is not loaded or invalid.');
        }

        $status->fill(['status' => 'closed', 'active' => 'offline']);
        $status->save();

        $user->delete();

        $this->request->session()->destroy(); // Förstör sessionen

        return new RedirectResponse(route('auth.logout.close-message'));
    }

    public function delete(): Response
    {
        $this->before();

        $id = $this->request->session()->get(Session::AUTH_KEY);

        if (!is_int($id) && !is_string($id)) {
            throw new NotAuthorizedException('Invalid user id in session.');
        }

        $user = User::find($id);

        if ($user && $user->isAdmin()) {
            throw new NotAuthorizedException('You are not authorized to delete this user.');
        }

        if (!$user instanceof User) {
            throw new NotAuthorizedException('User not found.');
        }

        $confirm = new ConfirmPasswordRequest($this->request);

        if (!$confirm->validate()) {
            $this->request->session()->remove('old');

            return $this->formErrorView('user.index', [
                'modal' => 'delete',
            ], $confirm->errors());
        }

        // Ladda det guardade lösenordet så isPasswordValid() kan jämföra korrekt
        $hashedPassword = $user->fetchGuardedAttribute('password');
        if ($hashedPassword !== '') {
            $user->forceFill(['password' => $hashedPassword]);
        }

        if (!$user->isPasswordValid($confirm->currentPassword())) {
            $this->request->session()->remove('old');

            return $this->formErrorView('user.index', [
                'modal' => 'delete',
            ], [
                'current_password' => ['Nuvarande lösenord är fel.'],
            ]);
        }

        $userDirectory = ROOT_PATH . '/public/images/user/' . $user->id;

        if (is_dir($userDirectory)) {
            $files = array_diff(scandir($userDirectory), ['.', '..']);
            foreach ($files as $file) {
                unlink($userDirectory . '/' . $file);
            }
            rmdir($userDirectory);
        }

        $user->forceDelete();

        $this->request->session()->destroy(); // Förstör sessionen

        return new RedirectResponse(route('auth.logout.delete-message'));
    }
}
