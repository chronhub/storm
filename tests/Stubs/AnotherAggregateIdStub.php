<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Aggregate\HasAggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final class AnotherAggregateIdStub implements AggregateIdentity
{
    use HasAggregateIdentity;
}
