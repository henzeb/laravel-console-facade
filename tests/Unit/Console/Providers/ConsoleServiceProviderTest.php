<?php

namespace Henzeb\Console\Tests\Unit\Console\Providers;

use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Output\NullOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

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

    public function providesOutputStyleResolveCases(): array
    {
        return [
            [
                false, false, ArrayInput::class, NullOutput::class
            ],
            [
                false, true, ArrayInput::class, NullOutput::class
            ],
            [
                true, true, ArrayInput::class, SymfonyConsoleOutput::class
            ],
            [
                true, false, ArgvInput::class, SymfonyConsoleOutput::class
            ]
        ];
    }

    /**
     * @param bool $runningInConsole
     * @param bool $runningUnitTests
     * @param string $expectedInput
     * @param string $expectedOutput
     * @return void
     * @dataProvider providesOutputStyleResolveCases
     */
    public function testResolveOutputStyle(
        bool   $runningInConsole,
        bool   $runningUnitTests,
        string $expectedInput,
        string $expectedOutput
    ): void
    {
        App::shouldReceive('runningInConsole')->andReturn($runningInConsole);
        App::shouldReceive('runningUnitTests')->andReturn($runningUnitTests);

        $consoleOutput = new ConsoleOutput();

        $this->assertInstanceOf($expectedInput, $consoleOutput->getInput());
        $this->assertInstanceOf($expectedOutput, $consoleOutput->getOutput()->getOutput());
    }
}
