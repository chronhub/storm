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
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class MessagingSerializer implements MessageSerializer
{
    private MessagingContentSerializer|ContentSerializer $contentSerializer;

    private readonly Serializer $serializer;

    public function __construct(?ContentSerializer $contentSerializer = null, NormalizerInterface ...$normalizers)
    {
        $this->contentSerializer = $contentSerializer ?? new MessagingContentSerializer();
        $this->serializer = new Serializer($normalizers, [(new JsonSerializer())->getEncoder()]);
    }

    public function serializeMessage(Message $message): array
    {
        $event = $message->event();

        if (! $message->isMessaging()) {
            throw new InvalidArgumentException('Message event '.$event::class.' must be an instance of Messaging to be serialized');
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
