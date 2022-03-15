<?php

namespace Henzeb\Console\Output;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

class ConsoleOutput
{
    use InteractsWithIO;

    public function __construct()
    {
        $this->setOutput(
            new OutputStyle(
                new ArrayInput([]),
                new SymfonyConsoleOutput()
            )
        );
    }
}
