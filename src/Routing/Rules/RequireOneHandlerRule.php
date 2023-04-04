<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Rules;

use Chronhub\Storm\Contracts\Routing\RoutingRule;
use Chronhub\Storm\Routing\CommandGroup;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\QueryGroup;
use Chronhub\Storm\Routing\Route;
use function count;
use function sprintf;

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

        $routes->each(static function (Route $route) use ($group): void {
            if (count($route->getHandlers()) !== 1) {
                $message = 'Group type %s and name %s require one route handler only for message %s';

                throw new RoutingViolation(sprintf($message, $group->getType()->value, $group->name, $route->getName()));
            }
        });
    }
}
