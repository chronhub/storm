<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Pipes;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;

final readonly class HandleGap
{
    public function __construct(private ProjectorRepository $repository)
    {
    }

    public function __invoke(Context $context, callable $next): callable|bool
    {
        if ($context->gap->hasGap()) {
            $context->gap->sleep();

            $this->repository->store();
        }

        return $next($context);
    }
}
