<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

use Stringable;

interface UniqueId extends Stringable
{
    /**
     * Generate a string unique id
     *
     * @return string
     */
    public function generate(): string;
}
