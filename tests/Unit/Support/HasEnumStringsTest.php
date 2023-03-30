<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Support\HasEnumStrings;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HasEnumStrings::class)]
class HasEnumStringsTest extends UnitTestCase
{
    public function testReturnEnumAsArrayStrings(): void
    {
        self::assertEquals(['foo', 'bar', 'baz'], SomeBackedEnumStringStub::strings());
    }
}
