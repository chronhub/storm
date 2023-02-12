<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Reporter;

interface ReporterManager
{
    /**
     * Create new instance of reporter by domain type and name
     *
     * @param  string  $type
     * @param  string  $name
     * @return Reporter
     */
    public function create(string $type, string $name): Reporter;

    /**
     * Create new instance of command reporter by name
     *
     * @param  string  $name
     * @return Reporter
     */
    public function command(string $name = 'default'): Reporter;

    /**
     * Create new instance of event reporter by name
     *
     * @param  string  $name
     * @return Reporter
     */
    public function event(string $name = 'default'): Reporter;

    /**
     * Create new instance of query reporter by name
     *
     * @param  string  $name
     * @return Reporter
     */
    public function query(string $name = 'default'): Reporter;
}
