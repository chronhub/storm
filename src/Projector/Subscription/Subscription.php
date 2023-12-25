<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Contracts\Projector\ActivityFactory;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Contracts\Projector\UserState;
use Chronhub\Storm\Projector\ProjectionStatus;
use Chronhub\Storm\Projector\Support\EventCounter;
use Chronhub\Storm\Projector\Support\Loop;
use Chronhub\Storm\Projector\Workflow\InMemoryUserState;
use Chronhub\Storm\Projector\Workflow\Sprint;

final class Subscription
{
    public readonly Sprint $sprint;

    public readonly UserState $state;

    public readonly Loop $looper;

    private ?string $currentStreamName = null;

    private ProjectionStatus $status = ProjectionStatus::IDLE;

    public function __construct(
        public readonly ProjectionOption $option,
        public readonly ActivityFactory $activityFactory,
        public readonly ?EventCounter $eventCounter = null,
    ) {
        $this->state = new InMemoryUserState();
        $this->sprint = new Sprint();
        $this->looper = new Loop();
    }

    public function &currentStreamName(): ?string
    {
        return $this->currentStreamName;
    }

    public function setStreamName(string &$streamName): void
    {
        $this->currentStreamName = &$streamName;
    }

    public function currentStatus(): ProjectionStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectionStatus $status): void
    {
        $this->status = $status;
    }
}
