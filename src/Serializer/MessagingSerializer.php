<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Reporter\Reporting;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;
use Chronhub\Storm\Message\Message;
use InvalidArgumentException;
use Symfony\Component\Serializer\Serializer;

final readonly class MessagingSerializer implements MessageSerializer
{
    public function __construct(
        private ContentSerializer $contentSerializer,
        private Serializer $serializer
    ) {
    }

    public function serializeMessage(Message $message): array
    {
        $event = $message->event();

        if (! $event instanceof Reporting) {
            throw new InvalidArgumentException(
                'Message event '.$event::class.' must be an instance of Reporting to be serialized'
            );
        }

        $headers = $event->headers();

        return [
            'headers' => $this->serializer->normalize($headers, 'json'),
            'content' => $this->contentSerializer->serialize($event),
        ];
    }

    public function deserializePayload(array $payload): Reporting
    {
        $headers = $payload['headers'] ?? [];

        $source = $headers[Header::EVENT_TYPE] ?? null;

        if ($source === null) {
            throw new InvalidArgumentException('Missing event type header to deserialize payload');
        }

        $event = $this->contentSerializer->deserialize($source, $payload);

        return $event->withHeaders($headers);
    }
}
