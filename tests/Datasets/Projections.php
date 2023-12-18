<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Datasets;

use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Repository\Events\ProjectionCreated;
use Chronhub\Storm\Projector\Repository\Events\ProjectionDeleted;
use Chronhub\Storm\Projector\Repository\Events\ProjectionDeletedWithEvents;
use Chronhub\Storm\Projector\Repository\Events\ProjectionReset;
use Chronhub\Storm\Projector\Repository\Events\ProjectionRestarted;
use Chronhub\Storm\Projector\Repository\Events\ProjectionStarted;
use Chronhub\Storm\Projector\Repository\Events\ProjectionStopped;

dataset('projection status', [
    'idle' => ProjectionStatus::IDLE,
    'running' => ProjectionStatus::RUNNING,
    'stopping' => ProjectionStatus::STOPPING,
    'resetting' => ProjectionStatus::RESETTING,
    'deleting' => ProjectionStatus::DELETING,
    'deleting with emitted events' => ProjectionStatus::DELETING_WITH_EMITTED_EVENTS,
]);

dataset('projection status monitored', [
    'stopping' => [ProjectionStatus::STOPPING->value],
    'resetting' => [ProjectionStatus::RESETTING->value],
    'deleting' => [ProjectionStatus::DELETING->value],
    'deleting_with_emitted_events' => [ProjectionStatus::DELETING_WITH_EMITTED_EVENTS->value],
]);

dataset('projection dispatcher events', [
    'created' => ProjectionCreated::class,
    'started' => ProjectionStarted::class,
    'stopped' => ProjectionStopped::class,
    'restarted' => ProjectionRestarted::class,
    'reset' => ProjectionReset::class,
    'deleted' => ProjectionDeleted::class,
    'deleted with emitted events' => ProjectionDeletedWithEvents::class,
]);
