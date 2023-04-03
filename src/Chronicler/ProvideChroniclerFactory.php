<?php

declare(strict_types=1);

namespace Chronhub\Storm\Chronicler;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Tracker\StreamTracker;
use Chronhub\Storm\Contracts\Chronicler\StreamSubscriber;
use Chronhub\Storm\Contracts\Chronicler\EventableChronicler;
use Chronhub\Storm\Contracts\Chronicler\TransactionalChronicler;
use Chronhub\Storm\Contracts\Tracker\TransactionalStreamTracker;
use Chronhub\Storm\Chronicler\Exceptions\InvalidArgumentException;
use Chronhub\Storm\Contracts\Chronicler\TransactionalEventableChronicler;
use function sprintf;
use function is_string;

trait ProvideChroniclerFactory
{
    protected ContainerInterface $container;

    protected function decorateChronicler(Chronicler $chronicler, ?StreamTracker $tracker): EventableChronicler|TransactionalEventableChronicler
    {
        if ($chronicler instanceof EventableChronicler) {
            throw new InvalidArgumentException('Unable to decorate a chronicler which is already decorated');
        }

        if (! $tracker instanceof StreamTracker) {
            throw new InvalidArgumentException(
                sprintf('Unable to decorate chronicler %s, stream tracker is not defined or invalid', $chronicler::class)
            );
        }

        if ($tracker instanceof TransactionalStreamTracker && $chronicler instanceof TransactionalChronicler) {
            return new TransactionalEventChronicler($chronicler, $tracker);
        }

        if (! $tracker instanceof TransactionalStreamTracker && ! $chronicler instanceof TransactionalChronicler) {
            return new EventChronicler($chronicler, $tracker);
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid configuration to decorate chronicler from chronicler provider: %s', static::class
        ));
    }

    protected function resolveStreamTracker(array $config): ?StreamTracker
    {
        $streamTracker = $config['tracking']['tracker_id'] ?? null;

        if (is_string($streamTracker)) {
            $streamTracker = $this->container->get($streamTracker);
        }

        return $streamTracker instanceof StreamTracker ? $streamTracker : null;
    }

    /**
     * @param  array<StreamSubscriber|string>  $streamSubscribers
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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
