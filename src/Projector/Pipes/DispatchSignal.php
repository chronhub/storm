<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\Scheme\Context;
use function pcntl_signal_dispatch;

final class DispatchSignal
{
    public function __invoke(Context $context, callable $next): callable|bool
    {
        if ($context->option->getSignal()) {
            pcntl_signal_dispatch();
        }

        return $next($context);
    }
}
