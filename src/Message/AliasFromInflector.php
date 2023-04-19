<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use Chronhub\Storm\Contracts\Message\MessageAlias;
use InvalidArgumentException;
use function basename;
use function class_exists;
use function ctype_lower;
use function mb_strtolower;
use function preg_replace;
use function str_replace;
use function ucwords;

final class AliasFromInflector implements MessageAlias
{
    public function classToAlias(string $eventClass): string
    {
        if (! class_exists($eventClass)) {
            throw new InvalidArgumentException("Event class $eventClass does not exists");
        }

        return $this->produceAlias($eventClass);
    }

    public function instanceToAlias(object $event): string
    {
        return $this->produceAlias($event::class);
    }

    private function produceAlias(string $eventClass): string
    {
        $delimiter = '-';

        $eventAlias = basename(str_replace('\\', '/', $eventClass));

        if (! ctype_lower($eventClass)) {
            $eventAlias = preg_replace('/\s+/u', '', ucwords($eventAlias));

            $eventAlias = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $eventAlias));
        }

        return $eventAlias;
    }
}
