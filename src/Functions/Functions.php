<?php

namespace Henzeb\Console\Functions;

use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\ConsoleOutput;
use RuntimeException;

function console(?string $message = null): ConsoleOutput
{
    $consoleOutput = Console::getFacadeRoot();

    if (!$consoleOutput instanceof ConsoleOutput) {
        throw new RuntimeException('The Console facade does not resolve to ' . ConsoleOutput::class);
    }

    if ($message) {
        $consoleOutput->info($message);
    }

    return $consoleOutput;
}
