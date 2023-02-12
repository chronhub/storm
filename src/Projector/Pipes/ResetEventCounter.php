<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\Scheme\Context;

final class ResetEventCounter
{
    public function __invoke(Context $context, callable $next): callable|bool
    {
        $context->eventCounter->reset();

        return $next($context);
    }
}
