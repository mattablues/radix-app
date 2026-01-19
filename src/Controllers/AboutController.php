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
}
