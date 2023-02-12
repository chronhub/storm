<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Message;

interface MessageAlias
{
    /**
     * @param  class-string  $eventClass
     * @return string
     */
    public function classToAlias(string $eventClass): string;

    /**
     * @param  object  $event
     * @return string
     */
    public function instanceToAlias(object $event): string;
}
