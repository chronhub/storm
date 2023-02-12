<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

interface ContentSerializer
{
    /**
     * Serialize Message event
     *
     * @param  object  $event
     * @return array
     */
    public function serialize(object $event): array;

    /**
     * Unserialize Message event
     *
     * @param  string  $source
     * @param  array  $payload
     * @return object
     */
    public function unserialize(string $source, array $payload): object;
}
