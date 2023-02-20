<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Generator;
use Chronhub\Storm\Reporter\DomainEvent;

interface StreamEventSerializer
{
    public function serializeEvent(DomainEvent $event): array;

    /**
     * @return Generator{iterable|object}
     */
    public function unserializeContent(array $payload): Generator;

    public function encodePayload(mixed $data): string;
}
