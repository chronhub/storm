<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Generator;
use InvalidArgumentException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Contracts\Message\Header;
use Symfony\Component\Serializer\Serializer;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use Chronhub\Storm\Contracts\Serializer\MessageSerializer;

final readonly class MessagingSerializer implements MessageSerializer
{
    public function __construct(private ContentSerializer $contentSerializer,
                                private Serializer $serializer)
    {
    }

    public function serializeMessage(Message $message): array
    {
        $event = $message->event();

        if (! $message->isMessaging()) {
            throw new InvalidArgumentException('Message event '.$event::class.' must be an instance of Reporting to be serialized');
        }

        $headers = $event->headers();

        return [
            'headers' => $this->serializer->normalize($headers, 'json'),
            'content' => $this->contentSerializer->serialize($event),
        ];
    }

    public function unserializeContent(array $payload): Generator
    {
        $headers = $payload['headers'] ?? [];

        $source = $headers[Header::EVENT_TYPE] ?? null;

        if ($source === null) {
            throw new InvalidArgumentException('Missing event type header to unserialize payload');
        }

        $event = $this->contentSerializer->unserialize($source, $payload);

        yield $event->withHeaders($headers);
    }
}
