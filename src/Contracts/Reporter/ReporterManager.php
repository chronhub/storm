<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

interface ReporterManager
{
    public function create(string $type, string $name): Reporter;

    public function command(string $name = 'default'): Reporter;

    public function event(string $name = 'default'): Reporter;

    public function query(string $name = 'default'): Reporter;
}
