<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface QuerySubscriber
{
    public function resets(): void;
}
