<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Contracts\Message\Header;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Message\AliasFromInflector;
use Chronhub\Storm\Message\AliasFromMap;
use Chronhub\Storm\Message\Message;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\Exceptions\RouteHandlerNotSupported;
use Chronhub\Storm\Routing\Exceptions\RouteNotFound;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\FindRoute;
use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\Rules\RequireOneHandlerRule;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

#[CoversClass(FindRoute::class)]
#[CoversClass(RouteNotFound::class)]
#[CoversClass(RouteHandlerNotSupported::class)]
final class FindRouteTest extends UnitTestCase
{
    private MockObject|ContainerInterface $container;

    private Message $message;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->message = new Message(SomeCommand::fromContent(['foo' => 'bar']), [
            Header::EVENT_TYPE => SomeCommand::class,
        ]);
    }

    #[DataProvider('provideMessageAlias')]
    public function testRouteToHandler(MessageAlias $messageAlias): void
    {
        $routes = new CollectRoutes($messageAlias);

        $routes->addRoute(SomeCommand::class)->to(static fn (): int => 42);

        $group = new Group(DomainType::COMMAND, 'default', $routes);

        $matcher = new FindRoute($group, $messageAlias, $this->container);

        $messageHandlers = $matcher->route($this->message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(42, $messageHandlers[0]());
    }

    #[DataProvider('provideMessageAlias')]
    public function testRouteToCallableMessageHandler(MessageAlias $messageAlias): void
    {
        $routes = new CollectRoutes($messageAlias);

        $messageHandler = $this->provideCommandHandler();
        $routes->addRoute(SomeCommand::class)->to($messageHandler);

        $group = new Group(DomainType::COMMAND, 'default', $routes);
        $group->withHandlerMethod('command');

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $messageHandlers = $matcher->route($this->message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    #[DataProvider('provideMessageAlias')]
    public function testRouteToCallableMessageHandlerPrioritizeInvokableFirst(MessageAlias $messageAlias): void
    {
        $routes = new CollectRoutes($messageAlias);

        $messageHandler = $this->provideInvokableCommandHandler();
        $routes->addRoute(SomeCommand::class)->to($messageHandler);

        $group = new Group(DomainType::COMMAND, 'default', $routes);
        $group->withHandlerMethod('command');

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $messageHandlers = $matcher->route($this->message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(1, $messageHandlers[0]());
    }

    #[DataProvider('provideMessageAlias')]
    public function testRouteToMessageHandlerResolvedFromIOC(MessageAlias $messageAlias): void
    {
        $messageHandler = $this->provideCommandHandler();

        $this->container->expects($this->once())
            ->method('get')
            ->with('some_message_handler.id')
            ->willReturn($messageHandler);

        $routes = new CollectRoutes($messageAlias);
        $routes->addRoute(SomeCommand::class)->to('some_message_handler.id');

        $group = new Group(DomainType::COMMAND, 'default', $routes);
        $group->withHandlerMethod('command');

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $messageHandlers = $matcher->route($this->message);

        $this->assertCount(1, $messageHandlers);
        $this->assertEquals(74, $messageHandlers[0]());
    }

    #[DataProvider('provideMessageAlias')]
    public function testExceptionRaisedWhenMessageHandlerNotSupported(MessageAlias $messageAlias): void
    {
        $this->expectException(RouteHandlerNotSupported::class);
        $this->expectExceptionMessage('Route handler is not supported for message name');

        $routes = new CollectRoutes($messageAlias);

        $messageHandler = $this->provideCommandHandler();
        $routes->addRoute(SomeCommand::class)->to($messageHandler);

        $group = new Group(DomainType::COMMAND, 'default', $routes);

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $matcher->route($this->message);
    }

    #[DataProvider('provideMessageAlias')]
    public function testExceptionRaisedWhenMessageNotFound(MessageAlias $messageAlias): void
    {
        $this->expectException(RouteNotFound::class);
        $this->expectExceptionMessage('Route not found with message name');

        $routes = new CollectRoutes($messageAlias);
        $this->assertTrue($routes->getRoutes()->isEmpty());

        $group = new Group(DomainType::COMMAND, 'default', $routes);

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $matcher->route($this->message);
    }

    #[DataProvider('provideMessageAlias')]
    public function testQueueOption(MessageAlias $messageAlias): void
    {
        $routes = new CollectRoutes($messageAlias);
        $routes
            ->addRoute(SomeCommand::class)
            ->to(static fn (): int => 42)
            ->onQueue(['connection' => 'redis', 'tries' => 3]);

        $group = new Group(DomainType::COMMAND, 'default', $routes);

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $queue = $matcher->onQueue($this->message);

        $this->assertEquals(['connection' => 'redis', 'tries' => 3], $queue);
    }

    #[DataProvider('provideMessageAlias')]
    public function testNullQueueOption(MessageAlias $messageAlias): void
    {
        $routes = new CollectRoutes($messageAlias);

        $routes->addRoute(SomeCommand::class);

        $group = new Group(DomainType::COMMAND, 'default', $routes);

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $this->assertNull($matcher->onQueue($this->message));
    }

    #[DataProvider('provideMessageAlias')]
    public function testRaisedExceptionWhenMessageNotFoundWhenQueueOption(MessageAlias $messageAlias): void
    {
        $this->expectException(RouteNotFound::class);

        $routes = new CollectRoutes($messageAlias);
        $this->assertTrue($routes->getRoutes()->isEmpty());

        $group = new Group(DomainType::COMMAND, 'default', $routes);

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $matcher->onQueue($this->message);
    }

    #[DataProvider('provideInvalidMessageHandlers')]
    public function testGroupRule(array $invalidCountMessageHandlers): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Group type command and name default require one route handler only for message some-command');

        $messageAlias = new AliasFromInflector();

        $routes = new CollectRoutes($messageAlias);
        $routes->addRoute(SomeCommand::class)->to(...$invalidCountMessageHandlers);

        $group = new Group(DomainType::COMMAND, 'default', $routes);
        $group->addRule(new RequireOneHandlerRule());

        $matcher = new FindRoute($group, $messageAlias, $this->container);
        $matcher->route($this->message);
    }

    public static function provideMessageAlias(): Generator
    {
        yield [new AliasFromClassName()];
        yield [new AliasFromInflector()];
        yield [new AliasFromMap([SomeCommand::class => 'some-command'])];
    }

    private function provideCommandHandler(): object
    {
        return new class
        {
            public function command(): int
            {
                return 74;
            }
        };
    }

    private function provideInvokableCommandHandler(): object
    {
        return new class
        {
            public function __invoke(): int
            {
                return 1;
            }

            public function command(): int
            {
                return 74;
            }
        };
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
