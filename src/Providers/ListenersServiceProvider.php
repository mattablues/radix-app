<?php

declare(strict_types=1);

namespace App\Providers;

use Psr\Container\ContainerInterface;
use Radix\ServiceProvider\ServiceProviderInterface;
use RuntimeException;

readonly class ListenersServiceProvider implements ServiceProviderInterface
{
    public function __construct(private ContainerInterface $container) {}

    public function register(): void
    {
        $dispatcher = $this->container->get(\Radix\EventDispatcher\EventDispatcher::class);
        /** @var \Radix\EventDispatcher\EventDispatcher $dispatcher */

        $listeners = require ROOT_PATH . '/config/listeners.php';

        if (!is_array($listeners)) {
            throw new RuntimeException('Config file config/listeners.php must return an array.');
        }

        // Ladda optional preset-listeners (install = filen finns)
        foreach (['auth', 'admin', 'contact'] as $preset) {
            $file = ROOT_PATH . '/config/listeners.' . $preset . '.php';

            if (!is_file($file)) {
                continue;
            }

            /** @phpstan-ignore-next-line require.fileNotFound optional scaffolded config */
            $extra = require $file;

            if (!is_array($extra)) {
                throw new RuntimeException(sprintf(
                    'Config file config/listeners.%s.php must return an array.',
                    $preset
                ));
            }

            // Merge: preset kan lägga till nya events eller ersätta/utöka handlers
            $listeners = array_replace_recursive($listeners, $extra);
        }

        // ... befintlig loop som registrerar handlers ...
        foreach ($listeners as $event => $handlers) {
            if (!is_string($event)) {
                throw new RuntimeException('Event name keys in config/listeners.php must be strings (usually class-string).');
            }

            if (!is_array($handlers)) {
                throw new RuntimeException("Handlers for event '$event' must be an array.");
            }

            foreach ($handlers as $handler) {
                if (!is_array($handler) || !isset($handler['type'], $handler['listener'])) {
                    throw new RuntimeException("Each handler for event '$event' must be an array with at least 'type' and 'listener' keys.");
                }

                $type = $handler['type'];
                $listenerId = $handler['listener'];

                if (!is_string($type) || !is_string($listenerId)) {
                    throw new RuntimeException("Handler 'type' and 'listener' for event '$event' must be strings.");
                }

                /** @var list<string> $deps */
                $deps = [];
                if (isset($handler['dependencies'])) {
                    $rawDeps = $handler['dependencies'];
                    if (is_array($rawDeps)) {
                        $deps = array_values(array_filter($rawDeps, 'is_string'));
                    }
                }

                $listener = match ($type) {
                    'container' => $this->container->get($listenerId),
                    'custom' => new $listenerId(
                        ...array_map(
                            fn(string $dep) => $this->container->get($dep),
                            $deps
                        )
                    ),
                    default => throw new RuntimeException("Invalid listener type: {$type}"),
                };

                assert(is_callable($listener));

                $dispatcher->addListener(
                    $event,
                    static function (object $event) use ($listener, $handler): void {
                        $listener($event);

                        if (!empty($handler['stopPropagation']) && $handler['stopPropagation'] === true) {
                            if (method_exists($event, 'stopPropagation')) {
                                $event->stopPropagation();
                            }
                        }
                    }
                );
            }
        }
    }
}
