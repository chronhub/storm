<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\InMemory;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;

final readonly class RetrieveAllInMemoryQueryFilter implements InMemoryQueryFilter
{
    public function __construct(private AggregateIdentity $aggregateId,
                                private string $direction)
    {
    }

    public function apply(): callable
    {
        return function (DomainEvent $event): ?DomainEvent {
            $currentAggregateId = (string) $event->header(EventHeader::AGGREGATE_ID);

            return $currentAggregateId === $this->aggregateId->toString() ? $event : null;
        };
    }

    public function orderBy(): string
    {
        return $this->direction;
    }
}
