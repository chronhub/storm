<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Projector\Iterator\MergeStreamIterator;
use Chronhub\Storm\Projector\Iterator\StreamIterator;
use Chronhub\Storm\Projector\Scheme\SleepDuration;
use Chronhub\Storm\Projector\Subscription\Subscription;
use Chronhub\Storm\Stream\StreamName;

use function array_keys;
use function array_values;

final class LoadStreams
{
    /**
     * @var callable
     */
    private $queryFilterResolver;

    public function __construct(
        callable $queryFilterResolver,
        private readonly ?SleepDuration $sleepDuration
    ) {
        $this->queryFilterResolver = $queryFilterResolver;
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        $streams = $this->readStreams($subscription);

        // checkMe pass stream iterator $next($subscription, $streams);

        $noStreams = $streams === [];

        if (! $noStreams) {
            $iterator = new MergeStreamIterator($subscription->clock, array_keys($streams), ...array_values($streams));

            $subscription->setStreamIterator($iterator);
        }

        $this->updateSleepDuration($noStreams);

        return $next($subscription);
    }

    /**
     * @return array<string,StreamIterator>
     */
    private function readStreams(Subscription $subscription): array
    {
        $streams = [];

        $loadLimiter = $subscription->option->getLoads();

        foreach ($subscription->streamManager->jsonSerialize() as $streamName => $streamPosition) {
            $queryFilter = ($this->queryFilterResolver)($streamName, $streamPosition + 1, $loadLimiter);

            try {
                $events = $subscription->chronicler->retrieveFiltered(new StreamName($streamName), $queryFilter);
                $streams[$streamName] = new StreamIterator($events);
            } catch (StreamNotFound) {
                continue;
            }
        }

        return $streams;
    }

    private function updateSleepDuration(bool $noStreams): void
    {
        if ($this->sleepDuration) {
            $noStreams ? $this->sleepDuration->increment() : $this->sleepDuration->reset();
        }
    }
}
