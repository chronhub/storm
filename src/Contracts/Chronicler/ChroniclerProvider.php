<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerProvider
{
    /**
     * @param  non-empty-string  $name
     */
    public function resolve(string $name, array $config): Chronicler;
}
