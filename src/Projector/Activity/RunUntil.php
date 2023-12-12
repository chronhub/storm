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
        if (! $this->isTimerStarted()) {
            $this->startTimer();
        }

        $response = $next($subscription);

        if ($this->isTimerExpired()) {
            $subscription->sprint->stop(); // checkMe

            return false;
        }

        return $response;
    }

    private function startTimer(): void
    {
        if ($this->interval && ! $this->startTime instanceof DateTimeImmutable) {
            $this->startTime = $this->clock->now();
        }
    }

    private function isTimerExpired(): bool
    {
        if ($this->startTime === null) {
            return false;
        }

        return $this->clock->isNowSubGreaterThan($this->interval, $this->startTime);
    }

    private function isTimerStarted(): bool
    {
        if ($this->interval === null) {
            return true;
        }

        return $this->startTime instanceof DateTimeImmutable;
    }
}
