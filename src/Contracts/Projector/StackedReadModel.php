<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface StackedReadModel
{
    /**
     * Stacks an operation to be applied to the read model.
     *
     * @param string $operation    The operation to apply.
     * @param mixed  ...$arguments The arguments for the operation.
     */
    public function stack(string $operation, mixed ...$arguments): void;
}
