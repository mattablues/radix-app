<?php

declare(strict_types=1);

namespace Radix\Tests\Database\ORM;

use PHPUnit\Framework\TestCase;
use Radix\Database\Connection;
use Radix\Database\ORM\Model;
use Radix\Database\ORM\Relationships\BelongsTo;

final class BelongsToUcfirstConventionTest extends TestCase
{
    public function testBelongsToTableConventionUsesUcfirstSingularizedModelName(): void
    {
        // Skapa App\Models\Foo om den inte redan finns
        if (!class_exists('App\\Models\\Foo', false)) {
            eval('namespace App\\Models; final class Foo extends \Radix\Database\ORM\Model { protected string $table = "foos"; }');
        }

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(null);

        $parent = new class extends Model {
            protected string $table = 'parents';
            /** @var array<int, string> */
            protected array $fillable = ['foo_id'];
        };
        $parent->forceFill(['foo_id' => 123]);

        // Viktigt: relatedModelOrTable är tabellnamn (inte FQCN) => går via ucFirst(singularize())
        $rel = new BelongsTo(
            $connection,
            'foos',
            'foo_id',
            'id',
            $parent,
            null
        );

        // Kör get() för att trigga resolveModelClassFromTable()
        $this->assertNull($rel->get());
    }

    public function testBelongsToFallbackConventionUsesUcfirstSingularizedModelName(): void
    {
        // Skapa App\Models\Foo (men INTE App\Models\foo)
        if (!class_exists('App\\Models\\Foo', false)) {
            eval('namespace App\\Models; final class Foo extends \Radix\Database\ORM\Model { protected string $table = "foos"; }');
        }

        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn(null);

        $parent = new class extends Model {
            protected string $table = 'parents';
            /** @var array<int, string> */
            protected array $fillable = ['foo_id'];
        };
        $parent->forceFill(['foo_id' => 1]);

        $rel = new BelongsTo(
            $connection,
            'foos',     // tabellnamn => fallback-konvention ska användas
            'foo_id',
            'id',
            $parent,
            null        // <- viktigt: resolver=null så att fallback körs
        );

        // Om ucfirst tas bort försöker den ladda App\Models\foo och kastar innan fetchOne.
        $this->assertNull($rel->get());
    }
}
