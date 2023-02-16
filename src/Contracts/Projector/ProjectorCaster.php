<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ProjectorCaster
{
    /**
     * Stop projection
     */
    public function stop(): void;

    /**
     * Get the current processed stream
     *
     * @return string|null only null on setup but available at the first event
     */
    public function streamName(): ?string;
}
