<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Illuminate\Support\Collection;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Contracts\Routing\Registrar;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use function array_merge;

final class RoutingRegistrar implements Registrar
{
    private Collection $groups;

    public function __construct(private readonly MessageAlias $messageAlias)
    {
        $this->groups = new Collection();
    }

    public function make(DomainType $domainType, string $name): Group
    {
        $group = $this->newGroupInstance($domainType, $name);

        if (! $this->groups->has($domainType->value)) {
            $this->groups->put($domainType->value, [$name => $group]);

            return $group;
        }

        return $this->mergeWithGroup($group);
    }

    public function makeCommand(string $name): Group
    {
        return $this->make(DomainType::COMMAND, $name);
    }

    public function makeEvent(string $name): Group
    {
        return $this->make(DomainType::EVENT, $name);
    }

    public function makeQuery(string $name): Group
    {
        return $this->make(DomainType::QUERY, $name);
    }

    public function get(DomainType $type, string $name): ?Group
    {
        return $this->groups[$type->value][$name] ?? null;
    }

    public function all(): Collection
    {
        return clone $this->groups;
    }

    private function mergeWithGroup(Group $group): Group
    {
        $domainType = $group->getType();
        $name = $group->name;

        $this->assertUniqueDomainTypeAndName($domainType, $name);

        $this->groups->put($domainType->value, array_merge($this->groups[$domainType->value], [$name => $group]));

        return $group;
    }

    private function newGroupInstance(DomainType $domainType, string $name): Group
    {
        $routing = new CollectRoutes($this->messageAlias);

        return match ($domainType) {
            DomainType::COMMAND => new CommandGroup($name, $routing),
            DomainType::EVENT => new EventGroup($name, $routing),
            DomainType::QUERY => new QueryGroup($name, $routing)
        };
    }

    private function assertUniqueDomainTypeAndName(DomainType $domainType, string $name): void
    {
        if (isset($this->groups[$domainType->value][$name])) {
            throw new RoutingViolation("$domainType->value domain already exists with name $name");
        }
    }
}
