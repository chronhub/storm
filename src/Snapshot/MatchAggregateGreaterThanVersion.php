<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;

final readonly class MatchAggregateGreaterThanVersion implements InMemoryQueryFilter
{
    use ExtractAggregateIdFromHeader;

    public function __construct(
        private AggregateIdentity $aggregateId,
        private string $aggregateType,
        private int $aggregateVersion,
    ) {
    }

    public function apply(): callable
    {
        return fn (DomainEvent $event): ?DomainEvent => $this->match($event) ? $event : null;
    }

    public function orderBy(): string
    {
        return 'asc';
    }

    private function match(DomainEvent $event): bool
    {
        return $this->extractAggregateId($event)->equalsTo($this->aggregateId)
            && (string) $event->header(EventHeader::AGGREGATE_TYPE) === $this->aggregateType
            && (int) $event->header(EventHeader::AGGREGATE_VERSION) > $this->aggregateVersion;
    }
}
