<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns\Stub;

use Illuminate\Console\Command;
use Henzeb\Console\Facades\Console;

class TestValidationCommand extends Command
{
    protected $signature = 'test:test {--test=}';

    protected $description = 'Command description';

    protected function configure()
    {
        Console::validateWith([
            '--test'=>'size:2'
        ]);
    }

    public function handle()
    {

    }
}
