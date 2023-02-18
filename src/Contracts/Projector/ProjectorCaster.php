<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectorCaster
{
    public function stop(): void;

    /**
     * @return string|null only null on setup but available at the first event
     */
    public function streamName(): ?string;
}
