<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\Rules\RequireAtLeastOneRoute;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;

final class RequireAtLeastOneRouteTest extends UnitTestCase
{
    public function testGroupHasAtLeastOneRoute(): void
    {
        $group = new Group(DomainType::COMMAND, 'command', new CollectRoutes(new AliasFromClassName()));
        $group->routes->addRoute(SomeCommand::class)->to(static fn (): null => null);

        $this->assertCount(1, $group->routes->getRoutes());

        $rule = new RequireAtLeastOneRoute();

        $rule->enforce($group);
    }

    public function testRaiseExceptionWhenGroupHasNoRoute(): void
    {
        $this->expectException(RoutingViolation::class);

        $group = new Group(DomainType::COMMAND, 'command', new CollectRoutes(new AliasFromClassName()));

        $this->assertCount(0, $group->routes->getRoutes());

        $rule = new RequireAtLeastOneRoute();

        $rule->enforce($group);
    }
}
