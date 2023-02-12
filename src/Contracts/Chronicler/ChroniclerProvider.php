<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerProvider
{
    /**
     * @param  string  $name
     * @param  array  $config
     * @return Chronicler
     */
    public function resolve(string $name, array $config): Chronicler;
}
