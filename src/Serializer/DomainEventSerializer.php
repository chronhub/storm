<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Serializer\EventContentSerializer;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;
use Chronhub\Storm\Reporter\DomainEvent;
use InvalidArgumentException;
use Symfony\Component\Serializer\Serializer;

use function is_string;

final readonly class DomainEventSerializer implements StreamEventSerializer
{
    public function __construct(
        private EventContentSerializer $contentSerializer,
        private Serializer $serializer
    ) {
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

    public function deserializePayload(array $payload): DomainEvent
    {
        $headers = $payload['headers'] ?? [];

        if (is_string($headers)) {
            $headers = $this->serializer->decode($headers, 'json');
        }

        $source = $headers[Header::EVENT_TYPE] ?? null;

        if ($source === null) {
            throw new InvalidArgumentException('Missing event type header to deserialize payload');
        }

        $content = $payload['content'] ?? [];

        if (is_string($content)) {
            $content = $this->serializer->decode($content, 'json');
        }

        $event = $this->contentSerializer->deserialize($source, ['headers' => $headers, 'content' => $content]);

        /**
         * Note about 'no' key and internal position header:
         *
         * 'no' represents the position in a sequence of events which can be produced in 2 ways:
         *     - by a stream persistence strategy which is not auto incremented,
         *       so the position is provided by the aggregate version, done in stream persistence context
         *
         *     - by a stream persistence strategy which is auto incremented,
         *
         *  Internal position can exist in headers when a persistent projection (no read model)
         *  have emitted or linked a stream event to a (new) stream event,
         *  so the internal position referred to the 'original' stream event 'no' key
         *  (meaning an event sourced have been deserialized and serialized again)
         */
        if (! isset($headers[EventHeader::INTERNAL_POSITION]) && isset($payload['no'])) {
            $headers[EventHeader::INTERNAL_POSITION] = $payload['no'];
        }

        return $event->withHeaders($headers);
    }

    public function decodePayload(array $payload): array
    {
        if (! isset($payload['headers'], $payload['content'], $payload['no'])) {
            throw new InvalidArgumentException('Missing headers, content and/or no key(s) to decode payload');
        }

        return [
            'no' => $payload['no'],
            'headers' => $this->serializer->decode($payload['headers'], 'json'),
            'content' => $this->serializer->decode($payload['content'], 'json'),
        ];
    }

    public function encodePayload(mixed $data): string
    {
        return $this->serializer->encode($data, 'json');
    }

    /**
     * @internal
     */
    public function getSerializer(): Serializer
    {
        return $this->serializer;
    }
}
