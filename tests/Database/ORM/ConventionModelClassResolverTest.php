<?php

declare(strict_types=1);

namespace Radix\Tests\Database\ORM;

use PHPUnit\Framework\TestCase;
use Radix\Database\ORM\ConventionModelClassResolver;

final class ConventionModelClassResolverTest extends TestCase
{
    public function testResolveTrimsNamespaceAndAddsTrailingBackslash(): void
    {
        $resolver = new ConventionModelClassResolver("  App\\Models  ");

        $this->assertSame(
            'App\\Models\\User',
            $resolver->resolve('users')
        );
    }

    public function testResolveUppercasesSingularizedClassName(): void
    {
        $resolver = new ConventionModelClassResolver('App\\Models\\');

        $this->assertSame(
            'App\\Models\\User',
            $resolver->resolve('users')
        );
    }

    public function testResolveReturnsFqcnUnchanged(): void
    {
        $resolver = new ConventionModelClassResolver('App\\Models\\');

        $this->assertSame(
            'Any\\Already\\Qualified\\ClassName',
            $resolver->resolve('Any\\Already\\Qualified\\ClassName')
        );
    }
}
