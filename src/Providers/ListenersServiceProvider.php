<?php

declare(strict_types=1);

namespace App\Providers;

use Psr\Container\ContainerInterface;
use Radix\ServiceProvider\ServiceProviderInterface;

readonly class ListenersServiceProvider implements ServiceProviderInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function register(): void
    {
        $dispatcher = $this->container->get(\Radix\EventDispatcher\EventDispatcher::class);
        $listeners = require ROOT_PATH . '/config/listeners.php';

        foreach ($listeners as $event => $handlers) {
            foreach ($handlers as $handler) {
                $listener = match ($handler['type']) {
                    'container' => $this->container->get($handler['listener']), // Hämta direkt från containern
                    'custom' => new $handler['listener'](
                        ...array_map(fn($dep) => $this->container->get($dep), $handler['dependencies'] ?? [])
                    ), // Skapa med beroenden
                    default => throw new \RuntimeException("Invalid listener type: {$handler['type']}"),
                };

                // Registrera lyssnaren hos dispatchern
                $dispatcher->addListener(
                    $event,
                    /**
                     * @param object $event
                     */
                    static function (object $event) use ($listener, $handler): void {
                        $listener($event); // Kör lyssnaren

                        // Stoppa propagationen om specificerat
                        if (!empty($handler['stopPropagation']) && $handler['stopPropagation'] === true) {
                            if (method_exists($event, 'stopPropagation')) {
                                $event->stopPropagation();
                            }
                        }
                    },
                    $handler['priority'] ?? 0 // Prioritera lyssnarens körordning
                );
            }
        }
    }
}