<?php

declare(strict_types=1);

namespace App\Providers;

use App\EventListeners\ContentLengthListener;
use Radix\EventDispatcher\EventDispatcher;
use Radix\Http\Event\ResponseEvent;
use Radix\ServiceProvider\ServiceProviderInterface;

class EventServiceProvider implements ServiceProviderInterface
{
    private array $listen = [
        ResponseEvent::class => [
            ContentLengthListener::class,
        ],
    ];

    public function __construct(private readonly EventDispatcher $eventDispatcher)
    {
    }

    public function register(): void
    {
        // loop over each event in the listen array
        foreach ($this->listen as $eventName => $listeners) {
            // loop over each listener
            foreach (array_unique($listeners) as $listener) {
                // call eventDispatcher->addListener
                $this->eventDispatcher->addListener($eventName, new $listener());
            }
        }
    }
}