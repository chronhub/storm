<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Support\ExtractEventHeader;

final readonly class MatchAggregateBetweenIncludedVersion implements InMemoryQueryFilter
{
    use ExtractEventHeader;

    public function __construct(
        private AggregateIdentity $aggregateId,
        private int $fromVersion,
        private int $toVersion
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
        if (! $this->extractAggregateIdentity($event)->equalsTo($this->aggregateId)) {
            return false;
        }

        $currentPosition = (int) $event->header(EventHeader::AGGREGATE_VERSION);

        return $currentPosition >= $this->fromVersion && $currentPosition <= $this->toVersion;
    }
}
