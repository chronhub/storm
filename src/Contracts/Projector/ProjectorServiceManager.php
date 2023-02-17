<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectorServiceManager
{
    public function create(string $name): ProjectorManager;

    public function extend(string $name, callable $callback): ProjectorServiceManager;

    public function shouldUse(string $driver, string|callable $factory): ProjectorServiceManager;
}
