<?php

declare(strict_types=1);

namespace Radix\Tests\Smoke;

use App\Providers\RadixOverridesServiceProvider;
use PHPUnit\Framework\TestCase;
use Radix\Config\Config;
use Radix\Container\Container;
use Radix\Support\StringHelper;
use Radix\Support\Validator;
use ReflectionClass;

/**
 * Verifierar att RadixOverridesServiceProvider kopplar in
 * translations.validations -> Validator::setFieldTranslationsConfig().
 */
final class RadixOverridesServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        // Städa statiskt state så tester inte påverkar varandra
        Validator::resetFieldTranslationsConfig();

        $ref = new ReflectionClass(StringHelper::class);

        $pCache = $ref->getProperty('irregularCache');
        $pCache->setAccessible(true);
        $pCache->setValue(null, null);

        $pOverride = $ref->getProperty('pluralizationOverride');
        $pOverride->setAccessible(true);
        $pOverride->setValue(null, null);

        Validator::resetFieldTranslationsConfig();

        parent::tearDown();
    }

    public function testProviderWiresValidationFieldTranslationsFromConfig(): void
    {
        $config = new Config([
            'translations' => [
                'validations' => [
                    'email' => 'mejl',
                ],
            ],
        ]);

        $container = new Container();
        $container->addShared('config', $config);

        $provider = new RadixOverridesServiceProvider($container);
        $provider->register();

        $validator = new Validator(data: [], rules: ['email' => 'required']);
        $this->assertFalse($validator->validate());

        $this->assertSame(
            ['Fältet mejl är obligatoriskt.'],
            $validator->errors()['email'] ?? null
        );
    }

    public function testProviderIgnoresNonStringValidationTranslationValues(): void
    {
        $config = new Config([
            'translations' => [
                'validations' => [
                    'email' => 123, // ska ignoreras
                ],
            ],
        ]);

        $container = new Container();
        $container->addShared('config', $config);

        $provider = new RadixOverridesServiceProvider($container);
        $provider->register();

        $validator = new Validator(data: [], rules: ['email' => 'required']);
        $this->assertFalse($validator->validate());

        // Om mutanten (||) lever så släpps email=>123 igenom och då blir fältnamnet "123" i meddelandet.
        $this->assertSame(
            ['Fältet e-post är obligatoriskt.'],
            $validator->errors()['email'] ?? null
        );
    }

    public function testProviderWiresPluralizationOverrideFromConfig(): void
    {
        $config = new Config([
            'pluralization' => [
                'irregular' => [
                    'mouse' => 'mice',
                ],
            ],
        ]);

        $container = new Container();
        $container->addShared('config', $config);

        $provider = new RadixOverridesServiceProvider($container);
        $provider->register();

        // Utan override skulle den enkla regeln ge "mouses".
        $this->assertSame('mice', StringHelper::pluralize('mouse'));
    }

    public function testProviderDoesNotSetValidatorOverrideWhenNoValidValidationTranslationsExist(): void
    {
        $config = new Config([
            'translations' => [
                'validations' => [
                    'email' => 123, // ogiltig (value ej string) => ska inte trigga override alls
                ],
            ],
        ]);

        $container = new Container();
        $container->addShared('config', $config);

        $provider = new RadixOverridesServiceProvider($container);
        $provider->register();

        $ref = new ReflectionClass(Validator::class);
        $p = $ref->getProperty('fieldTranslationsOverride');
        $p->setAccessible(true);

        $this->assertNull(
            $p->getValue(),
            'När inga giltiga string=>string entries finns ska provider inte sätta Validator override (ska vara null).'
        );
    }
}
