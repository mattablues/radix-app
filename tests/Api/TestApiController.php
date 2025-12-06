<?php

declare(strict_types=1);

namespace Radix\Tests\Api;

use Radix\Controller\ApiController;

/**
 * Testversion av ApiController som fångar felresponser i stället för att t.ex. skicka HTTP-svar + exit.
 */
final class TestApiController extends ApiController
{
    /** @var array<mixed>|null */
    public ?array $lastErrors = null;
    public ?int $lastStatus = null;

    public function __construct()
    {
        // Ingen parent::__construct – vi sätter egenskaper via Reflection i testen.
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
