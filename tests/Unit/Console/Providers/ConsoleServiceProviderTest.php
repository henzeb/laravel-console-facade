<?php

namespace Henzeb\Console\Tests\Unit\Console\Providers;

use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;

class ConsoleServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
    }

    public function testShouldIsolateConsoleWhenUsingArtisanCall()
    {
        $unit = $this;
        if (method_exists($this->app[Kernel::class], 'rerouteSymfonyCommandEvents')) {
            $this->app[Kernel::class]->rerouteSymfonyCommandEvents();
        }

        Artisan::command(
            'myApplication',
            function () use ($unit) {
                $unit->assertEquals('myApplication', Console::getInput()->getFirstArgument());

                Artisan::call('myOtherApplication');

                $unit->assertEquals('myApplication', Console::getInput()->getFirstArgument());
            }
        );

        Artisan::command(
            'myOtherApplication',
            function () use ($unit) {
                $unit->assertEquals('myOtherApplication', Console::getInput()->getFirstArgument());
            }
        );

        Artisan::call('myApplication');
    }

    public function providesFormatterStyleTestCases(): array
    {
        return [
            ['henzeb.console.verbose', "\e[36mtest\e[39m"],
            ['henzeb.console.very.verbose', "\e[33mtest\e[39m"],
            ['henzeb.console.debug', "\e[35mtest\e[39m"]
        ];
    }

    /**
     * @return void
     * @dataProvider providesFormatterStyleTestCases
     */
    public function testShouldHaveStyleRegistered(
        string $tag,
        string $expected
    ): void
    {
        $output = new ConsoleOutput();
        $output->getOutput()->setDecorated(true);
        $formatter = $output->getOutput()->getFormatter();
        $this->assertEquals($expected, $formatter->format(sprintf('<%s>test</>', $tag)));
    }
}
