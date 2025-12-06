<?php

declare(strict_types=1);

namespace Radix\Tests\Container;

use ArrayObject;
use PHPUnit\Framework\TestCase;
use Radix\Container\Container;
use Radix\Container\Definition;
use Radix\Container\Exception\ContainerDependencyInjectionException;
use Radix\Container\Reference;
use Radix\Container\Resolver;
use ReflectionClass;
use stdClass;

final class ResolverTest extends TestCase
{
    public function testInvokeMethodsAndPropertiesAndSetResolvedAreExecuted(): void
    {
        $container = new Container();
        $resolver  = new Resolver($container);

        // Låt definitionen peka direkt på vår testklass
        $definition = new Definition(TestServiceWithSetter::class);
        $definition->addMethodCall('setFlag', []);        // inga argument
        $definition->setProperties(['foo' => 'bar']);     // denna metod är inte fluent

        $instance = $resolver->resolve($definition);

        $this->assertInstanceOf(TestServiceWithSetter::class, $instance);
        /** @var TestServiceWithSetter $instance */
        $this->assertTrue($instance->flag, 'Metodanrop via invokeMethods ska ha kört setFlag().');
        $this->assertSame('bar', $instance->foo, 'Property-injektion via invokeProperties ska ha satt foo=bar.');
        $this->assertSame($instance, $definition->getResolved(), 'setResolved ska ha sparat samma instans.');
    }

    public function testInvalidStaticFactoryArrayThrowsDependencyInjectionException(): void
    {
        $container = new Container();
        $resolver  = new Resolver($container);

        // Skapa en valid Definition (concrete måste vara string|object|callable)
        $definition = new Definition(TestServiceWithSetter::class);

        // Sätt privat $factory till en ogiltig factory-array via Reflection:
        $defRef = new ReflectionClass($definition);
        $prop   = $defRef->getProperty('factory');
        $prop->setAccessible(true);
        $prop->setValue($definition, ['NonExistingClass', 'create']);

        // Anropa private createFromFactory() via Reflection
        $resolverRef = new ReflectionClass($resolver);
        $method      = $resolverRef->getMethod('createFromFactory');
        $method->setAccessible(true);

        $this->expectException(ContainerDependencyInjectionException::class);

        // Detta driver igenom villkoret i createFromFactory()
        // och ska kasta ContainerDependencyInjectionException
        $method->invoke($resolver, $definition);
    }

    public function testBuiltinTypeWithDefaultIsNotResolvedFromContainer(): void
    {
        $container = new Container();
        $resolver  = new Resolver($container);

        $definition = new Definition(ServiceWithBuiltinDefault::class);
        $definition->setAutowired(true);

        /** @var ServiceWithBuiltinDefault $instance */
        $instance = $resolver->resolve($definition);

        $this->assertSame(42, $instance->value);
    }

    public function testResolveArgumentsResolvesAllReferencesAndPreservesAllItems(): void
    {
        $container = new Container();
        $resolver  = new Resolver($container);

        $container->add('one', stdClass::class);
        $container->add('two', ArrayObject::class);

        $arguments = [
            new Reference('one'),
            'plain',
            new Reference('two'),
        ];

        $resolved = $this->callResolveArguments($resolver, $arguments);

        $this->assertInstanceOf(stdClass::class, $resolved[0]);
        $this->assertSame('plain', $resolved[1]);
        $this->assertInstanceOf(ArrayObject::class, $resolved[2]);
        $this->assertCount(3, $resolved, 'resolveArguments får inte kapa argumentlistan.');
    }

    /**
     * Anropar private resolveArguments via Reflection.
     *
     * @param array<int|string, mixed> $arguments
     * @return array<int|string, mixed>
     */
    private function callResolveArguments(Resolver $resolver, array $arguments): array
    {
        $refClass = new ReflectionClass($resolver);
        $method   = $refClass->getMethod('resolveArguments');
        $method->setAccessible(true);

        /** @var array<int|string, mixed> $result */
        $result = $method->invoke($resolver, $arguments);

        return $result;
    }
}

/**
 * Hjälpklass för att testa invokeMethods + invokeProperties.
 */
final class TestServiceWithSetter
{
    public bool $flag = false;
    public string $foo = '';

    public function setFlag(): void
    {
        $this->flag = true;
    }
}

/**
 * Hjälpklass: inbyggd typ med default, för att testa resolveDependencies-logiken.
 */
final class ServiceWithBuiltinDefault
{
    public function __construct(public int $value = 42) {}
}
