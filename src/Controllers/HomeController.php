<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;
use Throwable;

class HomeController extends AbstractController
{
    public function index(): Response
    {
        $latestVersion = 'v1.0.0';

        try {
            $systemUpdateClass = 'App\\Models\\SystemUpdate';

            if (class_exists($systemUpdateClass)) {
                $latest = $systemUpdateClass::orderBy('released_at', 'DESC')
                    ->limit(1)
                    ->pluck('version');

                if (is_array($latest) && isset($latest[0]) && is_string($latest[0])) {
                    $latestVersion = $latest[0];
                }
            }
        } catch (Throwable) {
            $latestVersion = 'v1.0.0';
        }

        return $this->view('home.index', ['latestVersion' => $latestVersion]);
    }
}
