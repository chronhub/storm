<?php

declare(strict_types=1);

namespace Chronhub\Storm\Serializer;

use stdClass;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Serializer\StreamEventConverter;
use Chronhub\Storm\Contracts\Serializer\StreamEventSerializer;

final readonly class ConvertStreamEvent implements StreamEventConverter
{
    public function __construct(private StreamEventSerializer $serializer)
    {
    }

    public function toArray(DomainEvent $event, bool $isAutoIncremented): array
    {
        $data = $this->serializer->serializeMessage(new Message($event));

        //fixMe json encode
        $normalizedEvent = [
            'event_id' => $data['headers'][Header::EVENT_ID],
            'event_type' => $data['headers'][Header::EVENT_TYPE],
            'aggregate_id' => $data['headers'][EventHeader::AGGREGATE_ID],
            'aggregate_type' => $data['headers'][EventHeader::AGGREGATE_TYPE],
            'aggregate_version' => $data['headers'][EventHeader::AGGREGATE_VERSION],
            'headers' => json_encode($data['headers'], 512, JSON_THROW_ON_ERROR),
            'content' => json_encode($data['content'], 512, JSON_THROW_ON_ERROR),
            'created_at' => $data['headers'][Header::EVENT_TIME],
        ];

        if (! $isAutoIncremented) {
            $normalizedEvent['no'] = $data['headers'][EventHeader::AGGREGATE_VERSION];
        }

        return $normalizedEvent;
    }

    public function toDomainEvent(iterable|stdClass $payload): DomainEvent
    {
        if ($payload instanceof stdClass) {
            $payload = (array) $payload;
        }

        return $this->serializer->unserializeContent($payload)->current();
    }
}
