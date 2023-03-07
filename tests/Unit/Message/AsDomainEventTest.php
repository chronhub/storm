<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use ReflectionClass;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Message\Attribute\AsDomainEvent;

class AsDomainEventTest extends UnitTestCase
{
    #[Test]
    public function it_assert_properties(): void
    {
        $attribute = new AsDomainEvent(['foo' => 'bar'], false, 'onEvent', 'handler');

        $this->assertEquals(['foo' => 'bar'], $attribute->content);
        $this->assertEquals('onEvent', $attribute->method);
        $this->assertEquals('handler', $attribute->handlers[0]);
    }

    #[Test]
    public function it_raise_exception_when_empty_handler_is_disallowed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AsDomainEvent(['foo' => 'bar'], false, 'onEvent');
    }

    #[Test]
    public function it_get_attribute(): void
    {
        $eventStub = new AsDomainEventStub(['foo' => 'bar']);

        $ref = new ReflectionClass($eventStub);

        $attribute = $ref->getAttributes(AsDomainEvent::class);

        $newInstance = $attribute[0]->newInstance();

        $this->assertEquals(['foo' => 'bar'], $newInstance->content);
        $this->assertEquals('__invoke', $newInstance->method);
        $this->assertEquals(['SomeEventHandler1', 'SomeEventHandler2'], $newInstance->handlers);
    }

    #[Test]
    public function it_set_magic_method_invoke_if_method_is_not_provided(): void
    {
        $attribute = new AsDomainEvent(['foo' => 'bar'], true, null);

        $this->assertEquals(['foo' => 'bar'], $attribute->content);
        $this->assertEquals('__invoke', $attribute->method);
        $this->assertEmpty($attribute->handlers);
    }
}
