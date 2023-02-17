<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerManager
{
    public function create(string $name): Chronicler;

    public function extend(string $name, callable $callback): ChroniclerManager;

    public function shouldUse(string $driver, string|ChroniclerProvider $provider): ChroniclerManager;
}
