<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Stubs\V4UniqueIdStub;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Serializer\ConvertStreamEvent;
use Chronhub\Storm\Serializer\DomainEventSerializer;

final class ConvertDomainEventTest extends UnitTestCase
{
    private array $headers;

    public function setUp(): void
    {
        parent::setUp();

        $datetime = new PointInTime();
        $now = $datetime->now();
        $eventId = V4UniqueIdStub::create();
        $aggregateId = V4AggregateId::create();

        $this->headers = [
            Header::EVENT_TYPE => SomeEvent::class,
            Header::EVENT_ID => $eventId->jsonSerialize(),
            Header::EVENT_TIME => $now->format($datetime::DATE_TIME_FORMAT),
            EventHeader::AGGREGATE_TYPE => SomeEvent::class,
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            EventHeader::AGGREGATE_VERSION => 42,
        ];
    }

    /**
     * @test
     */
    public function it_convert_domain_event_to_array(): void
    {
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $eventConverter = new ConvertStreamEvent(new DomainEventSerializer());

        $map = $eventConverter->toArray($event->withHeaders($this->headers), true);

        $expectedMap = [
            'event_id' => $this->headers[Header::EVENT_ID],
            'event_type' => $this->headers[Header::EVENT_TYPE],
            'aggregate_id' => $this->headers[EventHeader::AGGREGATE_ID],
            'aggregate_type' => $this->headers[EventHeader::AGGREGATE_TYPE],
            'aggregate_version' => $this->headers[EventHeader::AGGREGATE_VERSION],
            'headers' => $this->headers,
            'content' => ['name' => 'steph bug'],
            'created_at' => $this->headers[Header::EVENT_TIME],
        ];

        $this->assertEquals($expectedMap, $map);
    }

    /**
     * @test
     */
    public function it_convert_domain_event_to_array_and_add_version_as_no_in_return_map(): void
    {
        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $eventConverter = new ConvertStreamEvent(new DomainEventSerializer());

        $map = $eventConverter->toArray($event->withHeaders($this->headers), false);

        $expectedMap = [
            'event_id' => $this->headers[Header::EVENT_ID],
            'event_type' => $this->headers[Header::EVENT_TYPE],
            'aggregate_id' => $this->headers[EventHeader::AGGREGATE_ID],
            'aggregate_type' => $this->headers[EventHeader::AGGREGATE_TYPE],
            'aggregate_version' => $this->headers[EventHeader::AGGREGATE_VERSION],
            'no' => $this->headers[EventHeader::AGGREGATE_VERSION],
            'headers' => $this->headers,
            'content' => ['name' => 'steph bug'],
            'created_at' => $this->headers[Header::EVENT_TIME],
        ];

        $this->assertEquals($expectedMap, $map);
    }

    /**
     * @test
     */
    public function it_convert_array_payload_to_domain_event(): void
    {
        $eventConverter = new ConvertStreamEvent(new DomainEventSerializer());

        $payload = ['headers' => $this->headers, 'content' => ['name' => 'steph bug']];

        $event = $eventConverter->toDomainEvent($payload);

        $this->assertInstanceOf(SomeEvent::class, $event);
        $this->assertEquals($this->headers, $event->headers());
        $this->assertEquals(['name' => 'steph bug'], $event->toContent());
    }

    /**
     * @test
     */
    public function it_convert_stdclass_payload_to_domain_event(): void
    {
        $eventConverter = new ConvertStreamEvent(new DomainEventSerializer());

        $payload = (object) ['headers' => $this->headers, 'content' => ['name' => 'steph bug']];

        $event = $eventConverter->toDomainEvent($payload);

        $this->assertInstanceOf(SomeEvent::class, $event);
        $this->assertEquals($this->headers, $event->headers());
        $this->assertEquals(['name' => 'steph bug'], $event->toContent());
    }
}
