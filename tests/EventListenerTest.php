<?php

declare(strict_types=1);

namespace Radix\Tests;

use App\EventListeners\CacheControlListener;
use App\EventListeners\ContentLengthListener;
use App\EventListeners\CorsListener;
use PHPUnit\Framework\TestCase;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Event\ResponseEvent;
use Radix\Http\Response;
use Radix\Http\Request;

class EventListenerTest extends TestCase
{
    public function testCacheControlListenerAddsHeaders(): void
    {
        // Arrange: Skapa Request och Response
        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $_SERVER
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
        // Arrange: Skapa Request och Response
        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $_SERVER
        );

        $response = new Response();

        // Skapa ResponseEvent med både Request och Response
        $event = new ResponseEvent($request, $response);

        // Act: Kör lyssnaren
        $listener = new CorsListener();
        $listener($event);

        // Assert: Kontrollera att Access-Control-Allow-Origin-headern är satt
        $this->assertEquals(
            '*',
            $response->getHeaders()['Access-Control-Allow-Origin']
        );
    }

    public function testContentLengthListenerSetsHeader(): void
    {
        // Arrange: Skapa Request och Response
        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $_SERVER
        );

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
        $request = new Request(
            uri: "/test",
            method: "GET",
            get: [],
            post: [],
            files: [],
            cookie: [],
            server: $_SERVER
        );

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
            server: $_SERVER
        );

        $event = new ResponseEvent($request, $response);

        // Lägg till lyssnare i dispatchern
        $dispatcher->addListener(ResponseEvent::class, new CacheControlListener());
        $dispatcher->addListener(ResponseEvent::class, new CorsListener());
        $dispatcher->addListener(ResponseEvent::class, new ContentLengthListener());

        $dispatcher->dispatch($event);

        // Kontrollera att alla headers är inställda korrekt efter dispatcherflödet
        $headers = $response->getHeaders();
        $this->assertEquals('no-store, must-revalidate, max-age=0', $headers['Cache-Control']);
        $this->assertEquals('no-cache', $headers['Pragma']);
        $this->assertEquals('0', $headers['Expires']);
        $this->assertEquals('*', $headers['Access-Control-Allow-Origin']);
    }
}
