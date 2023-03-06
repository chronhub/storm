<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Chronicler;

use Psr\Container\ContainerInterface;

interface ChroniclerManager
{
    /**
     * @param  non-empty-string  $name
     */
    public function create(string $name): Chronicler;

    /**
     * @param  non-empty-string  $name
     * @param  callable(ContainerInterface, non-empty-string, array): Chronicler  $callback
     */
    public function extend(string $name, callable $callback): ChroniclerManager;

    /**
     * @param  non-empty-string  $driver
     */
    public function shouldUse(string $driver, string|ChroniclerProvider $provider): ChroniclerManager;
}
