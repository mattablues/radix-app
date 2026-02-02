<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\Token;
use Radix\Controller\ApiController as FrameworkApiController;

abstract class ApiController extends FrameworkApiController
{
    /**
         * Kapsla in tidskällan så den går att kontrollera i tester.
         */
    protected function now(): int
    {
        return time();
    }

    protected function isTokenValid(string $token): bool
    {
        $this->cleanupExpiredTokens();

        $validToken = getenv('API_TOKEN');
        if ($validToken !== false && $token === $validToken) {
            return true;
        }

        /** @var Token|null $existingToken */
        $existingToken = Token::query()->where('value', '=', $token)->first();
        if (!$existingToken) {
            return false;
        }

        $expiresAt = (string) $existingToken->expires_at;
        if ($expiresAt === '' || strtotime($expiresAt) < $this->now()) {
            return false;
        }

        return true;
    }

    protected function cleanupExpiredTokens(): void
    {
        Token::query()
            ->where('expires_at', '<', date('Y-m-d H:i:s', $this->now()))
            ->delete()
            ->execute();
    }
}
