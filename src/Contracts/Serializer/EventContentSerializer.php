<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Serializer;

use Chronhub\Storm\Reporter\DomainEvent;

interface EventContentSerializer
{
    /**
     * Serialize Domain event
     */
    public function serialize(DomainEvent $event): array;

    /**
     * Deserialize Domain event
     */
    public function deserialize(string $source, array $payload): DomainEvent;
}
