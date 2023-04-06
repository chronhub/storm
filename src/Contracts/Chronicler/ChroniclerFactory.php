<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerFactory
{
    /**
     * @param non-empty-string $name
     */
    public function createEventStore(string $name, array $config): Chronicler;
}
