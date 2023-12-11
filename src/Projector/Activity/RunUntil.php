<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Activity;

use Chronhub\Storm\Contracts\Clock\SystemClock;
use Chronhub\Storm\Projector\Subscription\Subscription;
use DateInterval;
use DateTimeImmutable;

final class RunUntil
{
    private ?DateTimeImmutable $startTime = null;

    public function __construct(
        private readonly SystemClock $clock,
        private readonly ?DateInterval $interval
    ) {
    }

    public function __invoke(Subscription $subscription, callable $next): callable|bool
    {
        if (! $this->isStarted()) {
            $this->start();
        }

        $response = $next($subscription);

        if ($this->isElapsed()) {
            $subscription->sprint->stop(); // checkMe

            return false;
        }

        return $response;
    }

    private function start(): void
    {
        if ($this->interval && ! $this->startTime instanceof DateTimeImmutable) {
            $this->startTime = $this->clock->now();
        }
    }

    private function isElapsed(): bool
    {
        if ($this->startTime === null) {
            return false;
        }

        return $this->clock->isNowSubGreaterThan($this->interval, $this->startTime);
    }

    private function isStarted(): bool
    {
        if ($this->interval === null) {
            return true;
        }

        return $this->startTime instanceof DateTimeImmutable;
    }
}
