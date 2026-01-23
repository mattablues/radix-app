<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use Radix\Http\RedirectResponse;
use Radix\Http\Response;

trait FormHelpers
{
    /**
     * Standard för GET-sidor: rensa gammal form-state och skapa ny honeypot.
     *
     * @return array{honeypotId:string}
     */
    protected function beginForm(): array
    {
        $this->request->session()->remove('old');
        $this->request->session()->remove('honeypot_id');

        $honeypotId = generate_honeypot_id();
        $this->request->session()->set('honeypot_id', $honeypotId);

        return ['honeypotId' => $honeypotId];
    }

    /**
     * Rotera honeypot-id (bra vid valideringsfel / form-error).
     */
    protected function rotateFormHoneypot(): string
    {
        $honeypotId = generate_honeypot_id();
        $this->request->session()->set('honeypot_id', $honeypotId);

        return $honeypotId;
    }

    /**
     * Standardiserad "return view vid fel" för formulär.
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @param array<string, mixed> $errors
     */
    protected function formErrorView(string $view, array $data, array $errors): Response
    {
        $data['honeypotId'] = $this->rotateFormHoneypot();
        $data['errors'] = $errors;

        return $this->view($view, $data);
    }

    /**
     * Standardiserad städning efter lyckad submit.
     */
    protected function endFormSuccess(): void
    {
        $this->request->session()->remove('old');
        $this->request->session()->remove('honeypot_id');
    }

    /**
     * Standardiserad redirect (med form-state cleanup).
     *
     * @param string $routeName
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     */
    protected function formRedirect(string $routeName, array $params = []): RedirectResponse
    {
        $this->endFormSuccess();
        return new RedirectResponse(route($routeName, $params));
    }

    /**
     * Standardiserad redirect med flash + form-state cleanup.
     *
     * @param string $routeName
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     */
    protected function formRedirectWithFlash(
        string $routeName,
        string $message,
        string $type = 'info',
        array $params = []
    ): RedirectResponse {
        $this->endFormSuccess();
        $this->request->session()->setFlashMessage($message, $type);

        return new RedirectResponse(route($routeName, $params));
    }

    /**
     * Shortcut för error-redirect.
     *
     * @param string $routeName
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     */
    protected function formRedirectWithError(
        string $routeName,
        string $message,
        array $params = []
    ): RedirectResponse {
        return $this->formRedirectWithFlash($routeName, $message, 'error', $params);
    }
}
