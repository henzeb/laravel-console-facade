<?php

namespace Henzeb\Console\Facades;

use Illuminate\Support\Facades\Facade;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Concerns\InteractsWithSignals;

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
