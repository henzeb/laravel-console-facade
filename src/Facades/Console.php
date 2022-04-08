<?php

namespace Henzeb\Console\Facades;

use Illuminate\Support\Facades\Facade;
use Henzeb\Console\Output\ConsoleOutput;

/**
 * @mixin ConsoleOutput
 */
class Console extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ConsoleOutput::class;
    }
}
