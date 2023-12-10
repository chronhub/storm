<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Scheme\Profiler;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Closure;
use Throwable;

final readonly class HandleProfiler
{
    public function __construct(private Profiler $profiler)
    {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $response = null;
        $error = null;

        try {
            $this->profiler->start($subscription);

            /** @var Closure|bool $response */
            $response = $next($subscription);
        } catch (Throwable $e) {
            $error = $e;
        } finally {
            $this->profiler->end($subscription, $error ?? null);
        }

        return $response ?? throw $error;
    }
}
