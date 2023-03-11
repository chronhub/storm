<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamEventSerializer
{
    /**
     * @return array{headers: array, content: array}
     */
    public function serializeEvent(DomainEvent $event): array;

    /**
     * @return Generator{iterable|object}
     */
    public function unserializeContent(array $payload): Generator;

    /**
     * @return array{headers: array, content: array}
     */
    public function normalizeContent(array $payload): array;

    public function encodePayload(mixed $data): string;
}
