<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\UploadService;
use Radix\Controller\AbstractController;
use Radix\Http\Exception\NotAuthorizedException;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;
use Radix\Session\Session;
use Radix\Support\Validator;

class UserController extends AbstractController
{
    public function index(): Response
    {
        return $this->view('user.index');
    }

    public function show(string $id): Response
    {
        $user = User::with('status')->where('id', '=', $id)->first();

        return $this->view('user.show', ['user' => $user]);
    }

    public function edit(): Response
    {
        $user = User::find($this->request->session()->get(Session::AUTH_KEY));

        return $this->view('user.edit', ['user' => $user]);
    }

    public function update(): Response
    {
        $this->before();

        $data = $this->request->post; // Hämta formulärdata
        $avatar = $this->request->files['avatar'] ?? null; // Hämta avatar-filen om den finns

        $userId = $this->request->session()->get(Session::AUTH_KEY); // Hämta aktuellt användar-ID
        $user = User::find($userId); // Hitta användaren i databasen

        // Validera data inklusive avatar
        $validator = new Validator($data + ['avatar' => $avatar], [
            'first_name' => 'required|min:2|max:15',
            'last_name' => 'required|min:2|max:15',
            'email' => 'required|email|unique:App\Models\User,email,id=' . $userId,
            'avatar' => 'nullable|file_size:2|file_type:image/jpeg,image/png', // Validera avatar-filen
            'password' => 'nullable|min:8|max:15',
            'password_confirmation' => 'nullable|required_with:password|confirmed:password',
        ]);

        if (!$validator->validate()) {
            // Om validering misslyckas, lagra gamla indata och returnera vy med felmeddelanden
            $this->request->session()->set('old', $data);

            return $this->view('user.edit', [
                'user' => $user,
                'errors' => $validator->errors(),
            ]);
        }

        // Hantera avatar-uppladdning om en fil har laddats upp
        if ($avatar && $avatar['error'] === UPLOAD_ERR_OK) {
            try {
                $uploadDirectory = ROOT_PATH . "/public/images/user/$userId/";

                // Skapa en instans av UploadService
                $uploadService = new UploadService();

                // Radera gammal avatar om det inte är standard-avatar
                if ($user->avatar !== '/images/graphics/avatar.png') {
                    $oldAvatarPath = ROOT_PATH . $user->avatar;
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }

                // Ladda upp och bearbeta avataren med `uploadAvatar`
                $data['avatar'] = $uploadService->uploadAvatar($avatar, $uploadDirectory);
            } catch (\RuntimeException $e) {
                // Vid fel under uppladdning, returnera vy med felet
                return $this->view('user.edit', [
                    'errors' => ['avatar' => $e->getMessage()],
                ]);
            }
        }

        // Kontrollera avatar innan fälten filtreras
        if ($avatar && $avatar['error'] === UPLOAD_ERR_NO_FILE) {
            unset($data['avatar']); // Ingen fil uppladdad, ta bort avatar från datan
        }

        // Filtrera irrelevanta fält
        $data = $this->request->filterFields($data);

        // Rensa session för gamla indata
        $this->request->session()->remove('old');

        // Uppdatera användardata i databasen
        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
        ]);

        // Uppdatera avatar om det finns en ny filväg
        if (!empty($data['avatar']) && is_string($data['avatar'])) {
            $user->avatar = $data['avatar'];
        }

        // Uppdatera lösenord om ett nytt lösenord angavs
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        // Spara ändringar i databasen
        $user->save();

        // Ange ett framgångsmeddelande
        $this->request->session()->setFlashMessage("Konto för {$data['first_name']} {$data['last_name']} har uppdaterats.");

        // Omdirigera till användarens startsida
        return new RedirectResponse(route('user.index'));
    }

    public function close(): Response
    {
        $this->before();

        $user = User::find($this->request->session()->get(Session::AUTH_KEY));

        if ($user && $user->hasRole('admin')) {
            throw new NotAuthorizedException('You are not authorized to close this account.');
        }

        $status = $user->status()->first();
        $status->fill(['status' => 'closed', 'active' => 'offline']);
        $status->save();

        $user->delete();

        $this->request->session()->destroy(); // Förstör sessionen

        return new RedirectResponse(route('auth.logout.close-message'));
    }

    public function delete(): Response
    {
        $this->before();

        $user = User::find($this->request->session()->get(Session::AUTH_KEY));

        if ($user && $user->hasRole('admin')) {
            throw new NotAuthorizedException('You are not authorized to delete this user.');
        }

        $userDirectory = ROOT_PATH . '/public/images/user/' . $user->id;

        // Kontrollera om katalogen existerar innan du försöker ta bort den
        if (is_dir($userDirectory)) {
            // Iterera och ta bort alla filer i katalogen
            $files = array_diff(scandir($userDirectory), ['.', '..']);
            foreach ($files as $file) {
                unlink($userDirectory . '/' . $file);
            }

            // Ta bort själva katalogen
            rmdir($userDirectory);
        }

        $user->forceDelete();

        $this->request->session()->destroy(); // Förstör sessionen        $this->auth->logout();

        return new RedirectResponse(route('auth.logout.delete-message'));
    }
}