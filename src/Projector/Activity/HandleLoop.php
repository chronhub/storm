<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Subscription\Subscription;
use Closure;

/**
 * Every activity that interacts with the running process
 * must be registered in the workflow after this class.
 */

/**
 * @deprecated
 */
final readonly class HandleLoop
{
    public function __invoke(Subscription $subscription, Closure $next): callable|bool
    {
        // checkMe: setup looper in RunProjection, but need to bring the subscription

        if (! $subscription->looper->hasStarted()) {
            $subscription->looper->start();
        }

        $response = $next($subscription);

        if (! $subscription->sprint->inBackground() || ! $subscription->sprint->inProgress()) {
            $subscription->looper->reset();
        } else {
            $subscription->looper->next();
        }

        return $response;
    }
}
