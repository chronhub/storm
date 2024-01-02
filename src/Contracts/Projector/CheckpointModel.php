<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface CheckpointModel
{
    public function id(): string;

    public function projectionName(): string;

    public function streamName(): string;

    public function position(): int;

    public function createdAt(): string;

    public function gaps(): string;
}
