<?php

namespace Henzeb\Console\Functions;

use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\ConsoleOutput;

function console(string $message = null): ConsoleOutput
{
    return tap(
        Console::getFacadeRoot(),
        function (ConsoleOutput $consoleOutput) use ($message) {
            if ($message) {
                $consoleOutput->info($message);
            }
        }
    );
}

;

