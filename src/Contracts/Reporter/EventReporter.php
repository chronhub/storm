<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

interface EventReporter extends Reporter
{
    public function relay(object|array $message): void;
}
