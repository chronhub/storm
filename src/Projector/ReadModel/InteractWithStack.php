<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\ReadModel;

trait InteractWithStack
{
    /**
     * @var array<array<string,mixed>>
     */
    protected array $stack = [];

    public function stack(string $operation, mixed ...$arguments): void
    {
        $this->stack[] = [$operation, $arguments];
    }

    public function persist(): void
    {
        foreach ($this->stack as [$operation, $args]) {
            $this->{$operation}(...$args);
        }

        $this->stack = [];
    }
}
