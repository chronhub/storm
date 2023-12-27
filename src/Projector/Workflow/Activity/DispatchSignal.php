<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Activity;

use Chronhub\Storm\Contracts\Projector\HookHub;

use function pcntl_signal_dispatch;

final readonly class DispatchSignal
{
    public function __construct(private bool $dispatchSignal)
    {
    }

    public function __invoke(HookHub $hub, callable $next): callable|bool
    {
        if ($this->dispatchSignal) {
            pcntl_signal_dispatch();
        }

        return $next($hub);
    }
}
