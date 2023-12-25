<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface ActivityFactory
{
    public function __invoke(Subscriptor $subscriptor, ProjectorScope $scope, Management $management): array;
}
