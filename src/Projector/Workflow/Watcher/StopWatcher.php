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
    private array $listeners = [];

    public function subscribe(NotificationHub $hub, array $callbacks): void
    {
        foreach ($callbacks as $name => $callback) {
            $method = 'stopWhen'.ucfirst($name);

            /**
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::stopWhenGapDetected
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::stopWhenCounterReach
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::stopWhenCycleReach
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::stopWhenTimeExpired
             */
            if (! method_exists($this, $method)) {
                throw new InvalidArgumentException("Invalid stop watcher callback $name");
            }

            $this->{$method}($hub, value($callback));
        }

        $hub->addListener(SprintTerminated::class, function (NotificationHub $hub): void {
            foreach ($this->listeners as $event) {
                $hub->forgetListener($event);
            }

            $this->listeners = [];
        });
    }

    protected function stopWhenGapDetected(NotificationHub $hub): void
    {
        $this->listeners[] = GapDetected::class;

        $hub->addListener(GapDetected::class, function (NotificationHub $hub): void {
            $this->notifySprintStopped($hub);
        });
    }

    protected function stopWhenCounterReach(NotificationHub $hub, array $values): void
    {
        [$limit, $resetOnStop] = $values;

        $hub->notify(KeepMasterCounterOnStop::class, ! $resetOnStop);

        $this->listeners[] = BatchCounterIncremented::class;

        $hub->addListener(BatchCounterIncremented::class, function (NotificationHub $hub) use ($limit): void {
            $currentCount = $hub->expect(CurrentMasterCount::class);

            if ($limit <= $currentCount) {
                $this->notifySprintStopped($hub);
            }
        });
    }

    protected function stopWhenCycleReach(NotificationHub $hub, int $expectedCycle): void
    {
        $this->listeners[] = CycleIncremented::class;

        $hub->addListener(CycleIncremented::class, function (NotificationHub $hub) use ($expectedCycle): void {
            $currentCycle = $hub->expect(CurrentCycle::class);

            if ($currentCycle === $expectedCycle) {
                $this->notifySprintStopped($hub);
            }
        });
    }

    protected function stopWhenTimeExpired(NotificationHub $hub, int $expiredAt): void
    {
        $this->listeners[] = CycleChanged::class;

        $hub->addListener(CycleChanged::class, function (NotificationHub $hub) use ($expiredAt): void {
            $currentTime = (int) $hub->expect(CurrentTime::class);
            $elapsedTime = (int) $hub->expect(GetElapsedTime::class);

            if ($expiredAt < $currentTime + $elapsedTime) {
                $this->notifySprintStopped($hub);
            }
        });
    }

    protected function notifySprintStopped(NotificationHub $hub): void
    {
        $hub->notify(SprintStopped::class);
    }
}
