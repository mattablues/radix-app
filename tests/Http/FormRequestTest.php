<?php

declare(strict_types=1);

namespace Radix\Tests\Http;

use PHPUnit\Framework\TestCase;
use Radix\Container\ApplicationContainer;
use Radix\Container\Container;
use Radix\Http\FormRequest;
use Radix\Http\Request;
use Radix\Session\SessionInterface;

/**
 * En lokal dummy-klass för att testa bas-funktionaliteten i FormRequest
 * utan honeypot-hantering.
 */
class TestFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email',
        ];
    }

    public function name(): string
    {
        $value = $this->data['name'] ?? '';
        return is_string($value) ? $value : '';
    }
}

/**
 * En klass för att testa honeypot-funktionaliteten
 * (simulerar RegisterRequest-beteende)
 */
class TestFormRequestWithHoneypot extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email',
        ];
    }

    protected function addExtraRules(array $rules): array
    {
        $honeypotId = $this->request->session()->get('honeypot_id');
        if (is_string($honeypotId) && $honeypotId !== '') {
            $rules[$honeypotId] = 'honeypot';
        }

        return $rules;
    }

    protected function handleValidationErrors(): void
    {
        $honeypotId = $this->request->session()->get('honeypot_id');

        if (!is_string($honeypotId) || $honeypotId === '') {
            return;
        }

        $honeypotErrors = preg_grep('/^hp_/', array_keys($this->validator->errors()));

        if (!empty($honeypotErrors)) {
            $this->validator->addError('form-error', 'Det verkar som att du försöker skicka spam. Försök igen.');
        }
    }

    public function name(): string
    {
        $value = $this->data['name'] ?? '';
        return is_string($value) ? $value : '';
    }
}

/**
 * Spy-request som bevisar att handleValidationErrors()
 * faktiskt anropas (och att override fungerar).
 */
class TestFormRequestErrorHookSpy extends FormRequest
{
    public bool $errorHookCalled = false;

    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
        ];
    }

    protected function handleValidationErrors(): void
    {
        $this->errorHookCalled = true;
    }
}

class FormRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ApplicationContainer::reset();
        $container = new Container();
        ApplicationContainer::set($container);
    }

    protected function tearDown(): void
    {
        ApplicationContainer::reset();
        parent::tearDown();
    }

    public function testFormRequestValidatesInputCorrectly(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('honeypot_id')->willReturn(null);

        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: [
                'name' => 'Mats',
                'email' => 'test@example.com',
            ],
            files: [],
            cookie: [],
            server: []
        );
        $request->setSession($session);

        $form = new TestFormRequest($request);

        $this->assertTrue($form->validate());
        $this->assertSame('Mats', $form->name());
        $this->assertEmpty($form->errors());
    }

    public function testFormRequestFailsValidationOnInvalidData(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: [
                'name' => 'Jo', // För kort (min:3)
                'email' => 'not-an-email',
            ],
            files: [],
            cookie: [],
            server: []
        );
        $request->setSession($session);

        $form = new TestFormRequest($request);

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('name', $form->errors());
        $this->assertArrayHasKey('email', $form->errors());
    }

    public function testValidatedReturnsOnlyDefinedFields(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: [
                'name' => 'Mats',
                'email' => 'test@example.com',
                'extra_field' => 'should-be-filtered-out',
            ],
            files: [],
            cookie: [],
            server: []
        );
        $request->setSession($session);

        $form = new TestFormRequest($request);

        $validated = $form->validated();

        $this->assertSame('Mats', $validated['name'] ?? null);
        $this->assertSame('test@example.com', $validated['email'] ?? null);

        $this->assertArrayNotHasKey('extra_field', $validated);
    }

    public function testFormRequestSkipsHoneypotIfEmptyStringOrNull(): void
    {
        $session = $this->createMock(SessionInterface::class);

        foreach (['', null] as $invalidHoneypot) {
            $session->method('get')->with('honeypot_id')->willReturn($invalidHoneypot);

            $request = new Request(
                uri: '/test',
                method: 'POST',
                get: [],
                post: ['name' => 'Mats', 'email' => 'test@example.com'],
                files: [],
                cookie: [],
                server: []
            );
            $request->setSession($session);

            $form = new TestFormRequestWithHoneypot($request);

            $this->assertTrue($form->validate(), "Misslyckades vid honeypot-värde: " . var_export($invalidHoneypot, true));
            $this->assertCount(0, $form->errors());
        }
    }

    public function testFormRequestIncludesHoneypotIfPresentInSession(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('honeypot_id')->willReturn('hp_field');

        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: [
                'name' => 'Mats',
                'email' => 'test@example.com',
                'hp_field' => 'i-am-a-bot',
            ],
            files: [],
            cookie: [],
            server: []
        );
        $request->setSession($session);

        $form = new TestFormRequestWithHoneypot($request);

        $this->assertFalse($form->validate());
        $this->assertArrayHasKey('hp_field', $form->errors());
    }

    public function testFormRequestSkipsHoneypotIfNonStringValue(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('honeypot_id')->willReturn(12345);

        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: ['name' => 'Mats', 'email' => 'test@example.com'],
            files: [],
            cookie: [],
            server: []
        );
        $request->setSession($session);

        $form = new TestFormRequestWithHoneypot($request);

        $this->assertTrue($form->validate());
        $this->assertArrayNotHasKey('12345', $form->errors());
    }

    public function testHandleValidationErrorsIsCalledOnlyWhenValidationFails(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: [
                'name' => 'Jo', // min:3 => fail
            ],
            files: [],
            cookie: [],
            server: []
        );
        $request->setSession($session);

        $form = new TestFormRequestErrorHookSpy($request);

        $this->assertFalse($form->validate(), 'validate() ska misslyckas när name är för kort.');
        $this->assertTrue($form->errorHookCalled, 'handleValidationErrors() ska anropas när validate() misslyckas.');
    }

    public function testHandleValidationErrorsIsNotCalledWhenValidationPasses(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $request = new Request(
            uri: '/test',
            method: 'POST',
            get: [],
            post: [
                'name' => 'Mats', // ok
            ],
            files: [],
            cookie: [],
            server: []
        );
        $request->setSession($session);

        $form = new TestFormRequestErrorHookSpy($request);

        $this->assertTrue($form->validate(), 'validate() ska lyckas när name uppfyller reglerna.');
        $this->assertFalse($form->errorHookCalled, 'handleValidationErrors() ska INTE anropas när validate() lyckas.');
    }
}
