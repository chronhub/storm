<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Scheme;

final class ConsumeToken extends AbstractTokenBucket
{
    protected function handleConsume(float $tokens = 1): bool
    {
        return $this->doConsume($tokens);
    }
}
