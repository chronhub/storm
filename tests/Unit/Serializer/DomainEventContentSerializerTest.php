<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Serializer;

use Chronhub\Storm\Serializer\DomainEventContentSerializer;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

#[CoversClass(DomainEventContentSerializer::class)]
class DomainEventContentSerializerTest extends UnitTestCase
{
    public function testSerializeContent(): void
    {
        $event = new SomeEvent(['name' => 'steph bug']);

        $contentSerializer = new DomainEventContentSerializer();

        $content = $contentSerializer->serialize($event);

        $this->assertEquals(['name' => 'steph bug'], $content);
    }

    public function testDeserializePayload(): void
    {
        $contentSerializer = new DomainEventContentSerializer();

        $event = $contentSerializer->deserialize(
            SomeEvent::class,
            ['content' => ['name' => 'steph bug']]
        );

        $this->assertInstanceOf(SomeEvent::class, $event);
        $this->assertEquals(['name' => 'steph bug'], $event->toContent());
    }

    public function testExceptionRaisedWithInvalidSourceToDeserialize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->expectExceptionMessage('Invalid source to deserialize');

        (new DomainEventContentSerializer())->deserialize(stdClass::class, []);
    }
}
