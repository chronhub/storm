<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriptor;

final readonly class ResetEventCounter
{
    public function __invoke(Subscriptor $subscriptor, callable $next): callable|bool
    {
        $subscriptor->resetEvent();

        return $next($subscriptor);
    }
}
