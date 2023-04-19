<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Unit\Routing;

use Chronhub\Storm\Message\AliasFromClassName;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\CollectRoutes;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\Rules\RequireOneHandlerRule;
use Chronhub\Storm\Tests\Stubs\Double\SomeCommand;
use Chronhub\Storm\Tests\UnitTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

final class RequireOneHandlerRuleTest extends UnitTestCase
{
    #[DataProvider('provideGroup')]
    public function testGroupRouteHasOneHandlerOnly(Group $group): void
    {
        $route = $group->routes->addRoute(SomeCommand::class)->to(fn () => null);

        $this->assertCount(1, $route->getHandlers());

        $rule = new RequireOneHandlerRule();

        $rule->enforce($group);
    }

    #[DataProvider('provideGroup')]
    public function testExceptionRaisedWhenGroupRouteDoesNotMetCondition(Group $group): void
    {
        $this->expectException(RoutingViolation::class);

        $route = $group->routes->addRoute(SomeCommand::class);

        $this->assertEmpty($route->getHandlers());

        $rule = new RequireOneHandlerRule();

        $rule->enforce($group);
    }

    #[DataProvider('provideGroup')]
    public function testGroupRouteDoesNotEnforceWithNoRouteSet(Group $group): void
    {
        $this->assertEmpty($group->routes->getRoutes());

        $rule = new RequireOneHandlerRule();

        $rule->enforce($group);
    }

    public static function provideGroup(): Generator
    {
        yield [new Group(DomainType::COMMAND, 'command', new CollectRoutes(new AliasFromClassName()))];
        yield [new Group(DomainType::QUERY, 'command', new CollectRoutes(new AliasFromClassName()))];
    }
}
