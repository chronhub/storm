<?php

declare(strict_types=1);

namespace Chronhub\Storm\Routing;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use Chronhub\Storm\Contracts\Routing\Registrar;
use Chronhub\Storm\Reporter\DomainType;
use Chronhub\Storm\Routing\Exceptions\RoutingViolation;
use Illuminate\Support\Collection;
use function array_merge;

final class GroupRegistrar implements Registrar
{
    private Collection $groups;

    public function __construct(private readonly MessageAlias $messageAlias)
    {
        $this->groups = new Collection();
    }

    public function make(DomainType $groupType, string $groupName): Group
    {
        $group = $this->newGroup($groupType, $groupName);

        if (! $this->groups->has($groupType->value)) {
            $this->groups->put($groupType->value, [$groupName => $group]);

            return $group;
        }

        return $this->mergeGroup($group);
    }

    public function makeCommand(string $groupName): Group
    {
        return $this->make(DomainType::COMMAND, $groupName);
    }

    public function makeEvent(string $groupName): Group
    {
        return $this->make(DomainType::EVENT, $groupName);
    }

    public function makeQuery(string $groupName): Group
    {
        return $this->make(DomainType::QUERY, $groupName);
    }

    public function get(DomainType $groupType, string $groupName): ?Group
    {
        return $this->groups[$groupType->value][$groupName] ?? null;
    }

    public function all(): Collection
    {
        return clone $this->groups;
    }

    private function newGroup(DomainType $groupType, string $name): Group
    {
        $routing = new CollectRoutes($this->messageAlias);

        return match ($groupType) {
            DomainType::COMMAND => new CommandGroup($name, $routing),
            DomainType::EVENT => new EventGroup($name, $routing),
            DomainType::QUERY => new QueryGroup($name, $routing)
        };
    }

    private function mergeGroup(Group $group): Group
    {
        $type = $group->getType()->value;
        $name = $group->name;

        if (isset($this->groups[$type][$name])) {
            throw new RoutingViolation(
                "Group $type already exists with name $name"
            );
        }

        $this->groups->put($type, array_merge($this->groups[$type], [$name => $group]));

        return $group;
    }
}
