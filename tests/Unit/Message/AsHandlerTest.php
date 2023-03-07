<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Message;

use ReflectionClass;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Message\Attribute\AsHandler;

class AsHandlerTest extends UnitTestCase
{
    #[Test]
    public function it_assert_properties(): void
    {
        $attribute = new AsHandler(SomeCommand::class, 'method');

        $this->assertEquals(SomeCommand::class, $attribute->domain);
        $this->assertEquals('method', $attribute->method);
    }

    #[Test]
    public function it_set_invoke_method(): void
    {
        $attribute = new AsHandler(SomeCommand::class);

        $this->assertEquals(SomeCommand::class, $attribute->domain);
        $this->assertEquals('__invoke', $attribute->method);
    }

    #[Test]
    public function it_get_attribute(): void
    {
        $command = new AsHandlerStub();

        $ref = new ReflectionClass($command);

        $attribute = $ref->getAttributes(AsHandler::class);

        $newInstance = $attribute[0]->newInstance();

        $this->assertEquals(SomeCommand::class, $newInstance->domain);
        $this->assertEquals('command', $newInstance->method);
    }
}
