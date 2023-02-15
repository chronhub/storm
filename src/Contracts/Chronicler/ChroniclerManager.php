<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

interface ChroniclerManager
{
    /**
     * @param  string  $name
     * @return Chronicler
     */
    public function create(string $name): Chronicler;

    /**
     * @param  string  $name
     * @param  callable  $callback
     * @return $this
     */
    public function extend(string $name, callable $callback): ChroniclerManager;

    /**
     * @param  string  $driver
     * @param  string|ChroniclerProvider  $provider
     * @return $this
     */
    public function shouldUse(string $driver, string|ChroniclerProvider $provider): ChroniclerManager;
}
