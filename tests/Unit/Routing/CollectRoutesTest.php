<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Route;
use Chronhub\Storm\Tests\Stubs\Double\AnotherCommand;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use function count;

#[CoversClass(CollectRoutes::class)]
final class CollectRoutesTest extends UnitTestCase
{
    private MockObject|MessageAlias $messageAlias;

    public function setUp(): void
    {
        $this->messageAlias = $this->createMock(MessageAlias::class);
    }

    public function testInstanceWithEmptyRoutes(): void
    {
        $routes = new CollectRoutes($this->messageAlias);

        $this->assertTrue($routes->getRoutes()->isEmpty());
        $this->assertNotSame($routes->getRoutes(), $routes->getRoutes());
        $this->assertNull($routes->match('foo'));
        $this->assertSame(0, count($routes));
    }

    public function testAddRoute(): void
    {
        $this->messageAlias->expects($this->once())
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $route = $routes->addRoute(SomeCommand::class);
        $this->assertSame(1, count($routes));

        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals('some-command', $route->getName());
        $this->assertEquals(SomeCommand::class, $route->getOriginalName());
    }

    public function testExceptionRaisedWithDuplicateRoute(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Message name already exists '.SomeCommand::class);

        $this->messageAlias->expects($this->once())
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $route = $routes->addRoute(SomeCommand::class);

        $this->assertEquals('some-command', $route->getName());

        $routes->addRoute(SomeCommand::class);
    }

    public function testMatchRoute(): void
    {
        $this->messageAlias->expects($this->any())
            ->method('classToAlias')
            ->willReturnMap([[SomeCommand::class, SomeCommand::class], [AnotherCommand::class, AnotherCommand::class]]);

        $routes = new CollectRoutes($this->messageAlias);

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($someRoute->getName(), $routes->match(SomeCommand::class)->getName());
        $this->assertEquals($anotherRoute->getName(), $routes->match(AnotherCommand::class)->getName());
    }

    public function testMatchRouteWithMessageAlias(): void
    {
        $this->messageAlias->expects($this->any())
            ->method('classToAlias')
            ->willReturnMap([[SomeCommand::class, 'some-command'], [AnotherCommand::class, 'another-command']]);

        $routes = new CollectRoutes($this->messageAlias);

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($someRoute->getName(), $routes->match('some-command')->getName());
        $this->assertEquals($anotherRoute->getName(), $routes->match('another-command')->getName());
    }

    public function testMatchOriginalRoute(): void
    {
        $this->messageAlias->expects($this->any())
            ->method('classToAlias')
            ->willReturnMap([[SomeCommand::class, SomeCommand::class], [AnotherCommand::class, AnotherCommand::class]]);

        $routes = new CollectRoutes($this->messageAlias);

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($someRoute->getName(), $routes->matchOriginal(SomeCommand::class)->getName());
        $this->assertEquals($anotherRoute->getName(), $routes->matchOriginal(AnotherCommand::class)->getName());
    }

    public function testGetCloneRouteCollection(): void
    {
        $this->messageAlias->expects($this->any())
            ->method('classToAlias')
            ->willReturnMap([[SomeCommand::class, 'some-command'], [AnotherCommand::class, 'another-command']]);

        $routes = new CollectRoutes($this->messageAlias);

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($routes->getRoutes(), new Collection([$someRoute, $anotherRoute]));
        $this->assertNotSame($routes->getRoutes(), $routes->getRoutes());
    }

    public function testJsonSerializeRouteCollection(): void
    {
        $this->messageAlias->expects($this->any())
            ->method('classToAlias')
            ->willReturnMap([[SomeCommand::class, 'some-command'], [AnotherCommand::class, 'another-command']]);

        $routes = new CollectRoutes($this->messageAlias);

        $routes->addRoute(SomeCommand::class);
        $routes->addRoute(AnotherCommand::class);

        $this->assertEquals([
            [
                'message_name' => 'some-command',
                'original_message_name' => SomeCommand::class,
                'message_handlers' => [],
                'queue_options' => null,
            ],
            [
                'message_name' => 'another-command',
                'original_message_name' => AnotherCommand::class,
                'message_handlers' => [],
                'queue_options' => null,
            ],
        ], $routes->jsonSerialize());
    }
}
