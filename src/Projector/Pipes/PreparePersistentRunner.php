<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\Scheme\Context;

final class PreparePersistentRunner
{
    use RemoteStatusDiscovery;

    private bool $isInitialized = false;

    public function __invoke(Context $context, callable $next): callable|bool
    {
        if (! $this->isInitialized) {
            $this->isInitialized = true;

            if ($this->refresh(true, $context->runner->inBackground())) {
                return true;
            }

            $this->repository->rise();
        }

        return $next($context);
    }
}
