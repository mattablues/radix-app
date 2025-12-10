<?php

declare(strict_types=1);

namespace Radix\Tests\Database\ORM;

use PHPUnit\Framework\TestCase;
use Radix\Database\ORM\Model;

final class ModelMagicSetTest extends TestCase
{
    public function testMagicSetUsesCamelCaseMutator(): void
    {
        $model = new class extends Model {
            protected string $table = 'items';
            /** @var array<int, string> */
            protected array $fillable = ['first_name'];

            public bool $mutatorCalled = false;

            // OBS: namn matchar exakt vad originalkoden bygger: set + ucfirst('first_name') + Attribute
            public function setFirst_nameAttribute(string $value): void
            {
                $this->mutatorCalled = true;
                $this->attributes['first_name'] = strtoupper($value);
            }
        };

        // Simulera __set('firstName', ...) → camel_to_snake('firstName') = 'first_name'
        $model->__set('firstName', 'john');

        $this->assertTrue(
            $model->mutatorCalled,
            '__set ska hitta och anropa setFirst_nameAttribute för egenskapen firstName.'
        );
        $this->assertSame('JOHN', $model->getAttribute('first_name'));
    }
}
