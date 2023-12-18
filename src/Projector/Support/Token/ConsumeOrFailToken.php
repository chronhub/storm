<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Support\Token;

use Chronhub\Storm\Projector\Exceptions\NoMoreTockenBucketAvailable;

final class ConsumeOrFailToken extends AbstractTokenBucket
{
    protected function handleConsume(float $tokens = 1): bool
    {
        if ($this->doConsume($tokens)) {
            return true;
        }

        throw new NoMoreTockenBucketAvailable('No more token bucket available');
    }
}
