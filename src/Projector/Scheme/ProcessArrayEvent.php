<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Reporter\DomainEvent;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Projector\ProjectorRepository;

final class ProcessArrayEvent extends EventProcessor
{
    public function __construct(private readonly array $eventHandlers,
                                private readonly ?MessageAlias $messageAlias = null)
    {
    }

    public function __invoke(Context $context, DomainEvent $event, int $key, ?ProjectorRepository $repository): bool
    {
        if (! $this->preProcess($context, $event, $key, $repository)) {
            return false;
        }

        if (null === $eventHandler = $this->determineEventHandler($event)) {
            if ($repository) {
                $this->persistOnReachedCounter($context, $repository);
            }

            return ! $context->runner->isStopped();
        }

        $state = $eventHandler($event, $context->state->get());

        return $this->afterProcess($context, $state, $repository);
    }

    private function determineEventHandler(DomainEvent $event): ?callable
    {
        if ($this->messageAlias) {
            return $this->eventHandlers[$this->messageAlias->classToAlias($event::class)];
        }

        return $this->eventHandlers[$event::class] ?? null;
    }
}
