<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Chronhub\Storm\Routing\Group;

interface RoutingRule
{
    /**
     * @throws RoutingViolation
     */
    public function enforce(Group $group): void;
}
