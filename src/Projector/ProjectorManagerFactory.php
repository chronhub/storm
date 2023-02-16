<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Projector\Scheme\Context;
use Chronhub\Storm\Contracts\Projector\Store;
use Chronhub\Storm\Projector\Scheme\DetectGap;
use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Scheme\EventCounter;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Projector\Scheme\StreamPosition;
use Chronhub\Storm\Contracts\Projector\ProjectorOption;
use Chronhub\Storm\Projector\Repository\RepositoryLock;
use Chronhub\Storm\Projector\Repository\StandaloneStore;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Projector\Options\DefaultProjectorOption;
use function array_merge;

abstract class ProjectorManagerFactory
{
    public function __construct(public readonly Chronicler $chronicler,
                                public readonly EventStreamProvider $eventStreamProvider,
                                public readonly ProjectionProvider $projectionProvider,
                                public readonly ProjectionQueryScope $queryScope,
                                public readonly SystemClock $clock,
                                public ProjectorOption|array $options = [])
    {
    }

    public function createStore(Context $context, string $streamName): Store
    {
        return new StandaloneStore(
            $context,
            $this->projectionProvider,
            $this->makeProjectorLock($context->option),
            $streamName
        );
    }

    public function createContext(array $options, ?EventCounter $eventCounter): Context
    {
        $option = $this->makeOption($options);

        $streamPositions = new StreamPosition($this->eventStreamProvider);

        $gapDetector = $eventCounter ? $this->makeGapDetector($streamPositions, $option) : null;

        return new Context($option, $streamPositions, $eventCounter, $gapDetector);
    }

    protected function makeProjectorLock(ProjectorOption $option): RepositoryLock
    {
        return new RepositoryLock(
            $this->clock,
            $option->lockTimeoutMs,
            $option->updateLockThreshold
        );
    }

    protected function makeOption(array $option): ProjectorOption
    {
        if ($this->options instanceof ProjectorOption) {
            return $this->options;
        }

        return new DefaultProjectorOption(...array_merge($this->options, $option));
    }

    protected function makeGapDetector(StreamPosition $streamPosition, ProjectorOption $option): DetectGap
    {
        return new DetectGap(
            $streamPosition,
            $this->clock,
            $option->retriesMs,
            $option->detectionWindows
        );
    }
}
