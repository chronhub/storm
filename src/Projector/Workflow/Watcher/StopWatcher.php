<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentCycle;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentMasterCount;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentTime;
use Chronhub\Storm\Projector\Subscription\Notification\CycleChanged;
use Chronhub\Storm\Projector\Subscription\Notification\CycleIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\GapDetected;
use Chronhub\Storm\Projector\Subscription\Notification\GetElapsedTime;
use Chronhub\Storm\Projector\Subscription\Notification\KeepMasterCounterOnStop;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;
use Chronhub\Storm\Projector\Subscription\Notification\SprintTerminated;

use function method_exists;
use function ucfirst;

class StopWatcher
{
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

    protected function stopWhenGapDetected(NotificationHub $hub): string
    {
        $listener = GapDetected::class;

        $hub->addListener($listener, function (NotificationHub $hub): void {
            $this->notifySprintStopped($hub);
        });

        return $listener;
    }

    protected function stopWhenCounterReach(NotificationHub $hub, array $values): string
    {
        [$limit, $resetOnStop] = $values;
        $listener = BatchCounterIncremented::class;

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
        $listener = CycleChanged::class;

        $hub->addListener($listener, function (NotificationHub $hub) use ($expiredAt): void {
            $currentTime = (int) $hub->expect(CurrentTime::class);
            $elapsedTime = (int) $hub->expect(GetElapsedTime::class);

            if ($expiredAt < $currentTime + $elapsedTime) {
                $this->notifySprintStopped($hub);
            }
        });

        return $listener;
    }

    protected function notifySprintStopped(NotificationHub $hub): void
    {
        $hub->notify(SprintStopped::class);
    }
}
