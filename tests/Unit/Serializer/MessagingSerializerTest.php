<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Clock\PointInTime;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Serializer\MessageContentSerializer;
use Chronhub\Storm\Serializer\MessagingSerializer;
use Chronhub\Storm\Serializer\SerializeToJson;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\Stubs\V4UniqueIdStub;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

use function sprintf;

#[CoversClass(MessagingSerializer::class)]
final class MessagingSerializerTest extends UnitTestCase
{
    public function testSerializePayload(): void
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

    public function testNormalizeAndSerializePayload(): void
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

    public function testExceptionRaisedWithInvalidMessageEvent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf('Message event %s must be an instance of Reporting to be serialized', stdClass::class)
        );

        $serializer = $this->messageSerializerInstance();

        $serializer->serializeMessage(new Message(new stdClass()));
    }

    public function testDeserializePayloadToReport(): void
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

    public function testExceptionRaisedWithMissingEventTypeHeaderDuringDeserialization(): void
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
