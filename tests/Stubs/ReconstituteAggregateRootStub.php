<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Aggregate\ReconstituteAggregate;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Stream\StreamProducer;
use Chronhub\Storm\Contracts\Chronicler\QueryFilter;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Contracts\Aggregate\AggregateType;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;

final class ReconstituteAggregateRootStub
{
    use ReconstituteAggregate;

    public function __construct(protected readonly Chronicler $chronicler,
                                protected readonly StreamProducer $streamProducer,
                                protected readonly AggregateType $aggregateType)
    {
    }

    public function reconstitute(AggregateIdentity $aggregateId,
                                 ?QueryFilter $queryFilter = null): ?AggregateRoot
    {
        return $this->reconstituteAggregateRoot($aggregateId, $queryFilter);
    }
}
