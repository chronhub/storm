<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use stdClass;
use InvalidArgumentException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\Double\SomeEvent;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Stubs\V4UniqueIdStub;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Serializer\DomainEventSerializer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use function json_encode;

final class DomainEventSerializerTest extends ProphecyTestCase
{
    /**
     * @test
     */
    public function it_serialize_message_with_scalar_headers(): void
    {
        $aggregateId = V4AggregateId::create();

        $headers = [
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
        ];

        $event = SomeEvent::fromContent(['name' => 'steph bug'])->withHeaders($headers);

        $message = new Message($event);

        $serializer = new DomainEventSerializer();

        $payload = $serializer->serializeMessage($message);

        $this->assertArrayHasKey('headers', $payload);
        $this->assertArrayHasKey('content', $payload);

        $this->assertEquals($headers, $payload['headers']);

        $this->assertEquals(['name' => 'steph bug'], $payload['content']);
    }

    /**
     * @test
     */
    public function it_normalize_and_serialize_headers(): void
    {
        $aggregateId = V4AggregateId::create();
        $datetime = new PointInTime();
        $now = $datetime->now();
        $uid = V4UniqueIdStub::create();

        $event = SomeEvent::fromContent(['name' => 'steph bug']);

        $message = new Message($event, [
            Header::EVENT_ID => $uid,
            Header::EVENT_TIME => $now,
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
        ]);

        $serializer = new DomainEventSerializer(null,
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => $datetime::DATE_TIME_FORMAT,
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]),
            new UidNormalizer()
        );

        $payload = $serializer->serializeMessage($message);

        $this->assertArrayHasKey('headers', $payload);
        $this->assertArrayHasKey('content', $payload);
        $this->assertEquals($payload['headers']['__event_id'], $uid->jsonSerialize());
        $this->assertEquals($payload['headers']['__event_time'], $now->format($datetime::DATE_TIME_FORMAT));
        $this->assertEquals(['name' => 'steph bug'], $payload['content']);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_is_not_an_instance_of_domain_event_during_serialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Message event '.stdClass::class.' must be an instance of Domain event to be serialized');

        (new DomainEventSerializer())->serializeMessage(new Message(new stdClass()));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_header_aggregate_id_is_missing_during_serialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing aggregate id and/or aggregate id type headers');

        (new DomainEventSerializer())->serializeMessage(new Message(SomeEvent::fromContent([])));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_header_aggregate_id_type_is_missing_during_serialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing aggregate id and/or aggregate id type headers');

        $aggregateId = V4AggregateId::create();

        $headers = [EventHeader::AGGREGATE_ID => $aggregateId->toString()];

        $event = SomeEvent::fromContent(['name' => 'steph bug'])->withHeaders($headers);

        (new DomainEventSerializer())->serializeMessage(new Message($event));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_header_aggregate_type_is_missing_during_serialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing aggregate type header');

        $aggregateId = V4AggregateId::create();

        $headers = [
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
        ];

        $event = SomeEvent::fromContent(['name' => 'steph bug'])->withHeaders($headers);

        (new DomainEventSerializer())->serializeMessage(new Message($event));
    }

    /**
     * @test
     */
    public function it_unserialize_payload(): void
    {
        $payload = [
            'headers' => [
                Header::EVENT_TYPE => SomeEvent::class,
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],
        ];

        $serializer = new DomainEventSerializer();

        $event = $serializer->unserializeContent($payload)->current();

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertEquals($payload['headers'], $event->headers());
        $this->assertEquals($payload['content'], $event->toContent());
    }

    /**
     * @test
     */
    public function it_decode_and_unserialize_payload(): void
    {
        $payload = [
            'headers' => [
                Header::EVENT_TYPE => SomeEvent::class,
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],
        ];

        $serializer = new DomainEventSerializer();

        $event = $serializer->unserializeContent(
            ['headers' => json_encode($payload['headers']), 'content' => json_encode($payload['content'])]
        )->current();

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertIsArray($payload['headers']);
        $this->assertIsArray($payload['content']);
    }

    /**
     * @test
     */
    public function it_unserialize_payload_and_add_version_to_payload_as_no(): void
    {
        $payload = [
            'no' => 42,
            'headers' => [
                Header::EVENT_TYPE => SomeEvent::class,
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],

        ];

        $serializer = new DomainEventSerializer();

        $event = $serializer->unserializeContent($payload)->current();

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertEquals($payload['no'], $event->headers()[EventHeader::INTERNAL_POSITION]);
        $this->assertEquals($payload['content'], $event->toContent());
    }

    /**
     * @test
     */
    public function it_does_not_add_version_to_payload_when_internal_position_header_already_exists(): void
    {
        $payload = [
            'no' => 42,
            'headers' => [
                Header::EVENT_TYPE => SomeEvent::class,
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
                EventHeader::INTERNAL_POSITION => 1,
            ],
            'content' => ['name' => 'steph bug'],

        ];

        $serializer = new DomainEventSerializer();

        $event = $serializer->unserializeContent($payload)->current();

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertEquals(1, $event->headers()[EventHeader::INTERNAL_POSITION]);
        $this->assertEquals($payload['content'], $event->toContent());
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_event_type_missing_during_unserialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing event type header to unserialize payload');

        $payload = [
            'headers' => [
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],
        ];

        (new DomainEventSerializer())->unserializeContent($payload)->current();
    }
}
