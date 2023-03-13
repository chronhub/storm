<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use stdClass;
use InvalidArgumentException;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Symfony\Component\Serializer\Serializer;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Serializer\SerializeToJson;
use Chronhub\Storm\Tests\Stubs\V4UniqueIdStub;
use Chronhub\Storm\Serializer\MessagingSerializer;
use Chronhub\Storm\Serializer\MessageContentSerializer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[CoversClass(MessagingSerializer::class)]
final class MessagingSerializerTest extends UnitTestCase
{
    #[Test]
    public function it_serialize_message_with_scalar_headers(): void
    {
        $command = SomeCommand::fromContent(['name' => 'steph bug'])->withHeaders([
            Header::EVENT_ID => '123-456',
            Header::EVENT_TIME => 'some_time',
        ]);

        $message = new Message($command);

        $serializer = $this->messageSerializerInstance();

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

    #[Test]
    public function it_serialize_content_message(): void
    {
        $command = SomeCommand::fromContent(['name' => 'steph bug'])->withHeaders([
            Header::EVENT_ID => '123-456',
            Header::EVENT_TIME => 'some_time',
        ]);

        $message = new Message($command);

        $serializer = $this->messageSerializerInstance();

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

    #[Test]
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

        $serializer = $this->messageSerializerInstance(new DateTimeNormalizer([
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

    #[Test]
    public function it_raise_exception_if_message_event_is_not_an_instance_of_reporting_during_serialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Message event '.stdClass::class.' must be an instance of Reporting to be serialized');

        $serializer = $this->messageSerializerInstance();

        $serializer->serializeMessage(new Message(new stdClass()));
    }

    #[Test]
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

        $serializer = $this->messageSerializerInstance();

        $event = $serializer->deserializePayload($payload);

        $this->assertInstanceOf(SomeCommand::class, $event);

        $this->assertEquals($payload['headers'], $event->headers());
        $this->assertEquals($payload['content'], $event->toContent());
    }

    #[Test]
    public function it_raise_exception_if_message_event_type_missing_during_unserialize_content(): void
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

        $serializer = $this->messageSerializerInstance();
        $serializer->deserializePayload($payload);
    }

    private function messageSerializerInstance(NormalizerInterface|DenormalizerInterface ...$normalizers): MessagingSerializer
    {
        return new MessagingSerializer(
            new MessageContentSerializer(),
            new Serializer(
                $normalizers,
                [(new SerializeToJson())->getEncoder()]
            )
        );
    }
}
