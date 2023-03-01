<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use InvalidArgumentException;
use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use function is_a;

final class MessagingContentSerializer implements ContentSerializer
{
    public function serialize(object $event): array
    {
        if (! $event instanceof Reporting) {
            throw new InvalidArgumentException('Message event '.$event::class.' must be an instance of Reporting to be serialized');
        }

        return $event->toContent();
    }

    public function unserialize(string $source, array $payload): object
    {
        if (is_a($source, Reporting::class, true)) {
            return $source::fromContent($payload['content']);
        }

        throw new InvalidArgumentException('Invalid source to unserialize');
    }
}
