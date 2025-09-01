<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Events\UserBlockedEvent;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\RedirectResponse;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use App\Models\User;

readonly class Auth implements MiddlewareInterface
{
    public function __construct(private EventDispatcher $eventDispatcher) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $session = $request->session();

        // Kontrollera om användaren är autentiserad
        if (!$session->isAuthenticated()) {
            $session->setFlashMessage('Du måste vara inloggad för att besöka sidan!', 'info');
            return new RedirectResponse(route('auth.login.index'));
        }

        // Hämta autentiserad användare
        $userId = $session->get(\Radix\Session\Session::AUTH_KEY);
        $user = User::with('status')->where('id', '=', $userId)->first();

        // Kontrollera om användaren är blockerad
        if ($user && $user->getRelation('status')->isBlocked()) { // Kontrollera blockeringsstatus;
            $this->eventDispatcher->dispatch(new UserBlockedEvent($user->id));
        }

        // Uppdatera användarens status till "online" och kontrollera timeout
        $this->handleUserSessionLifecycle($session);

        // Skicka vidare till nästa middleware / hantering
        return $next->handle($request);
    }

    /**
     * Hantera användarens session-livscykel för att markera online/inaktiv status.
     */
    private function handleUserSessionLifecycle($session): void
    {
        $userId = $session->get(\Radix\Session\Session::AUTH_KEY);

        if ($userId) {
            $user = User::find($userId); // Hämta användaren

            if ($user) {
                // Kontrollera timeout-inställningar
                $timeout = 15 * 60; // 15 minuter
                $lastLogin = $session->get('last_login', time());

                if (time() - $lastLogin > $timeout) {
                    $user->setOffline();
                    $session->clear();
                    $session->destroy();

                    redirect(route('auth.login.index')); // Omdirigera till inloggningssida
                }

                $session->set('last_login', time()); // Uppdatera för att fortsätta hålla sessionen aktiv
                $user->setOnline();
            }
        }
    }
}