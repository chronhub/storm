<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface Observer
{
    public function update(Subscriptor $subscriptor): void;
}
