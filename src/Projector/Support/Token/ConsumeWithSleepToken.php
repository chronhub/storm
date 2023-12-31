<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Token;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;
use Illuminate\Support\Sleep;

use function max;
use function microtime;
use function min;

final class ConsumeWithSleepToken extends AbstractTokenBucket
{
    protected function handleConsume(float $tokens = 1): bool
    {
        if ($tokens > $this->capacity) {
            throw new InvalidArgumentException('Requested tokens exceed the capacity of the token bucket.');
        }

        // overflow the bucket
        $this->doConsume($this->capacity);

        while (! $this->doConsume($tokens)) {
            $remainingTime = $this->getRemainingTimeUntilNextToken($tokens);

            $us = (int) ($remainingTime * 1000000);

            //dump('Sleep for: '.$remainingTime);

            Sleep::usleep($us);
        }

        return true;
    }

    /**
     * Calculate the time required to accumulate the required number of tokens.
     */
    private function getRemainingTimeUntilNextToken(float $tokens = 1): float
    {
        $this->refillTokens();

        return max(0, ($tokens - $this->tokens) / $this->rate);
    }

    protected function refillTokens(): void
    {
        $now = microtime(true);
        $timePassed = max(0, $now - $this->lastRefillTime);
        $this->tokens = min($this->capacity, $this->tokens + $timePassed * $this->rate);
        $this->lastRefillTime = $now;
    }
}
