<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Generator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Routing\FindRoute;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Tests\Double\SomeQuery;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Routing\Exceptions\RouteNotFound;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Exceptions\RouteHandlerNotSupported;

final class FindRouteTest extends ProphecyTestCase
{
    private ObjectProphecy|ContainerInterface $container;

    private ObjectProphecy|MessageAlias $messageAlias;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->messageAlias = $this->prophesize(MessageAlias::class);
    }

    /**
     * @test
     */
    public function it_route_message_to_his_handlers(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledTimes(2);

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $routes->addRoute(SomeCommand::class)->to(static fn(): int => 42);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $messageHandlers = $matcher->route($message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(42, $messageHandlers[0]());
    }

    /**
     * @test
     */
    public function it_route_message_to_his_handlers_transform_to_callable(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledTimes(2);

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $messageHandler = new class
        {
            public function command(): int
            {
                return 74;
            }
        };

        $routes->addRoute(SomeCommand::class)->to($messageHandler);

        $group = new CommandGroup('default', $routes);
        $group->withMessageHandlerMethodName('command');

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $messageHandlers = $matcher->route($message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    /**
     * @test
     */
    public function it_route_message_to_his_handlers_resolve_from_container(): void
    {
        $messageHandler = new class
        {
            public function __invoke(): int
            {
                return 74;
            }
        };

        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledTimes(2);
        $this->container->get('some_message_handler.id')->willReturn($messageHandler)->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $routes->addRoute(SomeCommand::class)->to('some_message_handler.id');

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $messageHandlers = $matcher->route($message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_handler_is_not_supported(): void
    {
        $this->expectException(RouteHandlerNotSupported::class);
        $this->expectExceptionMessage('Route handler is not supported for message name some-command');

        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledTimes(2);

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $messageHandler = new class
        {
            public function command(): int
            {
                return 74;
            }
        };

        $routes->addRoute(SomeCommand::class)->to($messageHandler);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->route($message);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_not_found(): void
    {
        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('Route not found with message name some-command');

        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());
        $this->assertTrue($routes->getRoutes()->isEmpty());

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->route($message);
    }

    /**
     * @test
     */
    public function it_return_route_queue_options(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledTimes(2);

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $routes
            ->addRoute(SomeCommand::class)
            ->to(static fn(): int => 42)
            ->onQueue(['connection' => 'redis', 'tries' => 3]);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $queue = $matcher->onQueue($message);

        $this->assertEquals(['connection' => 'redis', 'tries' => 3], $queue);
    }

    /**
     * @test
     */
    public function it_return_null_route_queue_options_if_not_set(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledTimes(2);

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $routes->addRoute(SomeCommand::class);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $this->assertNull($matcher->onQueue($message));
    }

    /**
     * @test
     */
    public function it_raise_exception_access_route_queue_when_message_not_found(): void
    {
        $this->expectException(RouteNotFound::class);

        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());
        $this->assertTrue($routes->getRoutes()->isEmpty());

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->onQueue($message);
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidMessageHandlers
     */
    public function it_raise_exception_for_command_group_when_count_message_handlers_is_not_exactly_one(array $messageHandlers): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Group command required one handler only for message name some-command');

        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledTimes(2);

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $routes->addRoute(SomeCommand::class)->to(...$messageHandlers);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->route($message);
    }

    /**
     * @test
     *
     * @dataProvider provideInvalidMessageHandlers
     */
    public function it_raise_exception_for_query_group_when_count_message_handlers_is_not_exactly_one(array $messageHandlers): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Group query required one handler only for message name some-query');

        $this->messageAlias->classToAlias(SomeQuery::class)->willReturn('some-query')->shouldBeCalledTimes(2);

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $routes->addRoute(SomeQuery::class)->to(...$messageHandlers);

        $group = new QueryGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias->reveal(), $this->container->reveal());

        $message = new Message(SomeQuery::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeQuery::class,
        ]);

        $matcher->route($message);
    }

    public function provideInvalidMessageHandlers(): Generator
    {
        yield [[]];

        yield [
            [
                static function (): void {
                    //
                },
                static function (): void {
                    //
                },
            ],
        ];
    }
}
