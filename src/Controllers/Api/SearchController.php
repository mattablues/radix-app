<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Status;
use App\Models\SystemEvent;
use App\Models\SystemUpdate;
use App\Models\User;
use Radix\Http\JsonResponse;

class SearchController extends ApiController
{
    public function profiles(): JsonResponse
    {
        // Validera förfrågan för att säkerställa att rätt data skickas med
        $this->validateRequestAllowingSession([
            'search.term' => 'required|string|min:1',
            'search.current_page' => 'nullable|integer|min:1',
        ]);

        $post = $this->request->post;

        $search = isset($post['search']) && is_array($post['search'])
            ? $post['search']
            : [];

        $termRaw        = $search['term']          ?? '';
        $currentPageRaw = $search['current_page']  ?? 1;
        $perPageRaw     = $search['per_page']      ?? 10;

        $term = is_string($termRaw) ? $termRaw : '';
        $currentPage = is_numeric($currentPageRaw) ? (int) $currentPageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 10;

        // Kontrollera om söktermen är tom, och returnera tomma resultat om så är fallet
        if ($term === '') {
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
        /** @var array{
         *     data: list<\App\Models\User>,
         *     search: array<string,mixed>
         * } $results
         */
        $results = User::with('status')
            ->search($term, ['first_name', 'last_name', 'email'], $perPage, $currentPage);

        // Formatera resultaten som JSON
        $data = array_map(
            /**
             * @param \App\Models\User $user
             * @return array<string,mixed>
             */
            function (User $user): array {
                $arr = $user->toArray();
                $avatarRaw = $arr['avatar'] ?? null;
                $path = is_string($avatarRaw) && $avatarRaw !== ''
                    ? $avatarRaw
                    : '/images/graphics/avatar.png';

                $arr['avatar_url'] = versioned_file($path);
                return $arr;
            },
            $results['data']
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'], // Metadata som t.ex. term, current_page, last_page
        ]);
    }

