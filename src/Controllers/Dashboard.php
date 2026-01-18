<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SystemEvent;
use App\Models\SystemUpdate;
use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class Dashboard extends AbstractController
{
    public function index(): Response
    {
        // Räkna siffror
        $userCount = User::count()->int() ?? 0;

        $latestUpdate = SystemUpdate::orderBy('released_at', 'DESC')
            ->first();

        // Hämta de 5 senaste händelserna med tillhörande användare
        $latestEvents = SystemEvent::with('user')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();


        return $this->view('dashboard.index', [
            'userCount'    => $userCount,
            'latestUpdate' => $latestUpdate,
            'latestEvents' => $latestEvents,
        ]);
    }
}
