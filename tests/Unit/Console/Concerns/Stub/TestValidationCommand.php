<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns\Stub;

use Henzeb\Console\Facades\Console;
use Illuminate\Console\Command;

class TestValidationCommand extends Command
{
    protected $signature = 'test:test {--test=}';

    protected $description = 'Command description';

    protected function configure()
    {
        Console::validateWith([
            '--test' => 'size:3'
        ]);
    }

    public function handle()
    {

    }
}
