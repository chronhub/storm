<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\Route;
use Illuminate\Support\Collection;
use Chronhub\Storm\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Chronhub\Storm\Routing\CollectRoutes;
use PHPUnit\Framework\MockObject\Exception;
use Chronhub\Storm\Tests\Double\SomeCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Chronhub\Storm\Tests\Double\AnotherCommand;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

final class CollectRoutesTest extends UnitTestCase
{
    private MockObject|MessageAlias $messageAlias;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->messageAlias = $this->createMock(MessageAlias::class);
    }

    #[Test]
    public function it_can_be_constructed_with_empty_routes(): void
    {
        $routes = new CollectRoutes($this->messageAlias);

        $this->assertTrue($routes->getRoutes()->isEmpty());
        $this->assertNotSame($routes->getRoutes(), $routes->getRoutes());
        $this->assertNull($routes->match('foo'));
    }

    #[Test]
    public function it_add_route_to_collection(): void
    {
        $this->messageAlias->expects($this->once())
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $route = $routes->addRoute(SomeCommand::class);

        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals('some-command', $route->getName());
        $this->assertEquals(SomeCommand::class, $route->getOriginalName());
    }

    #[Test]
    public function it_raise_exception_when_message_name_is_duplicate(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Message name already exists: '.SomeCommand::class);

        $this->messageAlias->expects($this->once())
            ->method('classToAlias')
            ->with(SomeCommand::class)
            ->willReturn('some-command');

        $routes = new CollectRoutes($this->messageAlias);

        $route = $routes->addRoute(SomeCommand::class);

        $this->assertEquals('some-command', $route->getName());

        $routes->addRoute(SomeCommand::class);
    }

    #[Test]
    public function it_find_routes_in_collection_with_message_class_name(): void
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

    #[Test]
    public function it_find_routes_in_collection_with_message_alias(): void
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

    #[Test]
    public function it_find_routes_in_collection_with_original_message_class_name(): void
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

    #[Test]
    public function it_access_a_clone_routes_collection(): void
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

    #[Test]
    public function it_serialize_routes(): void
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
