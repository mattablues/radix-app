<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SystemUpdate;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class HomeController extends AbstractController
{
    public function index(): Response
    {
        $latestVersion = SystemUpdate::orderBy('released_at', 'DESC')
        ->limit(1)
        ->pluck('version')[0] ?? 'v1.0.0';

        return $this->view('home.index', ['latestVersion' => $latestVersion]);
    }
}
