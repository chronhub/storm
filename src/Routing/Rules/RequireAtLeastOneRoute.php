<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing\Rules;

use Chronhub\Storm\Contracts\Routing\RoutingRule;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;
use function sprintf;

final readonly class RequireAtLeastOneRoute implements RoutingRule
{
    public function enforce(Group $group): void
    {
        if ($group->routes->getRoutes()->isEmpty()) {
            throw new RoutingViolation(
                sprintf('Group type %s and name %s require at least one route', $group->getType()->value, $group->name)
            );
        }
    }
}
