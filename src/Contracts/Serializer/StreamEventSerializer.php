<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Chronhub\Storm\Reporter\DomainEvent;

interface StreamEventSerializer
{
    /**
     * @return array{headers: array, content: array}
     */
    public function serializeEvent(DomainEvent $event): array;

    public function deserializePayload(array $payload): DomainEvent;

    /**
     * @return array{headers: array, content: array, no: int}
     */
    public function decodePayload(array $payload): array;

    public function encodePayload(mixed $data): string;
}
