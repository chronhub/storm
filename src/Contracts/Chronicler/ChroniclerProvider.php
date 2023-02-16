<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerProvider
{
    public function resolve(string $name, array $config): Chronicler;
}
