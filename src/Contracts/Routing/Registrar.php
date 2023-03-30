<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Routing;

use Chronhub\Storm\Routing\Group;
use Illuminate\Support\Collection;
use Chronhub\Storm\Reporter\DomainType;

interface Registrar
{
    public function make(DomainType $groupType, string $groupName): Group;

    public function makeCommand(string $groupName): Group;

    public function makeEvent(string $groupName): Group;

    public function makeQuery(string $groupName): Group;

    public function get(DomainType $groupType, string $groupName): ?Group;

    /**
     * @return Collection<Group>
     */
    public function all(): Collection;
}
