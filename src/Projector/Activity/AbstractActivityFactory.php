<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\PersistentManagement;
use Chronhub\Storm\Contracts\Projector\ProjectorScope;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Scheme\QueryFilterResolver;
use Chronhub\Storm\Projector\Scheme\SleepDuration;
use Chronhub\Storm\Projector\Subscription\Subscription;

use function array_map;

abstract class AbstractActivityFactory implements ActivityFactory
{
    public function __invoke(Subscription $subscription, ?PersistentManagement $management, ProjectorScope $scope): array
    {
        return array_map(
            fn (callable $activity): callable => $activity(),
            $this->activities($subscription, $management, $scope)
        );
    }

    protected function getQueryFilterResolver(Subscription $subscription): QueryFilterResolver
    {
        return new QueryFilterResolver($subscription->context()->queryFilter());
    }

    protected function getSleepDuration(Subscription $subscription): ?SleepDuration
    {
        $sleep = $subscription->option->getSleep();

        if ($sleep > 0) {
            return new SleepDuration($subscription->option->getSleep(), $subscription->option->getIncrementSleep());
        }

        return null;
    }

    protected function getEventProcessor(Subscription $subscription, ?PersistentManagement $management, ProjectorScope $scope): EventProcessor
    {
        return new EventProcessor($subscription->context()->reactors(), $scope, $management);
    }

    abstract protected function activities(Subscription $subscription, ?PersistentManagement $management, ProjectorScope $scope): array;
}
