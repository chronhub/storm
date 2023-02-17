<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerManager
{
    public function create(string $name): Chronicler;

    /**
     * @return $this
     */
    public function extend(string $name, callable $callback): ChroniclerManager;

    /**
     * @return $this
     */
    public function shouldUse(string $driver, string|ChroniclerProvider $provider): ChroniclerManager;
}
