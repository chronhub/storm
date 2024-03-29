<?php

declare(strict_types=1);

namespace Chronhub\Storm\Support;

trait HasEnumStrings
{
    /**
     * @return array<int, string>
     */
    public static function strings(): array
    {
        $strings = [];

        foreach (self::cases() as $case) {
            $strings[] = $case->value;
        }

        return $strings;
    }
}
