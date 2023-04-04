<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\Attribute\AsNotificationEvent;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

#[CoversClass(AsNotificationEvent::class)]
class AsNotificationEventTest extends UnitTestCase
{
    #[Test]
    public function it_assert_properties(): void
    {
        $attribute = new AsNotificationEvent(['foo' => 'bar'], false, 'handler1', 'handler2');

        $this->assertEquals(['foo' => 'bar'], $attribute->content);
        $this->assertFalse($attribute->allowEmpty);
        $this->assertEquals(['handler1', 'handler2'], $attribute->handlers);
    }

    #[Test]
    public function it_raise_exception_when_empty_handler_is_disallowed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AsNotificationEvent(['foo' => 'bar'], false);
    }

    #[Test]
    public function it_get_attribute(): void
    {
        $eventStub = new AsNotificationEventStub(['foo' => 'bar']);

        $ref = new ReflectionClass($eventStub);

        $attribute = $ref->getAttributes(AsNotificationEvent::class);

        $newInstance = $attribute[0]->newInstance();

        $this->assertEquals(['foo' => 'bar'], $newInstance->content);
        $this->assertTrue($newInstance->allowEmpty);
        $this->assertEquals(['SomeEventHandler1', 'SomeEventHandler2'], $newInstance->handlers);
    }
}
