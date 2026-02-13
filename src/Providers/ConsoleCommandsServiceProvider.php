<?php

declare(strict_types=1);

namespace App\Providers;

use InvalidArgumentException;
use Radix\Config\Config;
use Radix\Console\CommandsRegistry;
use Radix\Container\Contract\ContainerRegistryInterface;
use Radix\ServiceProvider\ServiceProviderInterface;

final readonly class ConsoleCommandsServiceProvider implements ServiceProviderInterface
{
    public function __construct(private ContainerRegistryInterface $container) {}

    public function register(): void
    {
        /** @var Config $config */
        $config = $this->container->get('config');

        /** @var CommandsRegistry $registry */
        $registry = $this->container->get(CommandsRegistry::class);

        $commands = $config->get('commands.commands', []);
        if (!is_array($commands)) {
            throw new InvalidArgumentException("Config 'commands.commands' must be an array.");
        }

        foreach ($commands as $name => $class) {
            if (!is_string($name) || $name === '') {
                continue;
            }
            if (!is_string($class) || $class === '') {
                continue;
            }

            // Valfritt men trevligt: fail fast om klassen inte finns
            if (!class_exists($class)) {
                throw new InvalidArgumentException("Console command class '{$class}' does not exist.");
            }

            /** @var class-string $class */
            $registry->register($name, $class);
        }
    }
}
