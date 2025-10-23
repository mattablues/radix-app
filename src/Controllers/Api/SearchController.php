<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\User;
use Radix\Controller\ApiController;
use Radix\Http\JsonResponse;

class SearchController extends ApiController
{
    public function users(): JsonResponse
    {
        // Validera förfrågan för att säkerställa att rätt data skickas med
        $this->validateRequest([
            'search.term' => 'required|string|min:1',
            'search.current_page' => 'nullable|integer|min:1',
        ]);

        // Hämta och sanitera sökparametrar från request
        $term = $this->request->post['search']['term'] ?? '';
        $currentPage = (int)($this->request->post['search']['current_page'] ?? 1);
        $perPage = (int)($this->request->post['search']['per_page'] ?? 1);

        // Kontrollera om söktermen är tom, och returnera tomma resultat om så är fallet
        if (empty($term)) {
            return $this->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'term' => $term,
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                ],
            ]);
        }

        // Utför sökningen i User-modellen med hjälp av QueryBuilder
        $results = User::with('status')->search($term, ['first_name', 'last_name', 'email'], $perPage, $currentPage);

        // Formatera resultaten som JSON
        $data = array_map(fn($user) => $user->toArray(), $results['data']);

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'], // Metadata som t.ex. term, current_page, last_page
        ]);
    }

    public function deletedUsers(): JsonResponse
    {
        // Validera förfrågan för att säkerställa att rätt data skickas med
        $this->validateRequest([
            'search.term' => 'required|string|min:1',
            'search.current_page' => 'nullable|integer|min:1',
        ]);

        // Hämta och sanitera sökparametrar från request
        $term = $this->request->post['search']['term'] ?? '';
        $currentPage = (int)($this->request->post['search']['current_page'] ?? 1);
        $perPage = (int)($this->request->post['search']['per_page'] ?? 10);

        // Kontrollera om söktermen är tom, och returnera tomma resultat om så är fallet
        if (empty($term)) {
            return $this->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'term' => $term,
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                ],
            ]);
        }

        // Utför sökningen i User-modellen med hjälp av QueryBuilder
        $results = User::with('status')->getOnlySoftDeleted()->search($term, ['first_name', 'last_name', 'email'], $perPage, $currentPage);

        // Formatera resultaten som JSON
        $data = array_map(fn($user) => $user->toArray(), $results['data']);

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'], // Metadata som t.ex. term, current_page, last_page
        ]);
    }
}