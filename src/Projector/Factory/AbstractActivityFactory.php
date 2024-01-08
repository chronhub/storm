<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Workflow\Activity\LoadStreams;
use Chronhub\Storm\Projector\Workflow\QueryFilterResolver;
use Chronhub\Storm\Projector\Workflow\StreamEventReactor;

use function array_map;

abstract readonly class AbstractActivityFactory implements ActivityFactory
{
    public function __construct(protected Chronicler $chronicler)
    {
    }

    public function __invoke(Subscriptor $subscriptor, ProjectorScope $projectorScope): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($subscriptor, $projectorScope)
        );
    }

    protected function createQueryFilterResolver(Subscriptor $subscriptor): QueryFilterResolver
    {
        return new QueryFilterResolver($subscriptor->getContext()->queryFilter());
    }

    protected function createStreamEventReactor(Subscriptor $subscriptor, ProjectorScope $projectorScope): StreamEventReactor
    {
        return new StreamEventReactor(
            $subscriptor->getContext()->reactors(),
            $projectorScope,
            $subscriptor->option()->getSignal()
        );
    }

    protected function createStreamLoader(Subscriptor $subscriptor): LoadStreams
    {
        return new LoadStreams(
            $this->chronicler,
            $subscriptor->clock(),
            $subscriptor->option()->getLoadLimiter(),
            $this->createQueryFilterResolver($subscriptor) // todo should be configurable in integration
        );
    }

    /**
     * @return array<callable>
     */
    abstract protected function activities(Subscriptor $subscriptor, ProjectorScope $scope): array;
}
