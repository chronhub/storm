<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Stream\GapType;
use Chronhub\Storm\Projector\Support\Notification\Batch\BatchIncremented;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\GapDetected;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\RecoverableGapDetected;
use Chronhub\Storm\Projector\Support\Notification\Checkpoint\UnrecoverableGapDetected;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CurrentCycle;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleIncremented;
use Chronhub\Storm\Projector\Support\Notification\Cycle\CycleRenewed;
use Chronhub\Storm\Projector\Support\Notification\MasterCounter\CurrentMasterCount;
use Chronhub\Storm\Projector\Support\Notification\MasterCounter\KeepMasterCounterOnStop;
use Chronhub\Storm\Projector\Support\Notification\Sprint\SprintStopped;
use Chronhub\Storm\Projector\Support\Notification\Sprint\SprintTerminated;
use Chronhub\Storm\Projector\Support\Notification\Stream\NoEventStreamDiscovered;
use Chronhub\Storm\Projector\Support\Notification\Timer\CurrentTime;
use Chronhub\Storm\Projector\Support\Notification\Timer\GetElapsedTime;
use Closure;

use function method_exists;
use function ucfirst;

class StopWatcher
{
    const EMPTY_EVENT_STREAM = 'emptyEventStream';

    const GAP_DETECTED = 'gapDetected';

    const CYCLE_REACH = 'cycleReach';

    const COUNTER_REACH = 'counterReach';

    const TIME_EXPIRED = 'timeExpired';

    /**
     * @var array<class-string>
     */
    protected array $events = [];

    public function subscribe(NotificationHub $hub, array $callbacks): void
    {
        foreach ($callbacks as $name => $callback) {
            $method = 'stopWhen'.ucfirst($name);

            /**
             * @covers stopWhenEmptyEventStream
             * @covers stopWhenGapDetected
             * @covers stopWhenCounterReach
             * @covers stopWhenCycleReach
             * @covers stopWhenTimeExpired
             */
            if (! method_exists($this, $method)) {
                throw new InvalidArgumentException("Invalid stop watcher callback $name");
            }

            $this->events[] = $this->{$method}($hub, value($callback));
        }

        $hub->addListener(SprintTerminated::class, function (NotificationHub $hub): void {
            foreach ($this->events as $event) {
                $hub->forgetListener($event);
            }

            $this->events = [];
        });
    }

    protected function stopWhenEmptyEventStream(NotificationHub $hub, ?int $expiredAt): string
    {
        $listener = NoEventStreamDiscovered::class;

        $callback = $expiredAt === null
            ? fn (NotificationHub $hub) => $this->notifySprintStopped($hub)
            : $this->expirationCallback($expiredAt);

        $hub->addListener($listener, $callback);

        return $listener;
    }

    protected function stopWhenGapDetected(NotificationHub $hub, GapType $gapType): string
    {
        $listener = match ($gapType) {
            GapType::RECOVERABLE_GAP => RecoverableGapDetected::class,
            GapType::UNRECOVERABLE_GAP => UnrecoverableGapDetected::class,
            GapType::IN_GAP => GapDetected::class,
        };

        $hub->addListener($listener, fn (NotificationHub $hub) => $this->notifySprintStopped($hub));

        return $listener;
    }

    protected function stopWhenCounterReach(NotificationHub $hub, array $values): string
    {
        [$limit, $resetOnStop] = $values;
        $listener = BatchIncremented::class;

        $hub->notify(KeepMasterCounterOnStop::class, ! $resetOnStop);

        $hub->addListener($listener, function (NotificationHub $hub) use ($limit): void {
            $currentCount = $hub->expect(CurrentMasterCount::class);

            if ($limit <= $currentCount) {
                $this->notifySprintStopped($hub);
            }
        });

        return $listener;
    }

    protected function stopWhenCycleReach(NotificationHub $hub, int $expectedCycle): string
    {
        $listener = CycleIncremented::class;

        $hub->addListener($listener, function (NotificationHub $hub) use ($expectedCycle): void {
            $currentCycle = $hub->expect(CurrentCycle::class);

            if ($currentCycle === $expectedCycle) {
                $this->notifySprintStopped($hub);
            }
        });

        return $listener;
    }

    protected function stopWhenTimeExpired(NotificationHub $hub, int $expiredAt): string
    {
        $listener = CycleRenewed::class;

        $hub->addListener($listener, $this->expirationCallback($expiredAt));

        return $listener;
    }

    protected function expirationCallback(int $expiredAt): Closure
    {
        return function (NotificationHub $hub) use ($expiredAt): void {
            $currentTime = (int) $hub->expect(CurrentTime::class);
            $elapsedTime = (int) $hub->expect(GetElapsedTime::class);

            if ($expiredAt < $currentTime + $elapsedTime) {
                $this->notifySprintStopped($hub);
            }
        };
    }

    protected function notifySprintStopped(NotificationHub $hub): void
    {
        $hub->notify(SprintStopped::class);
    }
}
