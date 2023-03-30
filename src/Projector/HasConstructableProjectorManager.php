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
use Chronhub\Storm\Contracts\Serializer\JsonSerializer;
use Chronhub\Storm\Projector\Repository\RepositoryLock;
use Chronhub\Storm\Contracts\Projector\ProjectionOption;
use Chronhub\Storm\Projector\Repository\StandaloneStore;
use Chronhub\Storm\Contracts\Projector\ProjectionProvider;
use Chronhub\Storm\Contracts\Chronicler\EventStreamProvider;
use Chronhub\Storm\Contracts\Projector\ProjectionQueryScope;
use Chronhub\Storm\Projector\Options\DefaultProjectionOption;
use function array_merge;

trait HasConstructableProjectorManager
{
    public function __construct(protected readonly Chronicler $chronicler,
                                protected readonly EventStreamProvider $eventStreamProvider,
                                protected readonly ProjectionProvider $projectionProvider,
                                protected readonly ProjectionQueryScope $queryScope,
                                protected readonly SystemClock $clock,
                                protected readonly JsonSerializer $jsonSerializer,
                                protected ProjectionOption|array $options = [])
    {
    }

    protected function createStore(Context $context, string $streamName): Store
    {
        return new StandaloneStore(
            $context,
            $this->projectionProvider,
            $this->createLock($context->option),
            $this->jsonSerializer,
            $streamName
        );
    }

    protected function createContext(array $options, $isPersistent): Context
    {
        $option = $this->createOption($options);

        $eventCounter = $isPersistent ? new EventCounter($option->getBlockSize()) : null;

        $streamPositions = new StreamPosition($this->eventStreamProvider);

        $gapDetector = $eventCounter ? $this->createGapDetector($streamPositions, $option) : null;

        return new Context($option, $streamPositions, $eventCounter, $gapDetector);
    }

    protected function createLock(ProjectionOption $option): RepositoryLock
    {
        return new RepositoryLock($this->clock, $option->getTimeout(), $option->getLockout());
    }

    protected function createOption(array $options): ProjectionOption
    {
        if ($this->options instanceof ProjectionOption) {
            return $this->options;
        }

        return new DefaultProjectionOption(...array_merge($this->options, $options));
    }

    protected function createGapDetector(StreamPosition $streamPosition, ProjectionOption $options): DetectGap
    {
        return new DetectGap(
            $streamPosition,
            $this->clock,
            $options->getRetries(),
            $options->getDetectionWindows()
        );
    }
}
