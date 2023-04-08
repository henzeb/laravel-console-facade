<?php

namespace Henzeb\Console\Tests\Unit\Console\Functions;

use Henzeb\Console\Facades\Console;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Console\OutputStyle;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use function Henzeb\Console\Functions\console;

class FunctionsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
    }

    public function testConsole()
    {
        $this->assertSame(
            Console::getFacadeRoot(),
            console()
        );
    }

    public function testConsoleWritesLine()
    {
        $output = new BufferedOutput();
        Console::setOutput(new OutputStyle(Console::getInput(), $output));

        $this->assertSame(
            Console::getFacadeRoot(),
            console('test')
        );

        $this->assertEquals('test' . PHP_EOL, $output->fetch());
    }


}
