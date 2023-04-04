<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\Exceptions\RouteHandlerNotSupported;
use Chronhub\Storm\Routing\Exceptions\RouteNotFound;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\FindRoute;
use Chronhub\Storm\Routing\Rules\RequireOneHandlerRule;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

#[CoversClass(FindRoute::class)]
#[CoversClass(RouteNotFound::class)]
#[CoversClass(RouteHandlerNotSupported::class)]
final class FindRouteTest extends UnitTestCase
{
    private MockObject|ContainerInterface $container;

    private MockObject|MessageAlias $messageAlias;

    private Message $message;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->messageAlias = $this->createMock(MessageAlias::class);
        $this->message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);
    }

    public function testRouteToHandler(): void
    {
        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class)->to(static fn (): int => 42);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $messageHandlers = $matcher->route($this->message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(42, $messageHandlers[0]());
    }

    public function testRouteToCallableMessageHandler(): void
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

        $messageHandlers = $matcher->route($this->message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    #[Test]
    public function testRouteToMessageHandlerResolvedFromIOC(): void
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

        $messageHandlers = $matcher->route($this->message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    public function testExceptionRaisedWhenMessageHandlerNotSupported(): void
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

        $matcher->route($this->message);
    }

    #[Test]
    public function testExceptionRaisedWhenMessageNotFound(): void
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

        $matcher->route($this->message);
    }

    #[Test]
    public function testQueueOptionGetter(): void
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

        $queue = $matcher->onQueue($this->message);

        $this->assertEquals(['connection' => 'redis', 'tries' => 3], $queue);
    }

    #[Test]
    public function testNullQueueOption(): void
    {
        $this->messageAlias->expects($this->exactly(2))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class);

        $group = new CommandGroup('default', $routes);

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $this->assertNull($matcher->onQueue($this->message));
    }

    #[Test]
    public function testRaisedExceptionWhenMessageNotFoundWhenGetQueueOption(): void
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

        $matcher->onQueue($this->message);
    }

    #[DataProvider('provideInvalidMessageHandlers')]
    public function testGroupRule(array $invalidCountMessageHandlers): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Group type command and name default require one route handler only for message some-command');

        $this->messageAlias->expects($this->exactly(1))
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class)->to(...$invalidCountMessageHandlers);

        $group = new CommandGroup('default', $routes);
        $group->addRule(new RequireOneHandlerRule());

        $matcher = new FindRoute($group, $this->messageAlias, $this->container);

        $matcher->route($this->message);
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
