<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Chronicler\InMemoryQueryFilter;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Reporter\ExtractEventHeader;
use DomainException;

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

        $eventVersion = (int) $event->header(EventHeader::AGGREGATE_VERSION);

        if ($eventVersion < 1) {
            throw new DomainException("Aggregate version must be greater or equal than 1, current is $eventVersion");
        }

        return $eventVersion >= $this->fromVersion && $eventVersion <= $this->toVersion;
    }
}
