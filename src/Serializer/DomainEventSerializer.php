<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Generator;
use InvalidArgumentException;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Symfony\Component\Serializer\Serializer;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function is_string;

final class DomainEventSerializer implements StreamEventSerializer
{
    private MessagingContentSerializer|ContentSerializer $contentSerializer;

    private readonly Serializer $serializer;

    public function __construct(?ContentSerializer $contentSerializer = null, NormalizerInterface ...$normalizers)
    {
        $this->contentSerializer = $contentSerializer ?? new MessagingContentSerializer();

        $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION;

        $encoder = new JsonEncoder(
            new JsonEncode([JsonEncode::OPTIONS => $jsonFlags]),
            new JsonDecode([JsonDecode::OPTIONS => JSON_BIGINT_AS_STRING])
        );

        $this->serializer = new Serializer($normalizers, [$encoder]);
    }

    public function serializeEvent(DomainEvent $event): array
    {
        $headers = $event->headers();

        if (! isset($headers[EventHeader::AGGREGATE_ID], $headers[EventHeader::AGGREGATE_ID_TYPE])) {
            throw new InvalidArgumentException('Missing aggregate id and/or aggregate id type headers');
        }

        if (! isset($headers[EventHeader::AGGREGATE_TYPE])) {
            throw new InvalidArgumentException('Missing aggregate type header');
        }

        return [
            'headers' => $this->serializer->normalize($headers, 'json'),
            'content' => $this->contentSerializer->serialize($event),
        ];
    }

    public function unserializeContent(array $payload): Generator
    {
        $headers = $payload['headers'] ?? [];

        if (is_string($headers)) {
            $headers = $this->serializer->decode($headers, 'json');
        }

        $source = $headers[Header::EVENT_TYPE] ?? null;

        if ($source === null) {
            throw new InvalidArgumentException('Missing event type header to unserialize payload');
        }

        $content = $payload['content'] ?? [];

        if (is_string($content)) {
            $content = $this->serializer->decode($content, 'json');
        }

        $event = $this->contentSerializer->unserialize($source, ['headers' => $headers, 'content' => $content]);

        if (! isset($headers[EventHeader::INTERNAL_POSITION]) && isset($payload['no'])) {
            $headers[EventHeader::INTERNAL_POSITION] = $payload['no'];
        }

        yield $event->withHeaders($headers);
    }

    public function encodePayload(mixed $data): string
    {
        return $this->serializer->encode($data, 'json');
    }
}
