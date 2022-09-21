<?php

namespace Henzeb\Console\Concerns;

use Illuminate\Console\Command;

trait InteractsWithCommand
{
    private ?Command $command = null;

    public function setCommand(Command $command): void
    {
        $this->command = $command;
    }

    private function getCommand(): Command
    {
        return $this->command ?? (new Command())->setName('default');
    }
}
