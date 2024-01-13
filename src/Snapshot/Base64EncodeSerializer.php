<?php

declare(strict_types=1);

namespace Chronhub\Storm\Snapshot;

use Chronhub\Storm\Contracts\Aggregate\AggregateRootWithSnapshotting;
use Chronhub\Storm\Contracts\Snapshot\SnapshotSerializer;

use function base64_decode;
use function base64_encode;
use function serialize;
use function unserialize;

final class Base64EncodeSerializer implements SnapshotSerializer
{
    public function serialize(mixed $data): string
    {
        $serialized = serialize($data);

        return base64_encode($serialized);
    }

    public function deserialize(string $serialized): mixed
    {
        $serialized = base64_decode($serialized, true);

        return unserialize($serialized, [AggregateRootWithSnapshotting::class]);
    }
}
