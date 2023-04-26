<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Reporter\DomainEvent;
use Generator;
use RuntimeException;

trait ReconstituteAggregateFromSnapshot
{
    public function reconstituteFromSnapshotting(Generator $events): ?static
    {
        $self = clone $this;

        if (! $self instanceof AggregateRootWithSnapshotting) {
            throw new RuntimeException('Aggregate must implement '.AggregateRootWithSnapshotting::class);
        }

        if ($self->version() < 1) {
            throw new RuntimeException('Aggregate version must be greater than zero');
        }

        foreach ($events as $event) {
            $self->apply($event);
        }

        return $self;
    }

    abstract protected function apply(DomainEvent $event): void;
}
