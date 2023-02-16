<?php

declare(strict_types=1);

namespace Chronhub\Storm\Message;

use InvalidArgumentException;
use Chronhub\Storm\Contracts\Message\MessageAlias;
use function ucwords;
use function basename;
use function ctype_lower;
use function str_replace;
use function class_exists;
use function preg_replace;
use function mb_strtolower;

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

        $eventClass = basename(str_replace('\\', '/', $eventClass));

        if (! ctype_lower($eventClass)) {
            $eventClass = preg_replace('/\s+/u', '', ucwords($eventClass));

            $eventClass = mb_strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1'.$delimiter, $eventClass));
        }

        return $eventClass;
    }
}
