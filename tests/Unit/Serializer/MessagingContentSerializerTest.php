<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use stdClass;
use InvalidArgumentException;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Serializer\MessagingContentSerializer;

final class MessagingContentSerializerTest extends ProphecyTestCase
{
    /**
     * @test
     */
    public function it_return_message_content(): void
    {
        $event = new SomeCommand(['name' => 'steph bug']);

        $contentSerializer = new MessagingContentSerializer();

        $content = $contentSerializer->serialize($event);

        $this->assertEquals(['name' => 'steph bug'], $content);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_is_not_an_instance_of_reporting_during_serialization(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Message event '.stdClass::class.' must be an instance of Reporting to be serialized');

        (new MessagingContentSerializer())->serialize(new stdClass());
    }

    /**
     * @test
     */
    public function it_return_reporting_instance_from_array_content(): void
    {
        $contentSerializer = new MessagingContentSerializer();

        $event = $contentSerializer->unserialize(
            SomeCommand::class,
            ['content' => ['name' => 'steph bug']]
        );

        $this->assertInstanceOf(SomeCommand::class, $event);
        $this->assertEquals(['name' => 'steph bug'], $event->toContent());
    }

    /**
     * @test
     */
    public function it_raise_exception_with_invalid_source_during_unserialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid source to unserialize');

        (new MessagingContentSerializer())->unserialize(stdClass::class, []);
    }
}
