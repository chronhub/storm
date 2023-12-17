<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

use Chronhub\Storm\Contracts\Projector\TokenBucket;
use Chronhub\Storm\Projector\Exceptions\InvalidArgumentException;

use function microtime;
use function min;

abstract class AbstractTokenBucket implements TokenBucket
{
    protected float $tokens;

    protected float $lastRefillTime;

    public function __construct(
        public readonly float $capacity,
        public readonly float $rate
    ) {
        if ($capacity <= 0 || $rate <= 0) {
            throw new InvalidArgumentException('Capacity and rate must be greater than zero.');
        }

        $this->tokens = $capacity;
        $this->lastRefillTime = microtime(true);
    }

    public function consume(float $tokens = 1): bool
    {
        return $this->handleConsume($tokens);
    }

    public function remainingTokens(): int|float
    {
        return $this->tokens;
    }

    public function getCapacity(): int|float
    {
        return $this->capacity;
    }

    public function getRate(): int|float
    {
        return $this->rate;
    }

    protected function doConsume(float $tokens): bool
    {
        $this->refillTokens();

        if ($this->tokens >= $tokens) {
            $this->tokens -= $tokens;

            return true;
        }

        return false;
    }

    protected function refillTokens(): void
    {
        $now = microtime(true);
        $timePassed = $now - $this->lastRefillTime;
        $this->tokens = min($this->capacity, $this->tokens + $timePassed * $this->rate);
        $this->lastRefillTime = $now;
    }

    abstract protected function handleConsume(float $tokens): bool;
}
