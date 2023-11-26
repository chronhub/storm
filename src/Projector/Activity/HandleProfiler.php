<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscription;
use Chronhub\Storm\Projector\Scheme\Profiler;
use Closure;
use Throwable;

final readonly class HandleProfiler
{
    public function __construct(private Profiler $profiler)
    {
    }

    public function __invoke(Subscription $subscription, Closure $next): Closure|bool
    {
        $response = null;
        $error = null;

        try {
            $this->profiler->start($subscription);

            /** @var callable|bool $response */
            $response = $next($subscription);
        } catch (Throwable $e) {
            $error = $e;
        } finally {
            $this->profiler->end($subscription, $error ?? null);
        }

        return $response ?? throw $error;
    }
}
