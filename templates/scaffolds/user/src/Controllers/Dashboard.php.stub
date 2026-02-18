<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SystemEvent;
use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Http\Response;
use RuntimeException;
use Throwable;

class Dashboard extends AbstractController
{
    public function index(): Response
    {
        // Räkna siffror
        $userCount = User::count()->int() ?? 0;

        // Hämta de 5 senaste händelserna med tillhörande användare
        $latestEvents = SystemEvent::with('user')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get();

        $systemStatus = 'OK';
        $statusColor = 'emerald';

        try {
            // 1. Kolla Databasen
            $dbCheck = User::query()->selectRaw('1')->fetchRaw();

            $isWritable = is_dir(cache_path('views')) && is_writable(cache_path('views'));

            if (!$dbCheck || !$isWritable) {
                throw new RuntimeException('System health check failed');
            }
        } catch (Throwable $e) {
            $systemStatus = 'Error';
            $statusColor = 'red';
        }


        return $this->view('dashboard.index', [
            'userCount'    => $userCount,
            'latestEvents' => $latestEvents,
            'systemStatus' => $systemStatus,
            'statusColor' => $statusColor,
        ]);
    }
}
