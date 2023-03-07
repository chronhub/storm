<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use ReflectionClass;
use InvalidArgumentException;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use Chronhub\Storm\Message\Attribute\AsDomainCommand;

#[CoversClass(AsDomainCommand::class)]
class AsDomainCommandTest extends UnitTestCase
{
    #[Test]
    public function it_assert_properties(): void
    {
        $attribute = new AsDomainCommand(['foo' => 'bar'], 'method', 'handler');

        $this->assertEquals(['foo' => 'bar'], $attribute->content);
        $this->assertEquals('method', $attribute->method);
        $this->assertEquals('handler', $attribute->handlers[0]);
    }

    #[Test]
    public function it_get_attribute(): void
    {
        $command = new AsDomainCommandStub(['foo' => 'bar']);

        $ref = new ReflectionClass($command);

        $attribute = $ref->getAttributes(AsDomainCommand::class);

        $newInstance = $attribute[0]->newInstance();

        $this->assertEquals(['foo' => 'bar'], $newInstance->content);
        $this->assertEquals('command', $newInstance->method);
        $this->assertEquals('SomeCommandHandler', $newInstance->handlers[0]);
    }

    #[Test]
    public function it_set_magic_method_invoke_if_method_is_not_provided(): void
    {
        $attribute = new AsDomainCommand(['foo' => 'bar'], null, 'handler');

        $this->assertEquals(['foo' => 'bar'], $attribute->content);
        $this->assertEquals('__invoke', $attribute->method);
        $this->assertEquals('handler', $attribute->handlers[0]);
    }

    #[Test]
    public function it_raise_exception_when_event_handlers_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AsDomainCommand(['foo' => 'bar'], 'method');
    }

    #[Test]
    public function it_raise_exception_when_event_handlers_is_greater_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AsDomainCommand(['foo' => 'bar'], 'method', 'handler', 'handler2');
    }
}
