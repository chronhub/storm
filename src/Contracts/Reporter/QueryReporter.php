<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

use React\Promise\PromiseInterface;

interface QueryReporter extends Reporter
{
    public function relay(object|array $message): PromiseInterface;
}
