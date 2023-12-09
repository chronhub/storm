<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\Subscriber;
use Chronhub\Storm\Projector\Scheme\Profiler;
use Closure;
use Throwable;

final readonly class HandleProfiler
{
    public function __construct(private Profiler $profiler)
    {
    }

    public function __invoke(Subscriber $subscriber, callable $next): callable|bool
    {
        $response = null;
        $error = null;

        try {
            $this->profiler->start($subscriber);

            /** @var Closure|bool $response */
            $response = $next($subscriber);
        } catch (Throwable $e) {
            $error = $e;
        } finally {
            $this->profiler->end($subscriber, $error ?? null);
        }

        return $response ?? throw $error;
    }
}
