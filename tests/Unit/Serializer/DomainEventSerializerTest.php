<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Aggregate\V4AggregateId;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Message\EventHeader;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Serializer\DomainEventContentSerializer;
use Chronhub\Storm\Serializer\DomainEventSerializer;
use Chronhub\Storm\Serializer\SerializeToJson;
use Chronhub\Storm\Tests\Stubs\AggregateRootStub;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Stubs\V4UniqueIdStub;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

use function json_encode;

#[CoversClass(DomainEventSerializer::class)]
final class DomainEventSerializerTest extends UnitTestCase
{
    public function testSerializeDomainEvent(): void
    {
        $aggregateId = V4AggregateId::create();

        $headers = [
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
        ];

        $event = SomeEvent::fromContent(['name' => 'steph bug'])->withHeaders($headers);

        $serializer = $this->domainEventSerializerInstance();

        $payload = $serializer->serializeEvent($event);

        $this->assertArrayHasKey('headers', $payload);
        $this->assertArrayHasKey('content', $payload);

        $this->assertEquals($headers, $payload['headers']);

        $this->assertEquals(['name' => 'steph bug'], $payload['content']);
    }

    public function testNormalizeAndSerializeHeaders(): void
    {
        $aggregateId = V4AggregateId::create();
        $datetime = new PointInTime();
        $now = $datetime->now();
        $uid = V4UniqueIdStub::create();

        $event = SomeEvent::fromContent(['name' => 'steph bug'])
            ->withHeaders([
                Header::EVENT_ID => $uid,
                Header::EVENT_TIME => $now,
                EventHeader::AGGREGATE_ID => $aggregateId->toString(),
                EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
                EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
            ]);

        $serializer = $this->domainEventSerializerInstance(
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => $datetime::DATE_TIME_FORMAT,
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]),
            new UidNormalizer()
        );

        $payload = $serializer->serializeEvent($event);

        $this->assertArrayHasKey('headers', $payload);
        $this->assertArrayHasKey('content', $payload);
        $this->assertEquals($payload['headers']['__event_id'], $uid->jsonSerialize());
        $this->assertEquals($payload['headers']['__event_time'], $now->format($datetime::DATE_TIME_FORMAT));
        $this->assertEquals(['name' => 'steph bug'], $payload['content']);
    }

    public function testExceptionRaisedWhenMissingAggregateIdOnSerialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing aggregate id and/or aggregate id type headers');

        $this->domainEventSerializerInstance()->serializeEvent(SomeEvent::fromContent([]));
    }

    public function testExceptionRaisedWhenMissingAggregateIdTypeOnSerialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing aggregate id and/or aggregate id type headers');

        $aggregateId = V4AggregateId::create();

        $headers = [EventHeader::AGGREGATE_ID => $aggregateId->toString()];

        $event = SomeEvent::fromContent(['name' => 'steph bug'])->withHeaders($headers);

        $this->domainEventSerializerInstance()->serializeEvent($event);
    }

    public function testExceptionRaisedWhenMissingAggregateTypeOnSerialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing aggregate type header');

        $aggregateId = V4AggregateId::create();

        $headers = [
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
        ];

        $event = SomeEvent::fromContent(['name' => 'steph bug'])->withHeaders($headers);

        $this->domainEventSerializerInstance()->serializeEvent($event);
    }

    public function testDeserializePayload(): void
    {
        $payload = [
            'headers' => [
                Header::EVENT_TYPE => SomeEvent::class,
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],
        ];

        $event = $this->domainEventSerializerInstance()->deserializePayload($payload);

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertEquals($payload['headers'], $event->headers());
        $this->assertEquals($payload['content'], $event->toContent());
    }

    public function testDecodeAndDeserializePayload(): void
    {
        $payload = [
            'headers' => [
                Header::EVENT_TYPE => SomeEvent::class,
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],
        ];

        $serializer = $this->domainEventSerializerInstance();

        $event = $serializer->deserializePayload(
            ['headers' => json_encode($payload['headers'], JSON_THROW_ON_ERROR), 'content' => json_encode($payload['content'])]
        );

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertIsArray($payload['headers']);
        $this->assertIsArray($payload['content']);
    }

    public function testDeserializePayloadAndAddSequenceNo(): void
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

        $serializer = $this->domainEventSerializerInstance();

        $event = $serializer->deserializePayload($payload);

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertEquals($payload['no'], $event->headers()[EventHeader::INTERNAL_POSITION]);
        $this->assertEquals($payload['content'], $event->toContent());
    }

    public function testInternalPositionNotSetWhenItAlreadyExists(): void
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

        $serializer = $this->domainEventSerializerInstance();

        $event = $serializer->deserializePayload($payload);

        $this->assertInstanceOf(SomeEvent::class, $event);

        $this->assertEquals(1, $event->headers()[EventHeader::INTERNAL_POSITION]);
        $this->assertEquals($payload['content'], $event->toContent());
    }

    public function testExceptionRaisedWhenMissingEventTypeHeaderDuringDeserialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing event type header to deserialize payload');

        $payload = [
            'headers' => [
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],
        ];

        $serializer = $this->domainEventSerializerInstance();

        $serializer->deserializePayload($payload);
    }

    public function testNormalizeContent(): void
    {
        $aggregateId = V4AggregateId::create();

        $headers = [
            EventHeader::AGGREGATE_ID => $aggregateId->toString(),
            EventHeader::AGGREGATE_ID_TYPE => $aggregateId::class,
            EventHeader::AGGREGATE_TYPE => AggregateRootStub::class,
        ];

        $content = ['name' => 'steph bug'];

        $event = SomeEvent::fromContent($content)->withHeaders($headers);

        $serializer = $this->domainEventSerializerInstance();

        $payload = $serializer->serializeEvent($event);

        $payload['headers'] = $serializer->getSerializer()->serialize($payload['headers'], 'json');
        $payload['content'] = $serializer->getSerializer()->serialize($payload['content'], 'json');
        $payload['no'] = 25;

        $normalized = $serializer->decodePayload($payload);

        $this->assertEquals($headers, $normalized['headers']);
        $this->assertEquals($content, $normalized['content']);
        $this->assertEquals(25, $normalized['no']);
    }

    public function testExceptionRaisedWhenMissingHeaderKeyOnDecodingPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing headers, content and/or no key(s) to decode payload');

        $serializer = $this->domainEventSerializerInstance();

        $serializer->decodePayload(['content' => 'some content']);
    }

    public function testExceptionRaisedWhenMissingContentKeyOnDecodingPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing headers, content and/or no key(s) to decode payload');

        $serializer = $this->domainEventSerializerInstance();

        $serializer->decodePayload(['headers' => 'some headers']);
    }

    public function testExceptionRaisedWhenMissingSequenceNoKeyOnDecodingPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Missing headers, content and/or no key(s) to decode payload');

        $serializer = $this->domainEventSerializerInstance();

        $serializer->decodePayload(['headers' => 'some headers', 'content' => 'some content']);
    }

    public function testEncodePayload(): void
    {
        $serializer = $this->domainEventSerializerInstance();

        $this->assertEquals(
            '{"headers":"some headers","content":"some content","no":25}',
            $serializer->encodePayload(['headers' => 'some headers', 'content' => 'some content', 'no' => 25])
        );
    }

    private function domainEventSerializerInstance(NormalizerInterface|DenormalizerInterface ...$normalizers): DomainEventSerializer
    {
        return new DomainEventSerializer(
            new DomainEventContentSerializer(),
            new Serializer(
                $normalizers,
                [(new SerializeToJson())->getEncoder()]
            )
        );
    }
}
