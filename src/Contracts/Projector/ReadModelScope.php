<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ReadModelScope extends ProjectorScope
{
    /**
     * Return the read model instance.
     *
     * todo do we need to access the full read model inside reactors?
     */
    public function readModel(): StackedReadModel;

    /**
     * Shortcut to read model stack.
     */
    public function stack(string $operation, mixed ...$arguments): void;
}
