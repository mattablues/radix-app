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
     * Redirect med querystring (och form-state cleanup).
     *
     * @param string $routeName
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     * @phpstan-param array<string, bool|float|int|string|null> $query
     */
    protected function formRedirectWithQuery(string $routeName, array $params = [], array $query = []): RedirectResponse
    {
        $this->endFormSuccess();

        $url = route($routeName, $params);

        $query = array_filter(
            $query,
            static fn($v): bool => $v !== null && $v !== '' && $v !== false
        );

        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        return new RedirectResponse($url);
    }

    /**
     * Redirecta + flash + querystring (q/page etc) i ett.
     *
     * @param string $routeName
     * @param string $message
     * @param string $type
     * @phpstan-param array<string, bool|float|int|string|\Stringable|null> $params
     * @phpstan-param array<string, bool|float|int|string|null> $query
     */
    protected function formRedirectWithFlashAndQuery(
        string $routeName,
        string $message,
        string $type = 'info',
        array $params = [],
        array $query = []
    ): RedirectResponse {
        $this->request->session()->setFlashMessage($message, $type);

        return $this->formRedirectWithQuery($routeName, $params, $query);
    }

    /**
     * Plockar standard querystring för listor (q/page) från requesten.
     *
     * @return array<string,string>
     */
    protected function currentListQuery(): array
    {
        $rawQ = $this->request->get['q'] ?? '';
        $q = is_string($rawQ) ? trim($rawQ) : '';

        $rawPage = $this->request->get['page'] ?? 1;
        $page = is_numeric($rawPage) ? (int) $rawPage : 1;

        $query = [];

        if ($q !== '') {
            $query['q'] = $q;
        }

        if ($page > 1) {
            $query['page'] = (string) $page;
        }

        return $query;
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
