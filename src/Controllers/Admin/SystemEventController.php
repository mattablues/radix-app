<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\SystemEvent;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class SystemEventController extends AbstractController
{
    /**
     * Visa händelseloggen.
     */
    public function index(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        // Hämta händelser med tillhörande användare
        $events = SystemEvent::with('user')
            ->orderBy('created_at', 'DESC')
            ->paginate(20, $page);

        return $this->view('admin.system-event.index', [
            'events' => $events,
        ]);
    }
}
