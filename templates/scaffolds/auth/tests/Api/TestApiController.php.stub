<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use App\Controllers\Api\ApiController;

/**
 * Testversion av ApiController som fångar felresponser i stället för att t.ex. skicka HTTP-svar + exit.
 */
final class TestApiController extends ApiController
{
    /** @var array<mixed>|null */
    public ?array $lastErrors = null;
    public ?int $lastStatus = null;

    public ?int $frozenNow = null;

    public function __construct()
    {
        // Ingen parent::__construct – vi sätter egenskaper via Reflection i testen.
    }

    protected function now(): int
    {
        return $this->frozenNow ?? parent::now();
    }

    /**
     * Fånga felmeddelanden i stället för att skicka riktig HTTP-respons.
     *
     * @param array<string, mixed> $errors
     */
    protected function respondWithErrors(array $errors, int $statusCode = 400): void
    {
        $this->lastErrors = $errors;
        $this->lastStatus = $statusCode;
        // Ingen json()->send() och inget exit.
    }
}
