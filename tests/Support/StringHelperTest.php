<?php

declare(strict_types=1);

namespace Radix\Tests\Support;

use PHPUnit\Framework\TestCase;
use Radix\Support\StringHelper;

final class StringHelperTest extends TestCase
{
    public function testSingularizeUsesIrregularMapCaseInsensitive(): void
    {
        // Configen har 'irregular' => ['status' => 'status'].
        // Med korrekt implementation ska 'Status' (case-variant) fortfarande bli 'status'.
        $this->assertSame('status', StringHelper::singularize('Status'));
    }

    public function testPluralizeUsesIrregularMapCaseInsensitive(): void
    {
        // Samma oregelbundna mappning används i pluralize().
        // För 'Status' ska vi få 'status' (inte 'Statuses').
        $this->assertSame('status', StringHelper::pluralize('Status'));
    }
}
