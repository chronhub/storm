<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\Scheme\Context;

final class PrepareQueryRunner
{
    private bool $isInitiated = false;

    public function __invoke(Context $context, callable $next): callable|bool
    {
        if (! $this->isInitiated) {
            $this->isInitiated = true;

            $context->streamPosition->watch($context->queries());
        }

        return $next($context);
    }
}
