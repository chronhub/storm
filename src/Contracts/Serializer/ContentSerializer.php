<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

interface ContentSerializer
{
    /**
     * Serialize Message event
     */
    public function serialize(object $event): array;

    /**
     * Unserialize Message event
     */
    public function unserialize(string $source, array $payload): object;
}
