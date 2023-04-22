<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class Snapshot
{
    public function __construct(
        public string $aggregateType,
        public string $aggregateId,
        public AggregateRootWithSnapshotting $aggregateRoot,
        public int $lastVersion,
        public DateTimeImmutable $createdAt
    ) {
        if ($lastVersion < 1) {
            throw new InvalidArgumentException(
                "Aggregate version must be greater or equal than 1, current is $lastVersion"
            );
        }
    }
}
