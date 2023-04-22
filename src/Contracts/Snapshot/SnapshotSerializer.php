<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Snapshot;

interface SnapshotSerializer
{
    public function serialize(mixed $data): string;

    public function deserialize(string $serialized): mixed;
}
