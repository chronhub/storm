<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector;

use Chronhub\Storm\Contracts\Projector\ProjectionModel;

final class InMemoryProjection implements ProjectionModel
{
    private string $positions = '{}';

    private string $gaps = '{}';

    private string $state = '{}';

    private ?string $lockedUntil = null;

    private function __construct(
        private readonly string $name,
        private string $status
    ) {
    }

    public static function create(string $name, string $status): self
    {
        return new self($name, $status);
    }

    public function setPosition(string $positions): void
    {
        $this->positions = $positions;
    }

    public function setGaps(string $gaps): void
    {
        $this->gaps = $gaps;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setLockedUntil(?string $lockedUntil): void
    {
        $this->lockedUntil = $lockedUntil;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function positions(): string
    {
        return $this->positions;
    }

    public function gaps(): string
    {
        return $this->gaps;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function lockedUntil(): ?string
    {
        return $this->lockedUntil;
    }
}
