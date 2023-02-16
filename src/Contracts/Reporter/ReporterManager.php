<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

interface ReporterManager
{
    /**
     * Create new instance of reporter by domain type and name
     */
    public function create(string $type, string $name): Reporter;

    /**
     * Create new instance of command reporter by name
     */
    public function command(string $name = 'default'): Reporter;

    /**
     * Create new instance of event reporter by name
     */
    public function event(string $name = 'default'): Reporter;

    /**
     * Create new instance of query reporter by name
     */
    public function query(string $name = 'default'): Reporter;
}
