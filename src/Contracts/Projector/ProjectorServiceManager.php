<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectorServiceManager
{
    public function create(string $name): ProjectorManagerInterface;

    public function extend(string $name, callable $callback): ProjectorServiceManager;

    public function setDefaultDriver(string $driver): ProjectorServiceManager;

    public function getDefaultDriver(): string;
}
