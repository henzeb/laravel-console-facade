<?php

namespace Henzeb\Console\Stores;

use Illuminate\Console\OutputStyle;

abstract class OutputStore
{
    private static array $outputs = [];

    public static function add(OutputStyle $output): void
    {
        self::$outputs[] = $output;
    }

    public static function pop(): ?OutputStyle
    {
        return array_pop(self::$outputs);
    }

    public static function hasOutputs(): bool
    {
        return !empty(self::$outputs);
    }
}
