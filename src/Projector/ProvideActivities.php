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

final class ProvideActivities
{
    /**
     * @return array<callable>
     */
    public static function persistent(ProjectEmitter|ProjectReadModel $projector): array
    {
        return [
            new RunUntil(),
            new RisePersistentProjection(),
            new LoadStreams(),
            self::makeStreamEventHandler($projector),
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
    public static function query(ProjectQuery $projector): array
    {
        return [
            new RunUntil(),
            new RiseQueryProjection(),
            new LoadStreams(),
            self::makeStreamEventHandler($projector),
            new DispatchSignal(),
        ];
    }

    private static function makeStreamEventHandler(ProjectQuery|ProjectEmitter|ProjectReadModel $projector): callable
    {
        return new HandleStreamEvent(
            new EventProcessor(
                $projector->subscription()->context()->reactors(),
                $projector->getScope()
            )
        );
    }
}
