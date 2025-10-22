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
        return $this->view('dashboard.index');
    }
}