<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Chronhub\Storm\Tracker\InteractWithStory;

final class StoryStub
{
    use InteractWithStory;

    public function getCurrentEvent(): ?string
    {
        return $this->currentEvent;
    }
}
