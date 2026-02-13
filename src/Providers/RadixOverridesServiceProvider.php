<?php

declare(strict_types=1);

namespace App\Providers;

use Radix\Config\Config;
use Radix\Container\Contract\ContainerRegistryInterface;
use Radix\ServiceProvider\ServiceProviderInterface;
use Radix\Support\StringHelper;
use Radix\Support\Validator;

final readonly class RadixOverridesServiceProvider implements ServiceProviderInterface
{
    public function __construct(private ContainerRegistryInterface $container) {}

    public function register(): void
    {
        /** @var Config $config */
        $config = $this->container->get('config');

        $this->registerPluralizationOverride($config);
        $this->registerValidatorFieldTranslationsOverride($config);
    }

    private function registerPluralizationOverride(Config $config): void
    {
        $plural = $config->get('pluralization');
        if (!is_array($plural)) {
            return;
        }

        /** @var array<string,mixed> $plural */
        StringHelper::setPluralizationConfig($plural);
    }

    private function registerValidatorFieldTranslationsOverride(Config $config): void
    {
        $validations = $config->get('translations.validations');
        if (!is_array($validations)) {
            return;
        }

        /** @var array<string,string> $filtered */
        $filtered = [];
        foreach ($validations as $k => $v) {
            if (is_string($k) && is_string($v)) {
                $filtered[$k] = $v;
            }
        }

        if ($filtered !== []) {
            Validator::setFieldTranslationsConfig($filtered);
        }
    }
}
