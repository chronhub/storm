<?php

declare(strict_types=1);

namespace Chronhub\Storm\Tests\Stubs;

use Stringable;
use Symfony\Component\Uid\Uuid;

final class V4UniqueIdStub implements Stringable
{
    public static function create(): Uuid
    {
        return Uuid::v4();
    }

    public function generate(): string
    {
        return self::create()->jsonSerialize();
    }

    public function __toString(): string
    {
        return $this->generate();
    }
}
