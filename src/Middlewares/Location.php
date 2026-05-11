<?php

declare(strict_types=1);

namespace App\Middlewares;

use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use Radix\Support\GeoLocator;

readonly class Location implements MiddlewareInterface
{
    public function __construct(private GeoLocator $geoLocator) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $appEnv = getenv('APP_ENV') ?: 'production';
        $geoLocatorEnabled = strtolower(trim((string) (getenv('GEOLOCATOR_ENABLED') ?: '0')));

        if (!in_array($geoLocatorEnabled, ['1', 'true', 'yes', 'on'], true)) {
            return $next->handle($request);
        }

        if ($appEnv === 'development' || $appEnv === 'local' || $appEnv === 'test') {
            return $next->handle($request);
        }

        $expectedCountry = trim((string) (getenv('LOCATOR_COUNTRY') ?: ''));
        $expectedCity = trim((string) (getenv('LOCATOR_CITY') ?: ''));

        if ($expectedCountry === '' || $expectedCity === '') {
            return $next->handle($request);
        }

        $location = $this->geoLocator->getLocation();

        $country = is_string($location['country'] ?? null) ? $location['country'] : '';
        $city = is_string($location['city'] ?? null) ? $location['city'] : '';

        if ($country !== $expectedCountry || $city !== $expectedCity) {
            $request->session()->setFlashMessage(
                'Endast kommuninvånare i ' . $expectedCity . ' kommun kan registrera sig för att rösta'
            );

            return new RedirectResponse(route('home.index'));
        }

        return $next->handle($request);
    }
}
