<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use Radix\Controller\AbstractController;
use Radix\Http\Response;

class Dashboard extends AbstractController
{
    public function index(): Response
    {

        // Räkna siffror - lägg till .int() för att köra frågan
        $userCount = User::count()->int() ?? 0;

        return $this->view('dashboard.index', ['userCount' => $userCount]);
    }
}
