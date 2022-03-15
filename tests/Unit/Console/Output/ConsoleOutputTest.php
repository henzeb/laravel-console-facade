<?php

namespace Henzeb\Jukebox\Tests\Unit\Console\Output;


use Orchestra\Testbench\TestCase;
use Henzeb\Console\Facades\Console;
use Illuminate\Console\OutputStyle;
use Henzeb\Console\Output\ConsoleOutput;

use Henzeb\Console\Providers\ConsoleServiceProvider;


class ConsoleOutputTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
    }

    public function testShouldAutomaticallySetOutputStyle() {

        Console::partialMock()->expects('setOutput');

        $this->artisan('env');
    }

    public function testShouldHaveDefaultOutput()
    {
        $this->assertInstanceOf(
            OutputStyle::class, (new ConsoleOutput())->getOutput()
        );
    }
}
