<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Chronhub\Storm\Reporter\DomainEvent;

interface StreamEventSerializer
{
    /**
     * Serialize event
     *
     * A stream persistence strategy should serialize a domain event to array
     * and done in two steps:
     *      - normalize headers and content in a mapping context
     *      - encode headers and content to json string
     *
     * @see StreamPersistence::serialize()
     * @see self::encodePayload()
     *
     * @return array{headers: array, content: array}
     */
    public function serializeEvent(DomainEvent $event): array;

    /**
     * Deserialize payload
     *
     * A stream event loader should deserialize payload to an event sourced
     */
    public function deserializePayload(array $payload): DomainEvent;

    /**
     * Deserialize payload
     *
     * A stream event loader should deserialize an event sourced payload to array
     * Mostly used in an api context to avoid unnecessary deserialization/serialization
     *
     * @return array{headers: array, content: array, no: int}
     */
    public function decodePayload(array $payload): array;

    /**
     * Encode payload
     *
     * Second step of event serialization to serialize headers and content to json string
     */
    public function encodePayload(mixed $data): string;
}
