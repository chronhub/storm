<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Subscription\Notification\BatchCounterIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentCycle;
use Chronhub\Storm\Projector\Subscription\Notification\CurrentMasterCount;
use Chronhub\Storm\Projector\Subscription\Notification\CycleIncremented;
use Chronhub\Storm\Projector\Subscription\Notification\CycleRenew;
use Chronhub\Storm\Projector\Subscription\Notification\GapDetected;
use Chronhub\Storm\Projector\Subscription\Notification\KeepMasterCounterOnStop;
use Chronhub\Storm\Projector\Subscription\Notification\SprintStopped;

use function method_exists;
use function time;
use function ucfirst;

class StopWatcher
{
    const GAP_DETECTED = 'gapDetected';

    const AT_CYCLE = 'atCycle';

    const MASTER_COUNTER_LIMIT = 'masterCounterLimit';

    const EXPIRED_AT = 'expiredAt';

    private array $handlers = [];

    // todo on rerun, listeners still exists
    // todo forget handlers on stop
    public function getHandlers(): array
    {
        return $this->handlers;
    }

    public function subscribe(NotificationHub $hub, array $callbacks): void
    {
        foreach ($callbacks as $name => $callback) {
            $method = 'subscribe'.ucfirst($name);
            /**
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::subscribeGapDetected
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::subscribeMasterCounterLimit
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::subscribeAtCycle
             * @covers \Chronhub\Storm\Projector\Workflow\Watcher\StopWatcher::subscribeExpiredAt
             */
            if (method_exists($this, $method)) {
                $this->{$method}($hub, value($callback));
            }
        }
    }

    protected function subscribeGapDetected(NotificationHub $hub): void
    {
        $handler = function (NotificationHub $hub): void {
            $this->notifySprintStopped($hub);
        };

        $this->handlers[GapDetected::class] = $handler;

        $hub->addListener(GapDetected::class, $handler);
    }

    protected function subscribeMasterCounterLimit(NotificationHub $hub, array $values): void
    {
        $handler = function (NotificationHub $hub) use ($values): void {
            $currentCount = $hub->expect(CurrentMasterCount::class);

            [$limit, $resetOnStop] = $values;

            $hub->notify(KeepMasterCounterOnStop::class, ! $resetOnStop);

            if ($limit <= $currentCount) {
                $this->notifySprintStopped($hub);
            }
        };

        $this->handlers[BatchCounterIncremented::class] = $handler;

        $hub->addListener(BatchCounterIncremented::class, $handler);
    }

    protected function subscribeAtCycle(NotificationHub $hub, int $expectedCycle): void
    {
        $handler = function (NotificationHub $hub) use ($expectedCycle): void {
            $currentCycle = $hub->expect(CurrentCycle::class);

            if ($currentCycle === $expectedCycle) {
                $this->notifySprintStopped($hub);
            }
        };

        $this->handlers[CycleIncremented::class] = $handler;

        $hub->addListener(CycleIncremented::class, $handler);
    }

    protected function subscribeExpiredAt(NotificationHub $hub, int $expiredAt): void
    {
        $handler = function (NotificationHub $hub) use ($expiredAt): void {
            if ($expiredAt < time()) {
                $this->notifySprintStopped($hub);
            }
        };

        $this->handlers[] = $handler;

        $hub->addListener(CycleRenew::class, $handler);
    }

    protected function notifySprintStopped(NotificationHub $hub): void
    {
        $hub->notify(SprintStopped::class);
    }
}
