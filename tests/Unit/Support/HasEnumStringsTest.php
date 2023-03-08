<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Support\HasEnumStrings;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(HasEnumStrings::class)]
class HasEnumStringsTest extends UnitTestCase
{
    #[Test]
    public function it_return_enum_strings(): void
    {
        self::assertEquals(['foo', 'bar', 'baz'], SomeBackedEnumStringStub::strings());
    }
}
