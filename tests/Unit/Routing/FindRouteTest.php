<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Generator;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Routing\FindRoute;
use Psr\Container\ContainerInterface;
use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\CollectRoutes;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Contracts\Message\Header;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Chronhub\Storm\Tests\Stubs\Double\SomeQuery;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Routing\Exceptions\RouteNotFound;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Exceptions\RouteHandlerNotSupported;

#[CoversClass(FindRoute::class)]
#[CoversClass(RouteNotFound::class)]
#[CoversClass(RouteHandlerNotSupported::class)]
final class FindRouteTest extends UnitTestCase
{
    private MockObject|ContainerInterface $container;

    private MockObject|MessageAlias $messageAlias;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->messageAlias = $this->createMock(MessageAlias::class);
    }

    #[Test]
    public function it_route_message_to_his_handlers(): void
    {
        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class)->to(static fn (): int => 42);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $messageHandlers = $matcher->route($message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(42, $messageHandlers[0]());
    }

    #[Test]
    public function it_route_message_to_his_handlers_transform_to_callable(): void
    {
        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $messageHandler = new class
        {
            public function command(): int
            {
                return 74;
            }
        };

        $routes->addRoute(SomeCommand::class)->to($messageHandler);

        $group = new CommandGroup('default', $routes);
        $group->withHandlerMethod('command');

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $messageHandlers = $matcher->route($message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    #[Test]
    public function it_route_message_to_his_handlers_resolve_from_container(): void
    {
        $messageHandler = new class
        {
            public function __invoke(): int
            {
                return 74;
            }
        };

        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $this->container->expects($this->once())
            ->method('get')
            ->with('some_message_handler.id')
            ->willReturn($messageHandler);

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class)->to('some_message_handler.id');

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $messageHandlers = $matcher->route($message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    #[Test]
    public function it_raise_exception_when_message_handler_is_not_supported(): void
    {
        $this->expectException(RouteHandlerNotSupported::class);
        $this->expectExceptionMessage('Route handler is not supported for message name some-command');

        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $messageHandler = new class
        {
            public function command(): int
            {
                return 74;
            }
        };

        $routes->addRoute(SomeCommand::class)->to($messageHandler);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->route($message);
    }

    #[Test]
    public function it_raise_exception_when_message_not_found(): void
    {
        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('Route not found with message name some-command');

        $this->messageAlias->expects($this->once())
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);
        $this->assertTrue($routes->getRoutes()->isEmpty());

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->route($message);
    }

    #[Test]
    public function it_return_route_queue_options(): void
    {
        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $routes
            ->addRoute(SomeCommand::class)
            ->to(static fn (): int => 42)
            ->onQueue(['connection' => 'redis', 'tries' => 3]);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $queue = $matcher->onQueue($message);

        $this->assertEquals(['connection' => 'redis', 'tries' => 3], $queue);
    }

    #[Test]
    public function it_return_null_route_queue_options_if_not_set(): void
    {
        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $this->assertNull($matcher->onQueue($message));
    }

    #[Test]
    public function it_raise_exception_access_route_queue_when_message_not_found(): void
    {
        $this->expectException(RouteNotFound::class);

        $this->messageAlias->expects($this->once())
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);
        $this->assertTrue($routes->getRoutes()->isEmpty());

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->onQueue($message);
    }

    #[DataProvider('provideInvalidMessageHandlers')]
    #[Test]
    public function it_raise_exception_for_command_group_when_count_message_handlers_is_not_exactly_one(array $messageHandlers): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Group command required one handler only for message name some-command');

        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class)->to(...$messageHandlers);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);

        $matcher->route($message);
    }

    #[DataProvider('provideInvalidMessageHandlers')]
    #[Test]
    public function it_raise_exception_for_query_group_when_count_message_handlers_is_not_exactly_one(array $messageHandlers): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Group query required one handler only for message name some-query');

        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeQuery::class)
            ->willReturn('some-query');

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeQuery::class)->to(...$messageHandlers);

        $group = new QueryGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $message = new Message(SomeQuery::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeQuery::class,
        ]);

        $matcher->route($message);
    }

    public static function provideInvalidMessageHandlers(): Generator
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
