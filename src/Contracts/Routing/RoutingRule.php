<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Chronhub\Storm\Routing\Group;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;

interface RoutingRule
{
    /**
     * @throws RoutingViolation
     */
    public function enforce(Group $group): void;
}
