<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;
use Throwable;

class AboutController extends AbstractController
{
    public function index(): Response
    {
        $recentUpdates = [];

        try {
            $systemUpdateClass = 'App\\Models\\SystemUpdate';

            if (class_exists($systemUpdateClass)) {
                /** @phpstan-ignore-next-line optional scaffold model */
                $recentUpdates = $systemUpdateClass::orderBy('released_at', 'DESC')
                    ->limit(5)
                    ->get();
            }
        } catch (Throwable) {
            $recentUpdates = [];
        }

        return $this->view('about.index', [
            'recentUpdates' => $recentUpdates,
        ]);
    }
}
