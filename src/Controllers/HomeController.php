<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;

class HomeController extends AbstractController
{
    /**
     * Definiera anpassade filter för HomeController
     */
//    protected function filters(): array
//    {
//        return [
//            'lowercase' => [
//                'callback' => fn($value) => strtolower($value),
//                'type' => 'string',
//            ],
//            'reverse' => [
//                'callback' => fn($value) => strrev($value),
//                'type' => 'string',
//            ],
//        ];
//    }

    public function index(): Response
    {
        // Dynamiskt registrera filter endast för denna vy
//        $this->viewer->registerFilter('lowercase', fn($value) => strtolower($value));
//        $this->viewer->registerFilter('reverse', fn($value) => strrev($value));
        // Rendera och returnera vyn
//        return $this->view('home.index', ['test' => 'Hello World']);

//          $user = User::find(2, true);
//          $user->restore();
//
//       $users = User::with('status')->paginate();
//          $users = User::paginate();
//          dd($users);
//
//          $softDeletedUsers = User::getOnlySoftDeleted()->get();
//          dd($softDeletedUsers);
//
//          $user = User::with('status')->search('åkebrand', ['last_name', 'first_name']);
//
//          dd($user);;

//   $user = User::find(1);
//   dd($user->status()->first());

//   dd(User::with('status')->get());

          return $this->view('home.index');
    }
}