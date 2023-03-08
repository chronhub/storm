<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Support;

use Chronhub\Storm\Support\HasEnumStrings;

enum SomeBackedEnumStringStub: string
{
    use HasEnumStrings;

    case FOO = 'foo';
    case BAR = 'bar';
    case BAZ = 'baz';
}
