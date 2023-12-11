<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\Subscription\Subscription;

/**
 * @property Subscription $subscription
 */
interface Subscriber
{
    public function start(ContextReaderInterface $context, bool $keepRunning): void;

    /**
     * Return user state
     */
    public function getState(): array;
}
