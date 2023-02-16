<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectorServiceManager
{
    /**
     * @param  string  $name
     * @return ProjectorManager
     */
    public function create(string $name): ProjectorManager;

    /**
     * @param  string  $name
     * @param  callable  $callback
     * @return ProjectorServiceManager
     */
    public function extend(string $name, callable $callback): ProjectorServiceManager;

    /**
     * @param  string  $driver
     * @param  string|callable  $factory
     * @return ProjectorServiceManager
     */
    public function shouldUse(string $driver, string|callable $factory): ProjectorServiceManager;
}
