<?php

declare(strict_types=1);

namespace Radix\Tests\Collection;

use PHPUnit\Framework\TestCase;
use Radix\Collection\Collection;

class CollectionTest extends TestCase
{
    public function testBasicArrayAccessAndCount(): void
    {
        $c = new Collection([1, 2, 3]);
        $this->assertCount(3, $c);
        $this->assertSame(1, $c[0]);
        $c[] = 4;
        $this->assertSame(4, $c[3]);
        unset($c[1]);
        $this->assertFalse(isset($c[1]));
        $this->assertCount(3, $c);
    }

    public function testGetSetAndRemove(): void
    {
        $c = new Collection(['a' => 1]);
        $this->assertSame(1, $c->get('a'));
        $this->assertNull($c->get('missing'));
        $c->set('b', 2);
        $this->assertSame(2, $c->get('b'));
        $removed = $c->remove('a');
        $this->assertSame(1, $removed);
        $this->assertNull($c->get('a'));
    }

    /**
     * add() ska vara publik och returnera true.
     * Mutanter:
     *  - PublicVisibility (protected)
     *  - TrueValue (return false)
     */
    public function testAddIsPublicAndReturnsTrue(): void
    {
        $c = new Collection([]);
        $result = $c->add('x');

        $this->assertTrue($result);
        $this->assertSame(['x'], $c->toArray());
    }

    /**
     * offsetExists ska:
     *  - returnera true för befintliga int- och string-nycklar
     *  - gå via containsKey(), inte bara kortslutas felaktigt.
     * Dödar flera LogicalNot/LogicalAnd/ReturnRemoval-mutanter på rad 73.
     */
    public function testOffsetExistsForValidIntAndStringKeys(): void
    {
        $c = new Collection([10, 'b' => 20]);

        // int-nyckel 0 finns
        $this->assertTrue(isset($c[0]));
        // string-nyckel 'b' finns
        $this->assertTrue(isset($c['b']));

        // icke-existerande giltiga nycklar ska ge false
        $this->assertFalse(isset($c[1]));
        $this->assertFalse(isset($c['missing']));
    }

    public function testFirstLastAndFirstWhere(): void
    {
        $c = new Collection([
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ]);

        $this->assertSame(['id' => 1, 'name' => 'Alice'], $c->first());
        $this->assertSame(['id' => 2, 'name' => 'Bob'], $c->last());
        $this->assertSame(['id' => 2, 'name' => 'Bob'], $c->firstWhere('name', 'Bob'));
        $this->assertNull($c->firstWhere('name', 'Eve'));
    }

    public function testMapFilterRejectReduce(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        // map: vi vet att kollektionen innehåller ints, så vi kan typa v som int
        $mapped = $c->map(fn(int $v, int|string $k): int => $v * 2);
        $this->assertSame([2, 4, 6, 8], $mapped->toArray());

        // filter: officiell signatur är callable(mixed, int|string): bool,
        // så vi tar mixed, men smalnar av med runtime‑check.
        $filtered = $c->filter(function (mixed $v, int|string $k): bool {
            if (!is_int($v)) {
                $this->fail('Expected int value in Collection::filter() test.');
            }
            return $v % 2 === 0;
        });
        $this->assertSame([1 => 2, 3 => 4], $filtered->toArray());

        $rejected = $c->reject(function (mixed $v, int|string $k): bool {
            if (!is_int($v)) {
                $this->fail('Expected int value in Collection::reject() test.');
            }
            return $v <= 2;
        });
        $this->assertSame([2 => 3, 3 => 4], $rejected->toArray());

        $sum = $c->reduce(function (mixed $acc, mixed $v, int|string $k): int {
            if (!is_int($acc)) {
                $this->fail('Expected int accumulator in Collection::reduce() test.');
            }
            if (!is_int($v)) {
                $this->fail('Expected int value in Collection::reduce() test.');
            }
            return $acc + $v;
        }, 0);
        $this->assertSame(10, $sum);
    }

    public function testOnlyExceptUniqueValuesKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 1, 'c' => 2]);

        $only = $c->only(['a', 'c']);
        $this->assertSame(['a' => 1, 'c' => 2], $only->toArray());

        $except = $c->except(['b']);
        $this->assertSame(['a' => 1, 'c' => 2], $except->toArray());

        $unique = $c->unique();
        $this->assertSame(['a' => 1, 'c' => 2], $unique->toArray());

        $vals = $c->values();
        $this->assertSame([1,1,2], $vals->toArray());

        $keys = $c->keys();
        $this->assertSame(['a','b','c'], $keys->toArray());
    }

    public function testPluckOnArraysAndObjects(): void
    {
        $obj = (object) ['id' => 2, 'name' => 'Bob'];
        $c = new Collection([
            ['id' => 1, 'name' => 'Alice'],
            $obj,
        ]);

        $names = $c->pluck('name')->values()->toArray();
        $this->assertSame(['Alice', 'Bob'], $names);

        $namesById = $c->pluck('name', 'id')->toArray();
        $this->assertSame([1 => 'Alice', 2 => 'Bob'], $namesById);
    }

    /**
     * pluck() ska inte försöka läsa saknade objektproperties (dödar LogicalAnd-mutanter).
     * Muterad kod skulle göra property-read även när isset(...) är false,
     * vilket ger notice/varning och gör testet "riskfyllt".
     */
    public function testPluckSkipsMissingObjectPropertyWithoutNotice(): void
    {
        $objWithName = (object) ['id' => 1, 'name' => 'Alice'];
        $objWithoutName = (object) ['id' => 2]; // saknar 'name'

        $c = new Collection([$objWithName, $objWithoutName]);

        $plucked = $c->pluck('name')->toArray();

        // andra elementet saknar 'name' -> ska bli null men utan notice
        $this->assertSame(
            [0 => 'Alice', 1 => null],
            $plucked
        );
    }

    public function testPluckKeyBySkipsMissingObjectPropertyWithoutNotice(): void
    {
        $objWithId = (object) ['id' => 1, 'name' => 'Alice'];
        $objWithoutId = (object) ['name' => 'Bob']; // saknar 'id'

        $c = new Collection([$objWithId, $objWithoutId]);

        $plucked = $c->pluck('name', 'id')->toArray();

        // Det viktiga här är att anropet inte genererar notice/varning
        // när andra objektet saknar 'id'. Vi nöjer oss med att resultatet
        // innehåller 'Bob' som värde.
        $this->assertSame(['Bob'], array_values($plucked));
    }

    public function testClearAndIsEmpty(): void
    {
        $c = new Collection([1]);
        $this->assertFalse($c->isEmpty());
        $c->clear();
        $this->assertTrue($c->isEmpty());
    }

    public function testContainsKeyAndArrayAccessRejectsInvalidKeyTypes(): void
    {
        $c = new Collection(['a' => 1]);
        $this->assertFalse($c->containsKey(false));
        /** @phpstan-ignore-next-line  intentional invalid key type for runtime behaviour */
        $this->assertFalse(isset($c[false]));
        /** @phpstan-ignore-next-line  intentional invalid key type for runtime behaviour */
        $this->assertNull($c[false]);
        /** @phpstan-ignore-next-line  intentional invalid key type for runtime behaviour */
        unset($c[false]); // ska inte kasta
        $this->assertSame(['a' => 1], $c->toArray());
    }

    public function testLastHandlesFalseAtEndCorrectly(): void
    {
        $c = new Collection([1, false]);
        $this->assertFalse($c->last('default'));
        $empty = new Collection([]);
        $this->assertSame('default', $empty->last('default'));
    }

    public function testUniqueStrictVsNonStrict(): void
    {
        $c = new Collection([1, '1', 1]);

        $strict = $c->unique(null, true)->toArray();
        $loose  = $c->unique()->toArray();

        $this->assertSame([0 => 1, 1 => '1'], $strict);
        $this->assertSame([0 => 1], $loose);
    }

    public function testUniqueWithObjectsAndArrays(): void
    {
        $obj1 = (object) ['a' => 1];
        $obj2 = (object) ['a' => 1];
        $arr1 = ['a' => 1];
        $arr2 = ['a' => 1];

        $c = new Collection([$obj1, $obj2, $arr1, $arr2]);
        $unique = $c->unique()->toArray();

        $this->assertCount(2, $unique, 'Objekt och arr med samma innehåll ska dedupliceras.');
    }

    public function testPluckWithNonScalarKeyByFallsBackToIndex(): void
    {
        $c = new Collection([
            ['id' => 1, 'data' => ['x' => 1]],
            ['id' => 2, 'data' => ['x' => 2]],
        ]);

        $plucked = $c->pluck('data', 'data')->toArray();
        // eftersom keyBy 'data' inte är int|string -> ska använda originalindex
        $this->assertSame(
            [0 => ['x' => 1], 1 => ['x' => 2]],
            $plucked
        );
    }

    public function testOnlyAndExceptIgnoreNonIntStringKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        /** @phpstan-ignore-next-line  testar att ogiltiga nycklar ignoreras vid runtime */
        $only = $c->only(['a', false])->toArray();
        $this->assertSame(['a' => 1], $only);
        /** @phpstan-ignore-next-line  testar att ogiltiga nycklar ignoreras vid runtime */
        $except = $c->except(['b', []])->toArray();
        $this->assertSame(['a' => 1, 'c' => 3], $except);
    }

    public function testOffsetAndContainsKeyRejectNonIntStringKeys(): void
    {
        $c = new Collection(['a' => 1]);

        // containsKey
        $this->assertFalse($c->containsKey(false));
        $this->assertFalse($c->containsKey([]));

        /** @phpstan-ignore-next-line  intentional invalid key type for runtime behaviour */
        $this->assertFalse(isset($c[false]));
        /** @phpstan-ignore-next-line  intentional invalid key type for runtime behaviour */
        $this->assertFalse(isset($c[[]]));

        // offsetGet – ska ge null, inte exception
        /** @phpstan-ignore-next-line  intentional invalid key type for runtime behaviour */
        $this->assertNull($c[false]);

        // offsetUnset – ska bara ignorera
        /** @phpstan-ignore-next-line  intentional invalid key type for runtime behaviour */
        unset($c[false]);
        $this->assertSame(['a' => 1], $c->toArray());
    }

    public function testLastHandlesFalseCorrectly(): void
    {
        $c = new Collection([1, false]);
        $this->assertFalse($c->last('default'), 'last ska returnera false om sista elementet är false');

        $empty = new Collection([]);
        $this->assertSame('default', $empty->last('default'), 'last på tom collection ska ge default');
    }

    /**
     * Extra explicit test för last() på tom samling
     * för att säkert döda ReturnRemoval-mutanter på rad 157.
     */
    public function testLastOnEmptyCollectionAlwaysReturnsDefault(): void
    {
        $c = new Collection([]);
        $this->assertSame('fallback', $c->last('fallback'));
    }

    public function testFirstWhereForArraysAndObjects(): void
    {
        $obj1 = (object) ['id' => 2, 'name' => 'Bob'];
        $obj2 = (object) ['id' => 3, 'name' => 'Carol'];
        $objWithoutId = (object) ['name' => 'NoId'];

        $c = new Collection([
            ['id' => 1, 'name' => 'Alice'],
            $obj1,
            $obj2,
            $objWithoutId,
        ]);

        $resArr = $c->firstWhere('id', 1);
        $this->assertSame(['id' => 1, 'name' => 'Alice'], $resArr);

        $resObj = $c->firstWhere('id', 3);
        $this->assertSame($obj2, $resObj);

        // Objekt utan 'id' får inte matcha
        $this->assertNull($c->firstWhere('id', 99));
    }

    public function testUniqueStrictVsNonStrictAndComplexValues(): void
    {
        $obj1 = (object) ['a' => 1];
        $obj2 = (object) ['a' => 1];
        $arr1 = ['a' => 1];
        $arr2 = ['a' => 1];

        $c = new Collection([1, '1', 1, $obj1, $obj2, $arr1, $arr2]);

        $strict = $c->unique(null, true)->toArray();
        $loose  = $c->unique()->toArray();

        // strict: 1 och '1' räknas som olika
        $this->assertSame([0 => 1, 1 => '1', 3 => $obj1, 5 => $arr1], $strict);

        // loose: 1 och '1' slås ihop
        $this->assertSame([0 => 1, 3 => $obj1, 5 => $arr1], $loose);
    }

    /**
     * unique() strikt läge ska skilja mellan olika scalar-värden
     * med samma typ (dödar ArrayItemRemoval-mutanter i strict-delen).
     */
    public function testUniqueStrictKeepsDifferentScalarValues(): void
    {
        $c = new Collection([1, 2, 2]);

        $strict = $c->unique(null, true)->toArray();

        // både 1 och 2 ska finnas kvar, 2 ska dedupliceras
        $this->assertSame([0 => 1, 1 => 2], $strict);
    }

    public function testOnlyAndExceptIgnoreInvalidKeys(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        /** @phpstan-ignore-next-line  testar ogiltig nyckeltyp i only() */
        $only = $c->only(['a', false, 123])->toArray();
        // false är ogiltig nyckeltyp, 123 finns inte som nyckel → bara 'a' ska komma med
        $this->assertSame(['a' => 1], $only);

        /** @phpstan-ignore-next-line  testar ogiltig nyckeltyp i except() */
        $except = $c->except(['b', []])->toArray();
        $this->assertSame(['a' => 1, 'c' => 3], $except);
    }

    /**
     * only() ska fortsätta iterera efter ogiltig nyckel (dödar LogicalNot/LogicalAnd/Continue_-mutanter).
     */
    public function testOnlyContinuesAfterInvalidKey(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        /** @phpstan-ignore-next-line  medvetet ogiltig nyckeltyp för att testa runtime-beteende */
        $only = $c->only(['a', false, 'c'])->toArray();

        // false ska ignoreras, men 'c' får INTE tappas bort
        $this->assertSame(['a' => 1, 'c' => 3], $only);
    }

    /**
     * except() ska också fortsätta efter ogiltig nyckel (dödar Continue_-mutant).
     */
    public function testExceptContinuesAfterInvalidKey(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);

        // ogiltig först, sedan giltig
        /** @phpstan-ignore-next-line  medvetet ogiltig nyckeltyp för att testa runtime-beteende */
        $except = $c->except([false, 'b'])->toArray();

        // 'b' ska tas bort trots att en ogiltig nyckel kom före
        $this->assertSame(['a' => 1, 'c' => 3], $except);
    }
}
