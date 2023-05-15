<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Support\ExtractEventHeader;

final readonly class MatchAggregateGreaterThanVersion implements InMemoryQueryFilter
{
    use ExtractEventHeader;

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
        return $this->extractAggregateIdentity($event)->equalsTo($this->aggregateId)
            && (string) $event->header(EventHeader::AGGREGATE_TYPE) === $this->aggregateType
            && (int) $event->header(EventHeader::AGGREGATE_VERSION) > $this->aggregateVersion;
    }
}
