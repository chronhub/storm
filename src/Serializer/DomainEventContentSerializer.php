<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Chronhub\Storm\Contracts\Serializer\EventContentSerializer;
use Chronhub\Storm\Reporter\DomainEvent;
use InvalidArgumentException;
use function is_a;

class DomainEventContentSerializer implements EventContentSerializer
{
    public function serialize(DomainEvent $event): array
    {
        return $event->toContent();
    }

    public function deserialize(string $source, array $payload): DomainEvent
    {
        if (is_a($source, DomainEvent::class, true)) {
            return $source::fromContent($payload['content']);
        }

        throw new InvalidArgumentException('Invalid source to deserialize');
    }
}
