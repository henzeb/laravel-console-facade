<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;


use Mockery;
use Closure;
use Orchestra\Testbench\TestCase;
use Henzeb\Console\Facades\Console;
use Illuminate\Console\OutputStyle;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Console\View\Components\Factory;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;


class ConsoleOutputTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
    }

    public function testShouldSetInput(): void
    {
        $this->assertFalse((new ConsoleOutput())->hasOption('test'));
    }

    public function testShouldSetInputWhenNewOutputIsSet(): void
    {
        $arrayInput = new ArrayInput([]);
        $console = new ConsoleOutput();
        $console->setOutput(new OutputStyle($arrayInput, new SymfonyConsoleOutput()));
        $this->assertFalse((new ConsoleOutput())->hasOption('test'));

        $this->assertTrue($arrayInput === $console->getInput());
    }

    public function testShouldAutomaticallySetOutputStyle()
    {
        Console::partialMock()->expects('setOutput');

        $this->artisan('env');
    }

    public function testShouldHaveDefaultOutput()
    {
        $this->assertInstanceOf(
            OutputStyle::class,
            (new ConsoleOutput())->getOutput()
        );
    }

    public function testShouldReturnSection(): void
    {
        $this->assertInstanceOf(ConsoleSectionOutput::class, (new ConsoleOutput())->section('mySection'));
    }

    public function testShouldReturnSameSection(): void
    {
        $output = (new ConsoleOutput());
        $expected = $output->section('mySection');
        $actual = $output->section('mySection');
        $this->assertTrue($expected === $actual);
    }

    public function testShouldReturnDifferentSection(): void
    {
        $output = (new ConsoleOutput());
        $expected = $output->section('mySection');
        $actual = $output->section('myOtherSection');
        $this->assertTrue($expected !== $actual);
    }

    public function testShouldRenderSection(): void
    {
        $output = new ConsoleOutput();
        $section = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $expectedCallable = function (ConsoleSectionOutput $section) {
            $section->write('test');
        };
        $section->expects('render')->with($expectedCallable);

        Closure::bind(function () use ($section) {
            $this->sections = ['mySection' => $section];
        }, $output, ConsoleOutput::class)();
        $output->section('mySection', $expectedCallable);
    }

    private function mockExit(ConsoleOutput $output, callable $callback = null): void
    {
        Closure::bind(function () use ($callback) {
            $this->exitMethod = $callback ?? fn() => true;
        }, $output, ConsoleOutput::class)();
    }

    public function testShouldExit(): void
    {
        $output = new ConsoleOutput();

        $this->mockExit($output, function (int $exitcode) {
            $this->assertEquals(0, $exitcode);
        });

        $output->exit();
    }

    public function testShouldCallOnExit()
    {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        });

        $output->exit();

        $this->assertTrue($actual);
    }

    public function testShouldCallOnExitWithDifferentStatus()
    {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        });

        $output->exit(1);

        $this->assertTrue($actual);
    }

    public function testShouldCallTwiceOnExit()
    {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = 0;

        $output->onExit(function () use (&$actual) {
            $actual++;
        });

        $output->onExit(function () use (&$actual) {
            $actual++;
        });

        $output->exit();

        $this->assertEquals(2, $actual);
    }

    public function testShouldCallTwiceOnExitWithDifferentStatusCode()
    {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = 0;

        $output->onExit(function () use (&$actual) {
            $actual++;
        });

        $output->onExit(function () use (&$actual) {
            $actual++;
        }, 1);

        $output->exit(1);

        $this->assertEquals(2, $actual);
    }

    public function testShouldNotCallWhenStatusCodeDoesNotMatch()
    {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        }, 0);

        $output->exit(1);

        $this->assertFalse($actual);
    }

    public function testShouldCallWhenStatusCodeDoesMatch()
    {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        }, 1);

        $output->exit(1);

        $this->assertTrue($actual);
    }

    public function testOnSignal()
    {
        $var = null;
        $output = new ConsoleOutput();

        $output->onSignal(
            function () use (&$var) {
                $var = func_get_args();
            },
            SIGINT
        );
        pcntl_signal_get_handler(SIGINT)(
            0,
            [
                "signo" => 2,
                "errno" => 0,
                "code" => 0,
            ]
        );

        $this->assertEquals($var, [
            0,
            [
                "signo" => 2,
                "errno" => 0,
                "code" => 0,
            ]
        ]);
    }

    public function testOnSignalMultipleSignals()
    {
        $var = 0;
        $output = new ConsoleOutput();
        $output->onSignal(
            function () use (&$var) {
                $var += 1;
            },
            SIGINT,
            SIGTERM
        );
        pcntl_signal_get_handler(SIGINT)();
        pcntl_signal_get_handler(SIGTERM)();

        $this->assertEquals(2, $var);
    }

    public function testOnSignalMultipleHandlers()
    {
        $var = 0;
        $output = new ConsoleOutput();

        $this->mockExit($output, function () use (&$var) {
            $var += 4;
        });

        $output->onSignal(
            function () use (&$var) {
                $var += 1;
            },
            SIGINT
        );

        $output->onSignal(
            function () use (&$var) {
                $var += 2;
            },
            SIGINT
        );
        pcntl_signal_get_handler(SIGINT)();

        $this->assertEquals(3, $var);
    }

    public function testOnSignalMultipleHandlersExit()
    {
        $var = 0;
        $output = new ConsoleOutput();
        $this->mockExit($output, function () use (&$var) {
            $var += 4;
        });

        $output->onSignal(
            function () use (&$var) {
                $var += 1;
                return true;
            },
            SIGINT
        );

        $output->onSignal(
            function () use (&$var) {
                $var += 2;
            },
            SIGINT
        );
        pcntl_signal_get_handler(SIGINT)();

        $this->assertEquals(7, $var);
    }

    public function testOnSignalMultipleHandlersExitWithOneReturningFalse()
    {
        $var = 0;
        $output = new ConsoleOutput();
        $this->mockExit($output, function () use (&$var) {
            $var += 4;
        });

        $output->onSignal(
            function () use (&$var) {
                $var += 1;
                return true;
            },
            SIGINT
        );

        $output->onSignal(
            function () use (&$var) {
                $var += 2;
                return false;
            },
            SIGINT
        );
        pcntl_signal_get_handler(SIGINT)();

        $this->assertEquals(7, $var);
    }

    public function testShouldReturnComponentsFactory(): void
    {
        $output = new ConsoleOutput();

        $expectedOutput = $output->getOutput();

        app()->bind(Factory::class, function ($app, $args) {
            return new class($args['output']) extends Factory {

            };
        });
        $resolved = resolve(Factory::class, ['output' => $expectedOutput]);

        $this->assertEquals(get_class($resolved), get_class($output->components()));

        $actualOutput = Closure::bind(function () {
            return $this->output;
        }, $resolved, Factory::class)();

        $this->assertTrue($expectedOutput === $actualOutput);
    }
}
