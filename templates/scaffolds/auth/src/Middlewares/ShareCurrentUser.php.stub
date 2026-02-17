<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Models\User;
use Radix\Http\Request;
use Radix\Http\RequestHandlerInterface;
use Radix\Http\Response;
use Radix\Middleware\MiddlewareInterface;
use Radix\Session\Session;
use Radix\Viewer\TemplateViewerInterface;
use Throwable;

readonly class ShareCurrentUser implements MiddlewareInterface
{
    public function __construct(private TemplateViewerInterface $viewer) {}

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        $session = $request->session();

        $userIdRaw = $session->get(Session::AUTH_KEY);

        $userId = null;
        if (is_int($userIdRaw)) {
            $userId = $userIdRaw;
        } elseif (is_string($userIdRaw)) {
            $trimmed = trim($userIdRaw);
            if ($trimmed !== '' && ctype_digit($trimmed)) {
                $userId = (int) $trimmed;
            }
        }

        if (is_int($userId)) {
            try {
                /** @var User|null $user */
                $user = User::with(['status', 'token'])
                    ->where('id', '=', $userId)
                    ->first();

                if ($user instanceof User) {
                    $this->viewer->shared('currentUser', $user);

                    $tokenRelation = $user->getRelation('token');

                    $currentToken = is_object($tokenRelation) && method_exists($tokenRelation, 'getAttribute')
                        ? $tokenRelation->getAttribute('value')
                        : null;

                    $this->viewer->shared('currentToken', $currentToken);
                }
            } catch (Throwable) {
                // Fail-safe: blockera inte hela sidan om ORM/relations/DB strular här.
                // (När allt funkar kan vi göra detta striktare igen.)
            }
        }

        return $next->handle($request);
    }
}
