<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Workflow\Watcher;

use Chronhub\Storm\Contracts\Projector\UserState;

final class UserStateWatcher implements UserState
{
    private array $state = [];

    public function put(array $state): void
    {
        $this->state = $state;
    }

    public function get(): array
    {
        return $this->state;
    }

    public function reset(): void
    {
        $this->state = [];
    }
}