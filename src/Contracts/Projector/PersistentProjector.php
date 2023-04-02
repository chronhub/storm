<?php

declare(strict_types=1);

namespace Chronhub\Storm\Contracts\Projector;

interface PersistentProjector extends ProjectorFactory
{
    public function delete(bool $withEmittedEvents): void;

    public function getStreamName(): string;
}
