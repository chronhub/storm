<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler\InMemory;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Support\ExtractEventHeader;

final readonly class RetrieveAllInMemoryQueryFilter implements InMemoryQueryFilter
{
    use ExtractEventHeader;

    public function __construct(
        private AggregateIdentity $aggregateId,
        private string $direction
    ) {
    }

    public function apply(): callable
    {
        return function (DomainEvent $event): ?DomainEvent {
            return $this->extractAggregateIdentity($event)->equalsTo($this->aggregateId) ? $event : null;
        };
    }

    public function orderBy(): string
    {
        return $this->direction;
    }
}
