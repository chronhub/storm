<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Contracts\Projector\Subscriptor;
use Chronhub\Storm\Projector\Support\NoEventStreamCounter;
use Chronhub\Storm\Projector\Support\Timer;
use Chronhub\Storm\Projector\Support\Token\ConsumeWithSleepToken;
use Chronhub\Storm\Projector\Workflow\EventProcessor;
use Chronhub\Storm\Projector\Workflow\QueryFilterResolver;

use function array_map;
use function is_array;

abstract readonly class AbstractActivityFactory implements ActivityFactory
{
    public function __construct(protected Chronicler $chronicler)
    {
    }

    public function __invoke(Subscriptor $subscriptor, ProjectorScope $scope, Management $management): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($subscriptor, $scope, $management)
        );
    }

    protected function getQueryFilterResolver(Subscriptor $subscriptor): QueryFilterResolver
    {
        return new QueryFilterResolver($subscriptor->getContext()->queryFilter());
    }

    protected function getNoStreamLoadedCounter(Subscriptor $subscriptor): NoEventStreamCounter
    {
        $sleep = $subscriptor->option()->getSleep();

        if (is_array($sleep)) {
            $bucket = new ConsumeWithSleepToken($sleep[0], $sleep[1]);

            return new NoEventStreamCounter($bucket);
        }

        return new NoEventStreamCounter(null, $sleep);
    }

    protected function getEventProcessor(Subscriptor $subscriptor, ProjectorScope $scope, Management $management): EventProcessor
    {
        return new EventProcessor($subscriptor->getContext()->reactors(), $scope, $management);
    }

    protected function getTimer(Subscriptor $subscriptor): Timer
    {
        return new Timer($subscriptor->clock(), $subscriptor->getContext()->timer());
    }

    /**
     * @return array<callable>
     */
    abstract protected function activities(Subscriptor $subscriptor, ProjectorScope $scope, Management $management): array;
}
