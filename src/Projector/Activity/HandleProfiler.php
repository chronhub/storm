<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Projector\Scheme\Profiler;
use Chronhub\Storm\Projector\Subscription\Beacon;
use Closure;
use Throwable;

final readonly class HandleProfiler
{
    public function __construct(private Profiler $profiler)
    {
    }

    public function __invoke(Beacon $manager, callable $next): callable|bool
    {
        $response = null;
        $error = null;

        try {
            $this->profiler->start($manager);

            /** @var Closure|bool $response */
            $response = $next($manager);
        } catch (Throwable $e) {
            $error = $e;
        } finally {
            $this->profiler->end($manager, $error ?? null);
        }

        return $response ?? throw $error;
    }
}
