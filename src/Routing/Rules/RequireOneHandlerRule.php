<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Rules;

use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\Route;
use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Contracts\Routing\RoutingRule;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use function count;

final readonly class RequireOneHandlerRule implements RoutingRule
{
    public function enforce(Group $group): void
    {
        if ($group instanceof CommandGroup) {
            $this->assertRouteHandler($group);
        }

        if ($group instanceof QueryGroup) {
            $this->assertRouteHandler($group);
        }
    }

    private function assertRouteHandler(Group $group): void
    {
        $routes = $group->routes->getRoutes();

        if ($routes->isEmpty()) {
            return;
        }

        $routes->each(function (Route $route) use ($group): void {
            if (count($route->getHandlers()) !== 1) {
                $message = "Group type {$group->getType()->value} and name {$group->name}";
                $message .= " require one route handler only for message {$route->getName()}";

                throw new RoutingViolation($message);
            }
        });
    }
}
