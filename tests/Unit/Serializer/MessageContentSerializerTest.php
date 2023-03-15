<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use stdClass;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Serializer\MessageContentSerializer;

#[CoversClass(MessageContentSerializer::class)]
final class MessageContentSerializerTest extends UnitTestCase
{
    #[Test]
    public function it_return_message_content(): void
    {
        $event = new SomeCommand(['name' => 'steph bug']);

        $contentSerializer = new MessageContentSerializer();

        $content = $contentSerializer->serialize($event);

        $this->assertEquals(['name' => 'steph bug'], $content);
    }

    #[Test]
    public function it_return_reporting_instance_from_array_content(): void
    {
        $contentSerializer = new MessageContentSerializer();

        $event = $contentSerializer->deserialize(
            SomeCommand::class,
            ['content' => ['name' => 'steph bug']]
        );

        $this->assertInstanceOf(SomeCommand::class, $event);
        $this->assertEquals(['name' => 'steph bug'], $event->toContent());
    }

    #[Test]
    public function it_raise_exception_with_invalid_source_during_deserialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid source to deserialize');

        (new MessageContentSerializer())->deserialize(stdClass::class, []);
    }
}
