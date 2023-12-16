<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

/**
 * Capacity (capacity):
 *
 * This represents the maximum number of tokens the bucket can hold.
 * It is a fixed quantity that doesn't change over time.
 * When the bucket is full, it cannot accumulate more tokens.
 *
 * Rate (rate):
 *
 * This represents the rate at which tokens are added to the bucket per unit of time.
 * It is specified as tokens per second (e.g., 10 tokens/second).
 * The rate determines how quickly the bucket refills with tokens.
 *
 * Interaction:
 *
 * If capacity is an integer, it means the bucket can hold a specific whole number of tokens.
 * If rate is an integer, it represents the number of tokens added to the bucket every second.
 * For example, if capacity is 5 and rate is 2, it means the bucket can hold up to 5 tokens, and it refills at a rate of 2 tokens per second.
 *
 * Floating-Point Values:
 *
 * If capacity or rate is a floating-point number, it allows for more precision in representing non-whole numbers.
 * For example, a rate of 0.5 tokens per second means the bucket refills at half a token every second.
 *
 * Example:
 *
 * Suppose capacity is 10 and the rate is 2.5 tokens per second.
 * The bucket starts with 10 tokens. Every second, it adds 2.5 tokens.
 * After one second, the bucket will have 12.5 tokens. However, since capacity is 10, it can only hold up to 10 tokens. The excess tokens are ignored.
 * In summary, capacity sets the maximum limit for the number of tokens the bucket can hold, while rate defines how, quickly, the bucket refills with tokens.
 *
 * Note: when using withSleep, requested token cannot exceed the capacity of the bucket to avoid infinite loop
 * and the bucket is overflowed immediately.
 */
interface TokenBucket
{
    public function consume(float $tokens = 1): bool;

    public function getCapacity(): int|float;

    public function getRate(): int|float;
}
