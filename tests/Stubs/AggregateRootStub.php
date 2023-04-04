<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Aggregate\HasAggregateBehaviour;
use Chronhub\Storm\Contracts\Aggregate\AggregateIdentity;
use Chronhub\Storm\Contracts\Aggregate\AggregateRoot;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use function count;

class AggregateRootStub implements AggregateRoot
{
    use HasAggregateBehaviour;

    private int $appliedEvents = 0;

    public static function create(AggregateIdentity $aggregateId, SomeEvent ...$events): self
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
