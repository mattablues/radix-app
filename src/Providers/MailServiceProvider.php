<?php

declare(strict_types=1);

namespace App\Providers;

use InvalidArgumentException;
use Radix\Config\Config;
use Radix\Container\Contract\ContainerRegistryInterface;
use Radix\Mailer\MailerInterface;
use Radix\Mailer\MailManager;
use Radix\ServiceProvider\ServiceProviderInterface;
use Radix\Viewer\TemplateViewerInterface;

final readonly class MailServiceProvider implements ServiceProviderInterface
{
    public function __construct(private ContainerRegistryInterface $container) {}

    public function register(): void
    {
        // Registrera MailManager med appens valda mailer
        $this->container->add(MailManager::class, function () {
            /** @var Config $config */
            $config = $this->container->get('config');

            /** @var TemplateViewerInterface $viewer */
            $viewer = $this->container->get(TemplateViewerInterface::class);

            // valfritt: om du vill att MailManager::createDefault ska vara enda vägen
            return MailManager::createDefault($viewer, $config);
        });

        // Om du vill kunna injicera MailerInterface direkt också:
        $this->container->add(MailerInterface::class, function () {
            /** @var Config $config */
            $config = $this->container->get('config');

            /** @var TemplateViewerInterface $viewer */
            $viewer = $this->container->get(TemplateViewerInterface::class);

            $mailerClass = $config->get('mail.mailer_class');
            if (!is_string($mailerClass) || $mailerClass === '') {
                throw new InvalidArgumentException("Config 'mail.mailer_class' must be a non-empty class-string.");
            }
            if (!class_exists($mailerClass) || !is_subclass_of($mailerClass, MailerInterface::class)) {
                throw new InvalidArgumentException("Mailer class '{$mailerClass}' must implement " . MailerInterface::class . '.');
            }

            /** @var class-string<MailerInterface> $mailerClass */
            return new $mailerClass($viewer, $config);
        });
    }
}
