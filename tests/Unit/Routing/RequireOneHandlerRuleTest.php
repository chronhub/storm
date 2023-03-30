<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Tests\UnitTestCase;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Rules\RequireOneHandlerRule;

final class RequireOneHandlerRuleTest extends UnitTestCase
{
    public function testGroupRouteHasOneHandlerOnly(): void
    {
        $group = new CommandGroup('command', new CollectRoutes(new AliasFromClassName()));
        $route = $group->routes->addRoute(SomeCommand::class)->to(fn () => null);

        $this->assertCount(1, $route->getHandlers());

        $rule = new RequireOneHandlerRule();

        $rule->enforce($group);
    }

    public function testExceptionRaisedWhenGroupRouteDoesNotMetCondition(): void
    {
        $this->expectException(RoutingViolation::class);

        $group = new CommandGroup('command', new CollectRoutes(new AliasFromClassName()));
        $route = $group->routes->addRoute(SomeCommand::class);

        $this->assertEmpty($route->getHandlers());

        $rule = new RequireOneHandlerRule();

        $rule->enforce($group);
    }

    public function testGroupRouteDoesNotEnforceWithNoRouteSet(): void
    {
        $group = new CommandGroup('command', new CollectRoutes(new AliasFromClassName()));

        $this->assertEmpty($group->routes->getRoutes());

        $rule = new RequireOneHandlerRule();

        $rule->enforce($group);
    }
}
