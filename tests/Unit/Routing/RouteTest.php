<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use stdClass;
use Generator;
use Chronhub\Storm\Routing\Route;
use Chronhub\Storm\Tests\UnitTestCase;
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
    public function testRouteInstance(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEquals($route->getOriginalName(), $messageName);
        $this->assertEquals($route->getName(), $messageName);
        $this->assertNull($route->getQueue());
        $this->assertEmpty($route->getHandlers());
    }

    public function testExceptionRaisedWhenMessageNameIsNotFQN(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Message name must be a valid class name, got foo');

        new Route('foo');
    }

    #[DataProvider('provideMessageClassName')]
    public function testMessageAlias(string $messageName): void
    {
        $route = new Route($messageName);

        $this->assertEquals($route->getOriginalName(), $messageName);
        $this->assertEquals($route->getName(), $messageName);

        $route->alias('some-alias');

        $this->assertEquals('some-alias', $route->getName());
        $this->assertEquals($route->getOriginalName(), $messageName);
    }

    #[DataProvider('provideMessageHandler')]
    public function testRouteMessageToHisHandler(string|object $messageHandler): void
    {
        $route = new Route(SomeCommand::class);
        $route->to($messageHandler);

        $this->assertEquals([$messageHandler], $route->getHandlers());
    }

    #[DataProvider('provideMessageHandler')]
    public function testRouteMessageToHisHandlers(): void
    {
        $route = new Route(SomeCommand::class);

        $route->to('some_message_handler');
        $route->to('another_message_handler');

        $this->assertEquals(['some_message_handler', 'another_message_handler'], $route->getHandlers());
    }

    public function testRouteQueueOptionSetter(): void
    {
        $route = new Route(SomeCommand::class);
        $route->onQueue(['connection' => 'redis', 'name' => 'default']);

        $this->assertEquals(['connection' => 'redis', 'name' => 'default'], $route->getQueue());
    }

    public function testNullQueueOptionArgument(): void
    {
        $route = new Route(SomeCommand::class);
        $route->onQueue();

        $this->assertNull($route->getQueue());
    }

    public function testItSerializeRoute(): void
    {
        $route = new Route(SomeCommand::class);

        $this->assertEquals([
            'message_name' => SomeCommand::class,
            'original_message_name' => SomeCommand::class,
            'message_handlers' => [],
            'queue_options' => null,
        ], $route->jsonSerialize());
    }

    public function testItSerializeRouteWithRouteQueueOption(): void
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
