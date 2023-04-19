<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Rules;

use Chronhub\Storm\Contracts\Routing\RoutingRule;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;

final readonly class RequireAtLeastOneRoute implements RoutingRule
{
    public function enforce(Group $group): void
    {
        if ($group->routes->getRoutes()->isEmpty()) {
            throw new RoutingViolation(
                "Group type {$group->getType()->value} and name $group->name require at least one route"
            );
        }
    }
}
