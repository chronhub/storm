<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Closure;
use Psr\Container\ContainerInterface;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Tracker\StreamTracker;
use Chronhub\Storm\Contracts\Chronicler\StreamSubscriber;
use Chronhub\Storm\Contracts\Chronicler\ChroniclerProvider;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;
use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use function is_string;

abstract class AbstractChroniclerProvider implements ChroniclerProvider
{
    protected ContainerInterface $container;

    public function __construct(Closure $app)
    {
        $this->container = $app();
    }

    protected function decorateChronicler(Chronicler $chronicler, ?StreamTracker $tracker): EventableChronicler|TransactionalEventableChronicler
    {
        if ($chronicler instanceof EventableChronicler) {
            throw new InvalidArgumentException('Unable to decorate a chronicler which is already decorated');
        }

        if (! $tracker instanceof StreamTracker) {
            throw new InvalidArgumentException(
                'Unable to decorate chronicler '.$chronicler::class.', stream tracker is not defined or invalid'
            );
        }

        if ($tracker instanceof TransactionalStreamTracker && $chronicler instanceof TransactionalChronicler) {
            return new TransactionalEventChronicler($chronicler, $tracker);
        }

        if (! $tracker instanceof TransactionalStreamTracker && ! $chronicler instanceof TransactionalChronicler) {
            return new EventChronicler($chronicler, $tracker);
        }

        throw new InvalidArgumentException('Invalid configuration to decorate chronicler from chronicler provider: '.static::class);
    }

    protected function resolveStreamTracker(array $config): ?StreamTracker
    {
        $streamTracker = $config['tracking']['tracker_id'] ?? null;

        if (is_string($streamTracker)) {
            $streamTracker = $this->container->get($streamTracker);
        }

        return $streamTracker;
    }

    protected function attachStreamSubscribers(EventableChronicler $chronicler, array $streamSubscribers): void
    {
        foreach ($streamSubscribers as $streamSubscriber) {
            if (! $streamSubscriber instanceof StreamSubscriber) {
                $streamSubscriber = $this->container->get($streamSubscriber);
            }

            $streamSubscriber->attachToChronicler($chronicler);
        }
    }
}
