<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use stdClass;
use Generator;
use Chronhub\Storm\Routing\Route;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeEvent;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

#[CoversClass(Route::class)]
final class RouteTest extends UnitTestCase
{
    #[DataProvider('provideMessageClassName')]
    #[Test]
    public function it_can_be_constructed_with_message_class_name(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEquals($route->getOriginalName(), $messageName);
        $this->assertEquals($route->getName(), $messageName);
        $this->assertNull($route->getQueue());
        $this->assertEmpty($route->getHandlers());
    }

    #[Test]
    public function it_raise_exception_when_message_name_is_not_a_valid_class_name(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Message name must be a valid class name, got foo');

        new Route('foo');
    }

    #[DataProvider('provideMessageClassName')]
    #[Test]
    public function it_set_message_alias(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEquals($route->getOriginalName(), $messageName);
        $this->assertEquals($route->getName(), $messageName);

        $route->alias('some-alias');

        $this->assertEquals('some-alias', $route->getName());
        $this->assertEquals($route->getOriginalName(), $messageName);
    }

    #[DataProvider('provideMessageHandler')]
    #[Test]
    public function it_add_message_handler(string|object $messageHandler): void
    {
        $route = new Route(SomeCommand::class);
        $route->to($messageHandler);

        $this->assertEquals([$messageHandler], $route->getHandlers());
    }

    #[DataProvider('provideMessageHandler')]
    #[Test]
    public function it_merge_message_handlers(): void
    {
        $route = new Route(SomeCommand::class);

        $route->to('some_message_handler');
        $route->to('another_message_handler');

        $this->assertEquals(['some_message_handler', 'another_message_handler'], $route->getHandlers());
    }

    #[Test]
    public function it_set_queue_options(): void
    {
        $route = new Route(SomeCommand::class);
        $route->onQueue(['connection' => 'redis', 'name' => 'default']);

        $this->assertEquals(['connection' => 'redis', 'name' => 'default'], $route->getQueue());
    }

    #[Test]
    public function it_return_null_queue_options_when_argument_is_empty(): void
    {
        $route = new Route(SomeCommand::class);
        $route->onQueue();

        $this->assertNull($route->getQueue());
    }

    #[Test]
    public function it_serialize_route(): void
    {
        $route = new Route(SomeCommand::class);

        $this->assertEquals([
            'message_name' => SomeCommand::class,
            'original_message_name' => SomeCommand::class,
            'message_handlers' => [],
            'queue_options' => null,
        ], $route->jsonSerialize());
    }

    #[Test]
    public function it_serialize_full_route(): void
    {
        $route = new Route(SomeCommand::class);

        $route->alias('some-alias');
        $route->to('some_message_handler');
        $route->onQueue(['connection' => 'rabbitmq', 'name' => 'transaction']);

        $this->assertEquals([
            'message_name' => 'some-alias',
            'original_message_name' => SomeCommand::class,
            'message_handlers' => ['some_message_handler'],
            'queue_options' => ['connection' => 'rabbitmq', 'name' => 'transaction'],
        ], $route->jsonSerialize());
    }

    public static function provideMessageClassName(): Generator
    {
        yield [SomeCommand::class];
        yield [SomeEvent::class];
        yield [SomeQuery::class];
        yield [stdClass::class];
    }

    public static function provideMessageHandler(): Generator
    {
        yield ['foo'];

        yield [static function () {
            //
        }];

        yield [new class()
        {
            public function __invoke(object $event): void
            {
            }
        }];
    }
}
