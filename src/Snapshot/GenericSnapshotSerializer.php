<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Snapshot\SnapshotSerializer;

use function serialize;
use function unserialize;

final class GenericSnapshotSerializer implements SnapshotSerializer
{
    public function serialize(mixed $data): string
    {
        return serialize($data);
    }

    public function deserialize(string $serialized): mixed
    {
        return unserialize($serialized, [AggregateRootWithSnapshotting::class]);
    }
}
