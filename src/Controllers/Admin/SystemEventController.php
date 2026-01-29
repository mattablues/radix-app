<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\SystemEvent;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class SystemEventController extends AbstractController
{
    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        $rawQ = $this->request->get['q'] ?? '';
        $q = is_string($rawQ) ? trim($rawQ) : '';

        if ($q !== '') {
            $results = SystemEvent::with('user')
                ->orderBy('created_at', 'DESC')
                ->search($q, ['message', 'type'], 20, $page);

            $events = [
                'data' => $results['data'] ?? [],
                'pagination' => $results['search'] ?? [
                    'term' => $q,
                    'total' => 0,
                    'per_page' => 20,
                    'current_page' => $page,
                    'last_page' => 0,
                    'first_page' => 1,
                ],
            ];
        } else {
            $events = SystemEvent::with('user')
                ->orderBy('created_at', 'DESC')
                ->paginate(20, $page);
        }

        return $this->view('admin.system-event.index', [
            'events' => $events,
            'q' => $q,
        ]);
    }
}
