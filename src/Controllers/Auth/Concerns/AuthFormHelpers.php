<?php

declare(strict_types=1);

namespace App\Controllers\Auth\Concerns;

use App\Controllers\Concerns\FormHelpers;
use Radix\Http\RedirectResponse;
use Radix\Http\Response;

trait AuthFormHelpers
{
    use FormHelpers;

    /**
     * Bakåtkompatibelt API: auth-namn som delegar till generella helpers.
     *
     * @return array{honeypotId:string}
     */
    protected function beginAuthForm(): array
    {
        return $this->beginForm();
    }

    protected function rotateHoneypot(): string
    {
        return $this->rotateFormHoneypot();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors
     */
    protected function authFormErrorView(string $view, array $data, array $errors): Response
    {
        return $this->formErrorView($view, $data, $errors);
    }

    protected function endAuthFormSuccess(): void
    {
        $this->endFormSuccess();
    }

    /**
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     */
    protected function authRedirect(string $routeName, array $params = []): RedirectResponse
    {
        return $this->formRedirect($routeName, $params);
    }

    /**
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     */
    protected function authRedirectWithFlash(
        string $routeName,
        string $message,
        string $type = 'info',
        array $params = []
    ): RedirectResponse {
        return $this->formRedirectWithFlash($routeName, $message, $type, $params);
    }

    /**
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     */
    protected function authRedirectWithError(
        string $routeName,
        string $message,
        array $params = []
    ): RedirectResponse {
        return $this->formRedirectWithError($routeName, $message, $params);
    }
}
