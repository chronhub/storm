<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Scheme\ConsumeWithSleepToken;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\NoStreamLoadedCounter;
use Chronhub\Storm\Projector\Scheme\QueryFilterResolver;
use Chronhub\Storm\Projector\Scheme\Timer;
use Chronhub\Storm\Projector\Subscription\Subscription;

use function array_map;
use function is_array;

abstract class AbstractActivityFactory implements ActivityFactory
{
    public function __invoke(Subscription $subscription, ProjectorScope $scope, ?PersistentManagement $management): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($subscription, $scope, $management)
        );
    }

    protected function getQueryFilterResolver(Subscription $subscription): QueryFilterResolver
    {
        return new QueryFilterResolver($subscription->context()->queryFilter());
    }

    protected function getNoStreamLoadedCounter(Subscription $subscription): NoStreamLoadedCounter
    {
        $sleep = $subscription->option->getSleep();

        if (is_array($sleep)) {
            $bucket = new ConsumeWithSleepToken($sleep[0], $sleep[1]);

            return new NoStreamLoadedCounter($bucket);
        }

        return new NoStreamLoadedCounter(null, $sleep);
    }

    protected function getEventProcessor(Subscription $subscription, ProjectorScope $scope, ?PersistentManagement $management): EventProcessor
    {
        return new EventProcessor($subscription->context()->reactors(), $scope, $management);
    }

    protected function getTimer(Subscription $subscription): Timer
    {
        return new Timer($subscription->clock, $subscription->context()->timer());
    }

    /**
     * @return array<callable>
     */
    abstract protected function activities(Subscription $subscription, ProjectorScope $scope, ?PersistentManagement $management): array;
}
