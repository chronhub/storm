<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\PersistentProjector;

final readonly class StopWhenRunningOnce
{
    public function __construct(private PersistentProjector $projector)
    {
    }

    public function __invoke(Context $context, callable $next): callable|bool
    {
        if (! $context->runner->inBackground() && ! $context->runner->isStopped()) {
            $this->projector->stop();
        }

        return $next($context);
    }
}
