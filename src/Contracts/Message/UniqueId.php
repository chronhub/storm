<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

use Stringable;

interface UniqueId extends Stringable
{
    /**
     * Generate a string unique id
     */
    public function generate(): string;
}
