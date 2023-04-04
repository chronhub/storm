<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use Chronhub\Storm\Message\Attribute\AsDomainQuery;
use Chronhub\Storm\Tests\UnitTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

#[CoversClass(AsDomainQuery::class)]
class AsDomainQueryTest extends UnitTestCase
{
    #[Test]
    public function it_assert_properties(): void
    {
        $attribute = new AsDomainQuery(['foo' => 'bar'], 'method', 'handler');

        $this->assertEquals(['foo' => 'bar'], $attribute->content);
        $this->assertEquals('method', $attribute->method);
        $this->assertEquals('handler', $attribute->handlers[0]);
    }

    #[Test]
    public function it_get_attribute(): void
    {
        $command = new AsDomainQueryStub(['foo' => 'bar']);

        $ref = new ReflectionClass($command);

        $attribute = $ref->getAttributes(AsDomainQuery::class);

        $newInstance = $attribute[0]->newInstance();

        $this->assertEquals(['foo' => 'bar'], $newInstance->content);
        $this->assertEquals('query', $newInstance->method);
        $this->assertEquals('SomeQueryHandler', $newInstance->handlers[0]);
    }

    #[Test]
    public function it_set_magic_method_invoke_if_method_is_not_provided(): void
    {
        $attribute = new AsDomainQuery(['foo' => 'bar'], null, 'handler');

        $this->assertEquals(['foo' => 'bar'], $attribute->content);
        $this->assertEquals('__invoke', $attribute->method);
        $this->assertEquals('handler', $attribute->handlers[0]);
    }

    #[Test]
    public function it_raise_exception_when_event_handlers_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AsDomainQuery(['foo' => 'bar'], 'method');
    }

    #[Test]
    public function it_raise_exception_when_event_handlers_is_greater_than_one(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AsDomainQuery(['foo' => 'bar'], 'method', 'handler', 'handler2');
    }
}
