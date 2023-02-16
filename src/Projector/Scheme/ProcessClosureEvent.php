<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Closure;
use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;

final class ProcessClosureEvent extends EventProcessor
{
    public function __construct(private readonly Closure $eventHandlers)
    {
    }

    public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository): bool
    {
        if (! $this->preProcess($context, $event, $key, $repository)) {
            return false;
        }

        $state = ($this->eventHandlers)($event, $context->state->get());

        return $this->afterProcess($context, $state, $repository);
    }
}
