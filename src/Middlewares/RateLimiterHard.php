<?php

declare(strict_types=1);

namespace App\Middlewares;

final class RateLimiterHard extends RateLimiter
{
    public function __construct()
    {
        parent::__construct(redis: null, limit: 10, windowSeconds: 60, bucket: 'hard', fileCache: null);
    }
}
