<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use stdClass;
use InvalidArgumentException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\V4UniqueIdStub;
use Chronhub\Storm\Serializer\MessagingSerializer;
use Chronhub\Storm\Contracts\Serializer\ContentSerializer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

final class MessagingSerializerTest extends ProphecyTestCase
{
    /**
     * @test
     */
    public function it_serialize_message_with_scalar_headers(): void
    {
        $command = SomeCommand::fromContent(['name' => 'steph bug'])->withHeaders([
            Header::EVENT_ID => '123-456',
            Header::EVENT_TIME => 'some_time',
        ]);

        $message = new Message($command);

        $serializer = new MessagingSerializer();

        $payload = $serializer->serializeMessage($message);

        $this->assertArrayHasKey('headers', $payload);
        $this->assertArrayHasKey('content', $payload);

        $this->assertEquals(
            [
                '__event_id' => '123-456',
                '__event_time' => 'some_time',
            ],
            $payload['headers']
        );

        $this->assertEquals(['name' => 'steph bug'], $payload['content']);
    }

    /**
     * @test
     */
    public function it_serialize_content_message(): void
    {
        $command = SomeCommand::fromContent(['name' => 'steph bug'])->withHeaders([
            Header::EVENT_ID => '123-456',
            Header::EVENT_TIME => 'some_time',
        ]);

        $contentSerializer = $this->prophesize(ContentSerializer::class);
        $contentSerializer->serialize($command)->willReturn(['name' => 'steph'])->shouldBeCalledOnce();

        $message = new Message($command);

        $serializer = new MessagingSerializer($contentSerializer->reveal());

        $payload = $serializer->serializeMessage($message);

        $this->assertArrayHasKey('headers', $payload);
        $this->assertArrayHasKey('content', $payload);

        $this->assertEquals(
            [
                '__event_id' => '123-456',
                '__event_time' => 'some_time',
            ],
            $payload['headers']
        );

        $this->assertEquals(['name' => 'steph'], $payload['content']);
    }

    /**
     * @test
     */
    public function it_normalize_and_serialize_headers(): void
    {
        $datetime = new PointInTime();
        $now = $datetime->now();
        $uid = V4UniqueIdStub::create();

        $command = SomeCommand::fromContent(['name' => 'steph bug']);

        $message = new Message($command, [
            Header::EVENT_ID => $uid,
            Header::EVENT_TIME => $now,
        ]);

        $serializer = new MessagingSerializer(null,
            new DateTimeNormalizer([
                DateTimeNormalizer::FORMAT_KEY => $datetime::DATE_TIME_FORMAT,
                DateTimeNormalizer::TIMEZONE_KEY => 'UTC',
            ]),
            new UidNormalizer()
        );

        $payload = $serializer->serializeMessage($message);

        $this->assertArrayHasKey('headers', $payload);
        $this->assertArrayHasKey('content', $payload);

        $this->assertEquals(
            [
                '__event_id' => $uid->jsonSerialize(),
                '__event_time' => $now->format($datetime::DATE_TIME_FORMAT),
            ],
            $payload['headers']
        );

        $this->assertEquals(['name' => 'steph bug'], $payload['content']);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_event_is_not_an_instance_of_reporting_during_serialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Message event '.stdClass::class.' must be an instance of Messaging to be serialized');

        (new MessagingSerializer())->serializeMessage(new Message(new stdClass()));
    }

    /**
     * @test
     */
    public function it_unserialize_payload(): void
    {
        $payload = [
            'headers' => [
                Header::EVENT_TYPE => SomeCommand::class,
                Header::EVENT_ID => '123-456',
                Header::EVENT_TIME => 'some_time',
            ],
            'content' => ['name' => 'steph bug'],
        ];

        $serializer = new MessagingSerializer();

        $event = $serializer->unserializeContent($payload)->current();

        $this->assertInstanceOf(SomeCommand::class, $event);

        $this->assertEquals($payload['headers'], $event->headers());
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

        (new MessagingSerializer())->unserializeContent($payload)->current();
    }
}
