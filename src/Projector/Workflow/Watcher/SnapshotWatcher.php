<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Contracts\Projector\NotificationHub;
use Chronhub\Storm\Projector\Stream\Checkpoint;
use Chronhub\Storm\Projector\Stream\ShouldSnapshotCheckpoint;
use Chronhub\Storm\Projector\Subscription\Management\SnapshotCheckpointCaptured;
use Closure;
use Illuminate\Support\Sleep;

class SnapshotWatcher
{
    /**
     * @var array<string, Closure>
     */
    protected array $callbacks = [];

    /**
     * @var array<string, int>
     */
    protected array $checkpointCreatedAt = [];

    public function __construct(
        protected ?SystemClock $clock,
        protected readonly ?int $everyPosition,
        protected readonly ?int $everySeconds,
        protected readonly ?int $usleep
    ) {
        if ($this->everyPosition) {
            $this->callbacks['position'] = $this->onPosition();
        }

        if ($this->everySeconds) {
            $this->callbacks['interval'] = $this->onInterval();
        }
    }

    public function subscribe(NotificationHub $hub): void
    {
        foreach ($this->callbacks as $callback) {
            $hub->addListener(ShouldSnapshotCheckpoint::class,
                function (NotificationHub $hub, ShouldSnapshotCheckpoint $event) use ($callback): void {
                    $isSnapshot = $callback($event->checkpoint);

                    $hub->notifyWhen($isSnapshot, fn () => $hub->trigger(new SnapshotCheckpointCaptured($event->checkpoint)));
                });
        }
    }

    protected function onInterval(): Closure
    {
        return function (Checkpoint $checkpoint): bool {
            $checkpointTime = $this->clock->toPointInTime($checkpoint->createdAt)->getTimestamp();

            if (! isset($this->checkpointCreatedAt[$checkpoint->streamName])) {
                $this->checkpointCreatedAt[$checkpoint->streamName] = $checkpointTime;
            }

            if (($this->checkpointCreatedAt[$checkpoint->streamName] + $this->everySeconds) < $checkpointTime) {
                unset($this->checkpointCreatedAt[$checkpoint->streamName]);

                return true;
            }

            if ($this->usleep) {
                Sleep::usleep($this->usleep);
            }

            return false;
        };
    }

    protected function onPosition(): Closure
    {
        return fn (Checkpoint $checkpoint): bool => $this->everyPosition && $checkpoint->position % $this->everyPosition === 0;
    }
}
