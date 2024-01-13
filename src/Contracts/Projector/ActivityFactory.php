<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ActivityFactory
{
    /**
     * @return array<callable>
     */
    public function __invoke(Subscriptor $subscriptor, ProjectorScope $projectorScope): array;
}
