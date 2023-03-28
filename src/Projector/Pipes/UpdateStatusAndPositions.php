<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\Scheme\Context;

final class UpdateStatusAndPositions
{
    use RemoteStatusDiscovery;

    public function __invoke(Context $context, callable $next): callable|bool
    {
        $this->refresh(false, $context->runner->inBackground());

        $context->streamPosition->watch($context->queries());

        return $next($context);
    }
}
