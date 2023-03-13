<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use InvalidArgumentException;
use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use function is_a;

final class MessageContentSerializer implements ContentSerializer
{
    public function serialize(Reporting $event): array
    {
        return $event->toContent();
    }

    public function deserialize(string $source, array $payload): Reporting
    {
        if (is_a($source, Reporting::class, true)) {
            return $source::fromContent($payload['content']);
        }

        throw new InvalidArgumentException('Invalid source to deserialize');
    }
}
