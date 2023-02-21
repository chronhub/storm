<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Chronhub\Storm\Routing\Group;
use Illuminate\Support\Collection;
use Chronhub\Storm\Reporter\DomainType;

interface Registrar
{
    public function make(DomainType $domainType, string $name): Group;

    public function get(DomainType $type, string $name): ?Group;

    /**
     * @return Collection<Group>
     */
    public function all(): Collection;
}