    public function users(): JsonResponse
    {
        $this->validateRequestAllowingSession([
            'search.term' => 'nullable|string',
            'search.current_page' => 'nullable|integer|min:1',
            'search.per_page' => 'nullable|integer|min:1',
        ]);

        $post = $this->request->post;

        $search = isset($post['search']) && is_array($post['search'])
            ? $post['search']
            : [];

        $termRaw        = $search['term']          ?? '';
        $currentPageRaw = $search['current_page']  ?? 1;
        $perPageRaw     = $search['per_page']      ?? 1;

        $term = is_string($termRaw) ? trim($termRaw) : '';
        $currentPage = is_numeric($currentPageRaw) ? (int) $currentPageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 1;

        // Tom term => vanlig paginering (så "Rensa" visar allt)
        if ($term === '') {
            $page = User::with('status')
                ->paginate($perPage, $currentPage);

            return $this->json([
                'success' => true,
                'data' => array_map(
                    /**
                     * @param mixed $user
                     * @return array<string,mixed>
                     */
                    function ($user): array {
                        if (!$user instanceof User) {
                            return [];
                        }

                        $idRaw = $user->getAttribute('id');
                        $id = (is_int($idRaw) || is_string($idRaw)) ? (string) $idRaw : '';

                        $statusRel = $user->getRelation('status');
                        $status = $statusRel instanceof Status ? $statusRel : null;

                        $statusRaw = $status instanceof Status ? ($status->getAttribute('status') ?? '') : '';
                        $statusValue = is_string($statusRaw) ? $statusRaw : '';

                        $activeRaw = $status instanceof Status ? ($status->getAttribute('active') ?? '') : '';
                        $activeValue = is_string($activeRaw) ? $activeRaw : '';

                        $activeAtRaw = $status instanceof Status ? ($status->getAttribute('active_at') ?? null) : null;
                        $activeAtStr = is_string($activeAtRaw) ? $activeAtRaw : '';

                        return [
                            'id' => $idRaw,
                            'first_name' => $user->getAttribute('first_name'),
                            'last_name' => $user->getAttribute('last_name'),
                            'email' => $user->getAttribute('email'),

                            'status' => $statusValue,
                            'active' => $activeValue,
                            'active_at' => $activeAtStr,

                            'show_url' => $id !== '' ? '/user/' . $id . '/show' : '',
                            'send_activation_url' => $id !== '' ? '/admin/users/' . $id . '/send-activation' : '',
                            'block_url' => $id !== '' ? '/admin/users/' . $id . '/block' : '',
                            'is_admin' => (bool) $user->isAdmin(),
                        ];
                    },
                    $page['data'] ?? []
                ),
                'meta' => $page['pagination'] ?? [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                    'first_page' => 1,
                    'term' => $term,
                ],
            ]);
        }

        $results = User::with('status')
            ->search($term, ['first_name', 'last_name', 'email'], $perPage, $currentPage);

        $data = array_map(
            /**
             * @param mixed $user
             * @return array<string,mixed>
             */
            function ($user): array {
                if (!$user instanceof User) {
                    return [];
                }

                $idRaw = $user->getAttribute('id');
                $id = (is_int($idRaw) || is_string($idRaw)) ? (string) $idRaw : '';

                $statusRel = $user->getRelation('status');
                $status = $statusRel instanceof Status ? $statusRel : null;

                $statusRaw = $status instanceof Status ? ($status->getAttribute('status') ?? '') : '';
                $statusValue = is_string($statusRaw) ? $statusRaw : '';

                $activeRaw = $status instanceof Status ? ($status->getAttribute('active') ?? '') : '';
                $activeValue = is_string($activeRaw) ? $activeRaw : '';

                $activeAtRaw = $status instanceof Status ? ($status->getAttribute('active_at') ?? null) : null;
                $activeAtStr = is_string($activeAtRaw) ? $activeAtRaw : '';

                return [
                    'id' => $idRaw,
                    'first_name' => $user->getAttribute('first_name'),
                    'last_name' => $user->getAttribute('last_name'),
                    'email' => $user->getAttribute('email'),

                    'status' => $statusValue,
                    'active' => $activeValue,
                    'active_at' => $activeAtStr,

                    'show_url' => $id !== '' ? '/user/' . $id . '/show' : '',
                    'send_activation_url' => $id !== '' ? '/admin/users/' . $id . '/send-activation' : '',
                    'block_url' => $id !== '' ? '/admin/users/' . $id . '/block' : '',
                    'is_admin' => (bool) $user->isAdmin(),
                ];
            },
            $results['data'] ?? []
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'] ?? [
                'term' => $term,
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => 0,
                'first_page' => 1,
            ],
        ]);
    }

    public function deletedUsers(): JsonResponse
    {
        $this->validateRequestAllowingSession([
            'search.term' => 'nullable|string',
            'search.current_page' => 'nullable|integer|min:1',
            'search.per_page' => 'nullable|integer|min:1',
        ]);

        $post = $this->request->post;

        $search = isset($post['search']) && is_array($post['search'])
            ? $post['search']
            : [];

        $termRaw        = $search['term'] ?? '';
        $currentPageRaw = $search['current_page'] ?? 1;
        $perPageRaw     = $search['per_page'] ?? 20;

        $term = is_string($termRaw) ? trim($termRaw) : '';
        $currentPage = is_numeric($currentPageRaw) ? (int) $currentPageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 20;

        $base = User::with('status')
            ->getOnlySoftDeleted()
            ->orderBy('deleted_at', 'DESC');

        if ($term === '') {
            $page = $base->paginate($perPage, $currentPage);

            return $this->json([
                'success' => true,
                'data' => array_map(
                    /**
                     * @param mixed $user
                     * @return array<string,mixed>
                     */
                    function ($user): array {
                        if (!$user instanceof User) {
                            return [];
                        }

                        $idRaw = $user->getAttribute('id');
                        $id = (is_int($idRaw) || is_string($idRaw)) ? (string) $idRaw : '';

                        $deletedAtRaw = $user->getAttribute('deleted_at');
                        $deletedAt = is_string($deletedAtRaw) ? $deletedAtRaw : '';

                        return [
                            'id' => $idRaw,
                            'first_name' => $user->getAttribute('first_name'),
                            'last_name' => $user->getAttribute('last_name'),
                            'email' => $user->getAttribute('email'),
                            'deleted_at' => $deletedAt,
                            'restore_url' => $id !== '' ? '/admin/users/' . $id . '/restore' : '',
                        ];
                    },
                    $page['data'] ?? []
                ),
                'meta' => $page['pagination'] ?? [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                    'first_page' => 1,
                    'term' => $term,
                ],
            ]);
        }

        $results = $base->search($term, ['first_name', 'last_name', 'email'], $perPage, $currentPage);

        $data = array_map(
            /**
             * @param mixed $user
             * @return array<string,mixed>
             */
            function ($user): array {
                if (!$user instanceof User) {
                    return [];
                }

                $idRaw = $user->getAttribute('id');
                $id = (is_int($idRaw) || is_string($idRaw)) ? (string) $idRaw : '';

                $deletedAtRaw = $user->getAttribute('deleted_at');
                $deletedAt = is_string($deletedAtRaw) ? $deletedAtRaw : '';

                return [
                    'id' => $idRaw,
                    'first_name' => $user->getAttribute('first_name'),
                    'last_name' => $user->getAttribute('last_name'),
                    'email' => $user->getAttribute('email'),
                    'deleted_at' => $deletedAt,
                    'restore_url' => $id !== '' ? '/admin/users/' . $id . '/restore' : '',
                ];
            },
            $results['data'] ?? []
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'] ?? [
                'term' => $term,
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => 0,
                'first_page' => 1,
            ],
        ]);
    }

    public function systemEvents(): JsonResponse
    {
        $this->validateRequestAllowingSession([
            'search.term' => 'nullable|string',
            'search.current_page' => 'nullable|integer|min:1',
            'search.per_page' => 'nullable|integer|min:1',
        ]);

        $post = $this->request->post;

        $search = isset($post['search']) && is_array($post['search'])
            ? $post['search']
            : [];

        $termRaw        = $search['term']          ?? '';
        $currentPageRaw = $search['current_page']  ?? 1;
        $perPageRaw     = $search['per_page']      ?? 20;

        $term = is_string($termRaw) ? trim($termRaw) : '';
        $currentPage = is_numeric($currentPageRaw) ? (int) $currentPageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 20;

        if ($term === '') {
            $page = SystemEvent::with('user')
                ->orderBy('created_at', 'DESC')
                ->paginate($perPage, $currentPage);

            return $this->json([
                'success' => true,
                'data' => array_map(
                    /**
                     * @param mixed $event
                     * @return array<string,mixed>
                     */
                    function ($event): array {
                        if (!$event instanceof SystemEvent) {
                            return [];
                        }

                        $user = $event->getRelation('user');
                        $userName = ($user instanceof User)
                            ? trim($user->first_name . ' ' . $user->last_name)
                            : null;

                        return [
                            'created_at' => $event->created_at,
                            'type' => $event->type,
                            'type_badge_class' => $event->getTypeBadgeClass(),
                            'message' => $event->message,
                            'user_name' => $userName,
                        ];
                    },
                    $page['data'] ?? []
                ),
                'meta' => $page['pagination'] ?? [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                    'first_page' => 1,
                ],
            ]);
        }

        $results = SystemEvent::with('user')
            ->orderBy('created_at', 'DESC')
            ->search($term, ['message', 'type'], $perPage, $currentPage);

        $data = array_map(
            /**
             * @param mixed $event
             * @return array<string,mixed>
             */
            function ($event): array {
                if (!$event instanceof SystemEvent) {
                    return [];
                }

                $user = $event->getRelation('user');
                $userName = ($user instanceof User)
                    ? trim($user->first_name . ' ' . $user->last_name)
                    : null;

                return [
                    'created_at' => $event->created_at,
                    'type' => $event->type,
                    'type_badge_class' => $event->getTypeBadgeClass(),
                    'message' => $event->message,
                    'user_name' => $userName,
                ];
            },
            $results['data'] ?? []
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'] ?? [
                'term' => $term,
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => 0,
                'first_page' => 1,
            ],
        ]);
    }

    public function systemUpdates(): JsonResponse
    {
        $this->validateRequestAllowingSession([
            'search.term' => 'nullable|string',
            'search.current_page' => 'nullable|integer|min:1',
            'search.per_page' => 'nullable|integer|min:1',
        ]);

        $post = $this->request->post;

        $search = isset($post['search']) && is_array($post['search'])
            ? $post['search']
            : [];

        $termRaw        = $search['term']          ?? '';
        $currentPageRaw = $search['current_page']  ?? 1;
        $perPageRaw     = $search['per_page']      ?? 10;

        $term = is_string($termRaw) ? trim($termRaw) : '';
        $currentPage = is_numeric($currentPageRaw) ? (int) $currentPageRaw : 1;
        $perPage     = is_numeric($perPageRaw) ? (int) $perPageRaw : 10;

        // Tom term => vanlig paginering (så "Rensa" visar allt)
        if ($term === '') {
            $page = SystemUpdate::orderBy('released_at', 'DESC')
                ->paginate($perPage, $currentPage);

            return $this->json([
                'success' => true,
                'data' => array_map(
                    /**
                     * @param mixed $update
                     * @return array<string,mixed>
                     */
                    function ($update): array {
                        if (!$update instanceof SystemUpdate) {
                            return [];
                        }

                        $releasedAtRaw = $update->getAttribute('released_at');
                        $releasedAt = is_string($releasedAtRaw) ? $releasedAtRaw : '';

                        $ts = $releasedAt !== '' ? strtotime($releasedAt) : false;
                        $releasedAtDate = $ts !== false ? date('Y-m-d', $ts) : '';

                        $idRaw = $update->getAttribute('id');
                        $id = (is_int($idRaw) || is_string($idRaw)) ? (string) $idRaw : '';

                        return [
                            'id' => $idRaw,
                            'version' => $update->getAttribute('version'),
                            'title' => $update->getAttribute('title'),
                            'description' => $update->getAttribute('description'),
                            'is_major' => (bool) $update->getAttribute('is_major'),
                            'released_at' => $releasedAt,
                            'released_at_date' => $releasedAtDate,
                            'edit_url' => $id !== '' ? '/admin/updates/' . $id . '/edit' : '',
                        ];
                    },
                    $page['data'] ?? []
                ),
                'meta' => $page['pagination'] ?? [
                    'total' => 0,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => 0,
                    'first_page' => 1,
                ],
            ]);
        }

        $results = SystemUpdate::query()
            ->orderBy('released_at', 'DESC')
            ->search($term, ['version', 'title', 'description'], $perPage, $currentPage);

        $data = array_map(
            /**
             * @param mixed $update
             * @return array<string,mixed>
             */
            function ($update): array {
                if (!$update instanceof SystemUpdate) {
                    return [];
                }

                $releasedAtRaw = $update->getAttribute('released_at');
                $releasedAt = is_string($releasedAtRaw) ? $releasedAtRaw : '';

                $ts = $releasedAt !== '' ? strtotime($releasedAt) : false;
                $releasedAtDate = $ts !== false ? date('Y-m-d', $ts) : '';

                $idRaw = $update->getAttribute('id');
                $id = (is_int($idRaw) || is_string($idRaw)) ? (string) $idRaw : '';

                return [
                    'id' => $idRaw,
                    'version' => $update->getAttribute('version'),
                    'title' => $update->getAttribute('title'),
                    'description' => $update->getAttribute('description'),
                    'is_major' => (bool) $update->getAttribute('is_major'),
                    'released_at' => $releasedAt,
                    'released_at_date' => $releasedAtDate,
                    'edit_url' => $id !== '' ? '/admin/updates/' . $id . '/edit' : '',
                ];
            },
            $results['data'] ?? []
        );

        return $this->json([
            'success' => true,
            'data' => $data,
            'meta' => $results['search'] ?? [
                'term' => $term,
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => 0,
                'first_page' => 1,
            ],
        ]);
    }
}
