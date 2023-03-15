<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs\Double;

use Chronhub\Storm\Reporter\DomainQuery;
use Chronhub\Storm\Message\HasConstructableContent;

final class SomeQuery extends DomainQuery
{
    use HasConstructableContent;
}
