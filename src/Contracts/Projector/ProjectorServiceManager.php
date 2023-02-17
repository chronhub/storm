<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

use Chronhub\Storm\Projector\ProjectorManagerFactory;

interface ProjectorServiceManager
{
    public function create(string $name): ProjectorManager;

    public function extend(string $name, callable $callback): ProjectorServiceManager;

    public function shouldUse(string $driver, string|ProjectorManagerFactory $factory): ProjectorServiceManager;
}
