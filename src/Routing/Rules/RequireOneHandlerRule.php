<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Rules;

use Chronhub\Storm\Contracts\Routing\RoutingRule;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\Route;

use function count;

final readonly class RequireOneHandlerRule implements RoutingRule
{
    public function enforce(Group $group): void
    {
        $match = match ($group->getType()) {
            DomainType::COMMAND, DomainType::QUERY => true,
            default => false
        };

        if ($match) {
            $this->assertRouteHandler($group);
        }
    }

    private function assertRouteHandler(Group $group): void
    {
        $routes = $group->routes->getRoutes();

        if ($routes->isEmpty()) {
            return;
        }

        $routes->each(static function (Route $route) use ($group): void {
            if (count($route->getHandlers()) !== 1) {
                throw new RoutingViolation(
                    "Group type {$group->getType()->value} and name $group->name require one route handler only for message {$route->getName()}"
                );
            }
        });
    }
}
