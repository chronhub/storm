<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Activity\DispatchSignal;
use Chronhub\Storm\Projector\Activity\HandleStreamEvent;
use Chronhub\Storm\Projector\Activity\HandleStreamGap;
use Chronhub\Storm\Projector\Activity\LoadStreams;
use Chronhub\Storm\Projector\Activity\PersistOrUpdate;
use Chronhub\Storm\Projector\Activity\RefreshProjection;
use Chronhub\Storm\Projector\Activity\ResetEventCounter;
use Chronhub\Storm\Projector\Activity\RisePersistentProjection;
use Chronhub\Storm\Projector\Activity\RiseQueryProjection;
use Chronhub\Storm\Projector\Activity\RunUntil;
use Chronhub\Storm\Projector\Activity\StopWhenRunningOnce;
use Chronhub\Storm\Projector\Scheme\EventProcessor;
use Chronhub\Storm\Projector\Subscription\EmitterSubscription;
use Chronhub\Storm\Projector\Subscription\QuerySubscription;
use Chronhub\Storm\Projector\Subscription\ReadModelSubscription;

final class ProvideActivities
{
    /**
     * @return array<callable>
     */
    public static function forPersistence(EmitterSubscription|ReadModelSubscription $subscription): array
    {
        return [
            new RunUntil(),
            new RisePersistentProjection(),
            new LoadStreams(),
            self::makeStreamEventHandler($subscription),
            new HandleStreamGap(),
            new PersistOrUpdate(),
            new ResetEventCounter(),
            new DispatchSignal(),
            new RefreshProjection(),
            new StopWhenRunningOnce(),
        ];
    }

    /**
     * @return array<callable>
     */
    public static function forQuery(QuerySubscription $subscription): array
    {
        return [
            new RunUntil(),
            new RiseQueryProjection(),
            new LoadStreams(),
            self::makeStreamEventHandler($subscription),
            new DispatchSignal(),
        ];
    }

    private static function makeStreamEventHandler(EmitterSubscription|ReadModelSubscription|QuerySubscription $subscription): callable
    {
        return new HandleStreamEvent(
            new EventProcessor(
                $subscription->context()->reactors(),
                $subscription->getScope()
            )
        );
    }
}
