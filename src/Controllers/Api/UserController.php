<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Status;
use App\Models\User;
use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;

class UserController extends ApiController
{
    public function index(): JsonResponse
    {
        $this->validateRequest(); // Kontrollera API-token för GET-anrop

        // Hämta användardata
        $currentPage = (int)($this->request->get['page'] ?? 1);
        $perPage = (int)($this->request->get['perPage'] ?? 10);

        $results = User::with('status')
            ->paginate($perPage, $currentPage);

        return $this->json([
            'success' => true,
            'data' => array_map(fn($user) => $user->toArray(), $results['data']),
            'meta' => $results['pagination'],
        ]);
    }

    public function store(): JsonResponse
    {
        // Validera inkommande förfrågan
        $this->validateRequest([
            'first_name' => 'required|string|min:2|max:15',
            'last_name' => 'required|string|min:2|max:15',
            'email' => 'required|email|unique:App\Models\User,email',
            'password' => 'required|string|min:8|max:15',
            'password_confirmation' => 'required|confirmed:password',
        ]);

        // Skapa användare
        $user = new User();
        $user->fill([
            'first_name' => $this->request->post['first_name'],
            'last_name' => $this->request->post['last_name'],
            'email' => $this->request->post['email'],
        ]);
        $user->password = $this->request->post['password'];
        $user->save();

        $status = new Status();
        $status->fill([
            'user_id' => $user->id,
            'status' => 'activate',
        ]);
        $status->save();

        // Returnera resultat
        return $this->json(['success' => true, 'data' => $user->toArray()], 201);
    }

    public function update(string $id): JsonResponse
    {
        $this->validateRequest([
            'first_name' => 'nullable|string|min:2|max:15',
            'last_name' => 'nullable|string|min:2|max:15',
            'email' => 'nullable|email', // Endast validera om e-post skickas
            'password' => 'nullable|string|min:6',
        ]);

        $user = User::find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren kunde inte hittas.']
            ], 404);
        }

        $data = $this->request->filterFields($this->request->post, ['password']);

       // Hantera lösenord
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->fill($data);
        $user->save();

        return $this->json([
           'success' => true,
           'data' => $user->toArray(),
        ]);
    }

    public function partialUpdate(string $id): JsonResponse
    {
        $rules = [
            'first_name' => 'sometimes|string|min:2|max:15',
            'last_name' => 'sometimes|string|min:2|max:15',
        ];

        // Lägg endast till email-regeln om fältet existerar i indata
        if (isset($this->request->post['email'])) {
            $rules['email'] = "email|unique:App\Models\User,email,$id,id";
        }

        $this->validateRequest($rules);

        $user = User::find($id);

        if (!$user) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren kunde inte hittas.']
            ], 404);
        }

        // Filtrera bort onödiga fält
        $data = $this->request->filterFields($this->request->post);

        // Uppdatera användaren
        $user->fill($data);
        $user->save();

        return $this->json([
            'success' => true,
            'data' => $user->toArray(),
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        // Steg 1: Validera API-token eller session
        $this->validateRequest();

        // Steg 2: Hämta användaren (inklusive trashed)
        $user = User::find($id, true);

        // Kontrollera att användaren existerar
        if (!$user) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren kunde inte hittas.']
            ], 404);
        }

        // Steg 3: Säkerställ att `deleted_at` finns, annars hämta det
        if (!array_key_exists('deleted_at', $user->getAttributes())) {
            $user->deleted_at = $user->fetchGuardedAttribute('deleted_at');
        }

        // Steg 4: Kontrollera om användaren redan är soft deleted
        if ($user->deleted_at) {
            return $this->json([
                'success' => false,
                'errors' => ['user' => 'Användaren är redan soft deleted.']
            ], 400);
        }

        // Steg 5: Utför soft delete
        try {
            $user->delete();
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'errors' => ['server' => "Fel: {$e->getMessage()}"]
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Användaren har raderats (soft delete).',
        ]);
    }
}