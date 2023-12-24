<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Factory;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\Management;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Projector\Support\NoEventStreamCounter;
use Chronhub\Storm\Projector\Support\Timer;
use Chronhub\Storm\Projector\Support\Token\ConsumeWithSleepToken;
use Chronhub\Storm\Projector\Workflow\EventProcessor;
use Chronhub\Storm\Projector\Workflow\QueryFilterResolver;

use function array_map;
use function is_array;

abstract class AbstractActivityFactory implements ActivityFactory
{
    public function __invoke(Subscription $subscription, ProjectorScope $scope, Management $management): array
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

    protected function getNoStreamLoadedCounter(Subscription $subscription): NoEventStreamCounter
    {
        $sleep = $subscription->option->getSleep();

        if (is_array($sleep)) {
            $bucket = new ConsumeWithSleepToken($sleep[0], $sleep[1]);

            return new NoEventStreamCounter($bucket);
        }

        return new NoEventStreamCounter(null, $sleep);
    }

    protected function getEventProcessor(Subscription $subscription, ProjectorScope $scope, Management $management): EventProcessor
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
    abstract protected function activities(Subscription $subscription, ProjectorScope $scope, Management $management): array;
}
