<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Aggregate\HasAggregateBehaviour;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final class AnotherAggregateRootStub implements AggregateRoot
{
    use HasAggregateBehaviour;

    public static function create(AggregateIdentity $aggregateId, SomeEvent ...$events): self
    {
        $aggregateRoot = new self($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->recordThat($event);
        }

        return $aggregateRoot;
    }
}
