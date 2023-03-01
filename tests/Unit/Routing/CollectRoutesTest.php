<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Routing\Route;
use Illuminate\Support\Collection;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Tests\ProphecyTestCase;
use Chronhub\Storm\Tests\Double\SomeCommand;
use Chronhub\Storm\Tests\Double\AnotherCommand;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

final class CollectRoutesTest extends ProphecyTestCase
{
    private ObjectProphecy|MessageAlias $messageAlias;

    public function setUp(): void
    {
        parent::setUp();

        $this->messageAlias = $this->prophesize(MessageAlias::class);
    }

    /**
     * @test
     */
    public function it_can_be_constructed_with_empty_routes(): void
    {
        $routes = new CollectRoutes($this->messageAlias->reveal());

        $this->assertTrue($routes->getRoutes()->isEmpty());
        $this->assertNotSame($routes->getRoutes(), $routes->getRoutes());
        $this->assertNull($routes->match('foo'));
    }

    /**
     * @test
     */
    public function it_add_route_to_collection(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $route = $routes->addRoute(SomeCommand::class);

        $this->assertInstanceOf(Route::class, $route);

        $this->assertEquals('some-command', $route->getName());
        $this->assertEquals(SomeCommand::class, $route->getOriginalName());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_name_is_duplicate(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Message name already exists: '.SomeCommand::class);

        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $route = $routes->addRoute(SomeCommand::class);

        $this->assertEquals('some-command', $route->getName());

        $routes->addRoute(SomeCommand::class);
    }

    /**
     * @test
     */
    public function it_add_route_instance_to_collection(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->shouldNotBeCalled();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $route = new Route(SomeCommand::class);
        $route->alias('some-command');

        $routes->addRouteInstance($route);

        $this->assertEquals('some-command', $route->getName());
        $this->assertEquals(SomeCommand::class, $route->getOriginalName());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_instance_is_duplicate(): void
    {
        $this->expectException(RoutingViolation::class);
        $this->expectExceptionMessage('Message name already exists: '.SomeCommand::class);

        $this->messageAlias->classToAlias(SomeCommand::class)->shouldNotBeCalled();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $route = new Route(SomeCommand::class);
        $route->alias('some-command');

        $routes->addRouteInstance($route);
        $routes->addRouteInstance($route);
    }

    /**
     * @test
     */
    public function it_find_routes_in_collection_with_message_class_name(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn(SomeCommand::class)->shouldBeCalledOnce();
        $this->messageAlias->classToAlias(AnotherCommand::class)->willReturn(AnotherCommand::class)->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($someRoute->getName(), $routes->match(SomeCommand::class)->getName());
        $this->assertEquals($anotherRoute->getName(), $routes->match(AnotherCommand::class)->getName());
    }

    /**
     * @test
     */
    public function it_find_routes_in_collection_with_message_alias(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();
        $this->messageAlias->classToAlias(AnotherCommand::class)->willReturn('another-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($someRoute->getName(), $routes->match('some-command')->getName());
        $this->assertEquals($anotherRoute->getName(), $routes->match('another-command')->getName());
    }

    /**
     * @test
     */
    public function it_find_routes_in_collection_with_original_message_class_name(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();
        $this->messageAlias->classToAlias(AnotherCommand::class)->willReturn('another-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($someRoute->getName(), $routes->matchOriginal(SomeCommand::class)->getName());
        $this->assertEquals($anotherRoute->getName(), $routes->matchOriginal(AnotherCommand::class)->getName());
    }

    /**
     * @test
     */
    public function it_access_a_clone_routes_collection(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();
        $this->messageAlias->classToAlias(AnotherCommand::class)->willReturn('another-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

        $someRoute = $routes->addRoute(SomeCommand::class);
        $anotherRoute = $routes->addRoute(AnotherCommand::class);

        $this->assertEquals($routes->getRoutes(), new Collection([$someRoute, $anotherRoute]));
        $this->assertNotSame($routes->getRoutes(), $routes->getRoutes());
    }

    /**
     * @test
     */
    public function it_serialize_routes(): void
    {
        $this->messageAlias->classToAlias(SomeCommand::class)->willReturn('some-command')->shouldBeCalledOnce();
        $this->messageAlias->classToAlias(AnotherCommand::class)->willReturn('another-command')->shouldBeCalledOnce();

        $routes = new CollectRoutes($this->messageAlias->reveal());

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
