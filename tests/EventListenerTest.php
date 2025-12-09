<?php

declare(strict_types=1);

namespace Radix\Tests;

use PHPUnit\Framework\TestCase;
use Radix\Config\Config; // lägg till denna rad
use Radix\EventDispatcher\Event;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Event\ResponseEvent;
use Radix\Http\EventListeners\CacheControlListener;
use Radix\Http\EventListeners\ContentLengthListener;
use Radix\Http\EventListeners\CorsListener;
use Radix\Http\Request;
use Radix\Http\Response;

final class TestStoppableEvent extends Event
{
    /** @var list<string> */
    public array $log = [];
}

class EventListenerTest extends TestCase
{
    public function testCacheControlListenerAddsHeaders(): void
    {
        // Arrange: Skapa Request och Response
        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $server
        ); // Simulera en HTTP-request

        $response = new Response();

        // Skapa ResponseEvent med både Request och Response
        $event = new ResponseEvent($request, $response);

        // Skapa och kör lyssnaren
        $listener = new CacheControlListener();
        $listener($event);

        // Assert: Kontrollera headers med hjälp av getHeaders()
        $this->assertEquals(
            'no-store, must-revalidate, max-age=0',
            $response->getHeaders()['Cache-Control']
        );
        $this->assertEquals('no-cache', $response->getHeaders()['Pragma']);
        $this->assertEquals('0', $response->getHeaders()['Expires']);
    }

    public function testCorsListenerSetsHeader(): void
    {
        // CORS är nu konfigdrivet – bygg en minimal Config bara för detta test
        $config = new Config([
            'cors' => [
                'enabled' => true,
                'paths' => ['/'],           // låt allt matcha i detta test
                'allow_origins' => ['*'],
                'allow_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'allow_headers' => ['Authorization', 'Content-Type', 'X-Requested-With'],
                'expose_headers' => [],
                'max_age' => 600,
                'allow_credentials' => false,
            ],
        ]);

        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );
        $response = new Response();
        $event = new ResponseEvent($request, $response);

        (new CorsListener($config))($event);

        $this->assertEquals('*', $response->getHeaders()['Access-Control-Allow-Origin']);
    }

    public function testContentLengthListenerSetsHeader(): void
    {
        // Arrange: Skapa Request och Response
        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $server
        ); // Simulera en HTTP-request

        $response = new Response();
        $response->setBody("Test body"); // Sätt en body för att kontrollera längd

        // Skapa ResponseEvent med både Request och Response
        $event = new ResponseEvent($request, $response);

        // Act: Kör lyssnaren
        $listener = new ContentLengthListener();
        $listener($event);

        // Assert: Kontrollera att Content-Length-headern är satt korrekt
        $this->assertEquals(
            '9', // Längden av "Test body"
            $response->getHeaders()['Content-Length']
        );
    }

    public function testContentLengthListenerDoesNotOverrideExistingHeader(): void
    {
        // Arrange: Skapa Request och Response
        /** @var array<string, mixed> $server */
        $server = $_SERVER;

        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $server
        ); // Simulera en HTTP-request

        $response = new Response();
        $response->setBody("Test body");
        $response->setHeader("Content-Length", "15"); // Initial längd

        // Skapa ResponseEvent med både Request och Response
        $event = new ResponseEvent($request, $response);

        // Act: Kör lyssnaren
        $listener = new ContentLengthListener();
        $listener($event);

        // Assert: Kontrollera att Content-Length-headern inte har blivit överskriven
        $this->assertEquals(
            '15', // Det tidigare värdet ska inte ha ändrats
            $response->getHeaders()['Content-Length']
        );
    }

    public function testEventDispatcherStopsPropagationForStoppableEvent(): void
    {
        $dispatcher = new EventDispatcher();
        $event      = new TestStoppableEvent();

        // Första lyssnaren loggar och stoppar propagation
        $dispatcher->addListener(TestStoppableEvent::class, function (TestStoppableEvent $e): void {
            $e->log[] = 'first';
            $e->stopPropagation();
        });

        // Andra lyssnaren ska ALDRIG anropas om stopPropagation fungerar
        $dispatcher->addListener(TestStoppableEvent::class, function (TestStoppableEvent $e): void {
            $e->log[] = 'second';
        });

        /** @var TestStoppableEvent $result */
        $result = $dispatcher->dispatch($event);

        $this->assertSame(
            ['first'],
            $result->log,
            'När propagation stoppas ska efterföljande lyssnare inte anropas.'
        );
    }

    public function testGetListenersForEventReturnsRegisteredListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $event      = new TestStoppableEvent();

        $listener1 = function (TestStoppableEvent $e): void {
            $e->log[] = 'one';
        };
        $listener2 = function (TestStoppableEvent $e): void {
            $e->log[] = 'two';
        };

        $dispatcher->addListener(TestStoppableEvent::class, $listener1);
        $dispatcher->addListener(TestStoppableEvent::class, $listener2);

        $listeners = $dispatcher->getListenersForEvent($event);

        // $listeners är redan typad som iterable i EventDispatcher, så en extra
        // assertIsIterable() ger ingen ny information för PHPStan.
        $listenersArray = is_array($listeners) ? $listeners : iterator_to_array($listeners, false);

        $this->assertCount(2, $listenersArray);
        $this->assertSame($listener1, $listenersArray[0]);
        $this->assertSame($listener2, $listenersArray[1]);
    }

    public function testEventDispatcherCallsListeners(): void
    {
        $dispatcher = new EventDispatcher();
        $response = new Response();
        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: []
        );
        $event = new ResponseEvent($request, $response);

        $config = new Config([
            'cors' => [
                'enabled' => true,
                'paths' => ['/'],
                'allow_origins' => ['*'],
                'allow_methods' => ['GET'],
                'allow_headers' => ['Authorization'],
                'expose_headers' => [],
                'max_age' => 0,
                'allow_credentials' => false,
            ],
        ]);

        $dispatcher->addListener(ResponseEvent::class, new CacheControlListener());
        $dispatcher->addListener(ResponseEvent::class, new CorsListener($config));
        $dispatcher->addListener(ResponseEvent::class, new ContentLengthListener());

        $dispatcher->dispatch($event);

        $headers = $response->getHeaders();
        $this->assertEquals('no-store, must-revalidate, max-age=0', $headers['Cache-Control']);
        $this->assertEquals('no-cache', $headers['Pragma']);
        $this->assertEquals('0', $headers['Expires']);
        $this->assertEquals('*', $headers['Access-Control-Allow-Origin']);
    }
}
