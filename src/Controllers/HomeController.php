<?php

declare(strict_types=1);

namespace App\Controllers;

use Radix\Controller\AbstractController;
use Radix\Http\Response;
use Radix\Support\GeoLocator;

class HomeController extends AbstractController
{
    public function index(): Response
    {
//        $geoLocator = new GeoLocator();
//
//        $location = $geoLocator->getLocation(); // Hämta plats för besökaren
//        echo "Land: " . ($location['country'] ?? 'Okänt');
//        echo "Stad: " . ($location['city'] ?? 'Okänt');
//
//        // Hämta endast specifik data
//        $country = $geoLocator->get('country', '85.228.5.49'); // För valfri IP
//        echo "Land för 85.228.5.49: $country";
        return $this->view('home.index');
    }
}