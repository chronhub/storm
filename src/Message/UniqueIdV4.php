<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\UniqueId;
use Symfony\Component\Uid\Uuid;

final class UniqueIdV4 implements UniqueId
{
    public function generate(): string
    {
        return Uuid::v4()->jsonSerialize();
    }

    public function __toString(): string
    {
        return $this->generate();
    }
}
