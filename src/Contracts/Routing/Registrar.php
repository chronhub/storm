<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Chronhub\Storm\Routing\Group;
use Illuminate\Support\Collection;
use Chronhub\Storm\Reporter\DomainType;

interface Registrar
{
    /**
     * Return new group instance
     *
     * @param  DomainType  $domainType
     * @param  string  $name
     * @return Group
     */
    public function make(DomainType $domainType, string $name): Group;

    /**
     * Return group instance by domain type and name if exists
     *
     * @param  DomainType  $type
     * @param  string  $name
     * @return Group|null
     */
    public function get(DomainType $type, string $name): ?Group;

    /**
     * Return group collection
     *
     * @return Collection
     */
    public function all(): Collection;
}
