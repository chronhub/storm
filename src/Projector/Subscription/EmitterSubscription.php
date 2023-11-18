<?php

declare(strict_types=1);

namespace Chronhub\Storm\Projector\Subscription;

use Chronhub\Storm\Chronicler\Exceptions\StreamNotFound;
use Chronhub\Storm\Contracts\Chronicler\Chronicler;
use Chronhub\Storm\Contracts\Projector\EmitterSubscriptionInterface;
use Chronhub\Storm\Stream\StreamName;

final class EmitterSubscription extends AbstractPersistentSubscription implements EmitterSubscriptionInterface
{
    private Chronicler $chronicler;

    private bool $streamFixed = false;

    public function revise(): void
    {
        parent::revise();

        $this->deleteStream();
    }

    public function discard(bool $withEmittedEvents): void
    {
        $this->repository->delete();

        if ($withEmittedEvents) {
            $this->deleteStream();
        }

        $this->sprint()->stop();

        $this->resetProjection();
    }

    public function isFixed(): bool
    {
        return $this->streamFixed;
    }

    public function fixe(): void
    {
        $this->streamFixed = true;
    }

    public function unfix(): void
    {
        $this->streamFixed = false;
    }

    public function setChronicler(Chronicler $chronicler): void
    {
        $this->chronicler = $chronicler;
    }

    private function deleteStream(): void
    {
        try {
            $this->chronicler->delete(new StreamName($this->projectionName()));
        } catch (StreamNotFound) {
            // fail silently
        }

        $this->unfix();
    }
}
