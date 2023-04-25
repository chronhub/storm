<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Generator;
use RuntimeException;
use function method_exists;

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

        if (! method_exists($self, 'apply')) {
            throw new RuntimeException('Method apply not found in aggregate root with snapshotting '.static::class);
        }

        foreach ($events as $event) {
            $self->apply($event);
        }

        return $self;
    }
}
