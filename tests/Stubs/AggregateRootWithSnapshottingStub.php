<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Aggregate\HasAggregateBehaviour;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Snapshot\ReconstituteAggregateFromSnapshot;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use function count;

class AggregateRootWithSnapshottingStub implements AggregateRootWithSnapshotting
{
    use HasAggregateBehaviour;
    use ReconstituteAggregateFromSnapshot;

    private int $appliedEvents = 0;

    public static function create(AggregateIdentity $aggregateId, SomeEvent ...$events): AggregateRootWithSnapshotting
    {
        $aggregateRoot = new self($aggregateId);

        foreach ($events as $event) {
            $aggregateRoot->recordThat($event);
        }

        return $aggregateRoot;
    }

    public function recordSomeEvents(SomeEvent ...$events): void
    {
        foreach ($events as $event) {
            $this->recordThat($event);
        }
    }

    public function countRecordedEvents(): int
    {
        return count($this->recordedEvents);
    }

    public function getAppliedEvents(): int
    {
        return $this->appliedEvents;
    }

    protected function applySomeEvent(SomeEvent $event): void
    {
        $this->appliedEvents++;
    }
}
