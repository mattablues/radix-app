<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SystemUpdate;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class AboutController extends AbstractController
{
    public function index(): Response
    {
        $recentUpdates = SystemUpdate::orderBy('released_at', 'DESC')
            ->limit(5)
            ->get();

        return $this->view('about.index', [
            'recentUpdates' => $recentUpdates,
        ]);
    }

    public function changelog(): Response
    {
        $rawPage = $this->request->get['page'] ?? 1;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        $updates = \App\Models\SystemUpdate::orderBy('released_at', 'DESC')
            ->paginate(10, $page);

        return $this->view('about.changelog', [
            'updates' => $updates,
        ]);
    }
}
