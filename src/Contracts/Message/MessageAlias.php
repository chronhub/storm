<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

interface MessageAlias
{
    /**
     * @param  class-string  $eventClass
     */
    public function classToAlias(string $eventClass): string;

    public function instanceToAlias(object $event): string;
}
