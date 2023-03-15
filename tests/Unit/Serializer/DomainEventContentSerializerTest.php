<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use stdClass;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Serializer\DomainEventContentSerializer;

class DomainEventContentSerializerTest extends UnitTestCase
{
    #[Test]
    public function it_return_message_content(): void
    {
        $event = new SomeEvent(['name' => 'steph bug']);

        $contentSerializer = new DomainEventContentSerializer();

        $content = $contentSerializer->serialize($event);

        $this->assertEquals(['name' => 'steph bug'], $content);
    }

    #[Test]
    public function it_return_domain_event_instance_from_array_content(): void
    {
        $contentSerializer = new DomainEventContentSerializer();

        $event = $contentSerializer->deserialize(
            SomeEvent::class,
            ['content' => ['name' => 'steph bug']]
        );

        $this->assertInstanceOf(SomeEvent::class, $event);
        $this->assertEquals(['name' => 'steph bug'], $event->toContent());
    }

    #[Test]
    public function it_raise_exception_with_invalid_source_when_deserialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid source to deserialize');

        (new DomainEventContentSerializer())->deserialize(stdClass::class, []);
    }
}
