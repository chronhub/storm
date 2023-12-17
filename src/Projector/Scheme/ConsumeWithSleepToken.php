<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function max;
use function usleep;

final class ConsumeWithSleepToken extends AbstractTokenBucket
{
    protected function handleConsume(float $tokens = 1): bool
    {
        if ($tokens > $this->capacity) {
            throw new InvalidArgumentException('Requested tokens exceed the capacity of the token bucket.');
        }

        while (! $this->doConsume($tokens)) {
            $remainingTime = $this->getRemainingTimeUntilNextToken($tokens);

            $us = (int) ($remainingTime * 1000000);

            // dump('Sleep for: '.$remainingTime);

            usleep($us);
        }

        return true;
    }

    private function getRemainingTimeUntilNextToken(float $tokens = 1): float
    {
        // Ensure tokens are up to-date
        $this->refillTokens();

        // Calculate the time required to accumulate the required number of tokens
        return max(0, ($tokens - $this->tokens) / $this->rate);
    }
}
