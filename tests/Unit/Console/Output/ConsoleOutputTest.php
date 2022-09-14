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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;


class ConsoleOutputTest extends TestCase
{
    private function executeHandler(ConsoleOutput $output, int $signal, array $siginfo = null): void
    {
        Closure::bind(function (int $signal, array $siginfo = null) {
            $this->handleSignal($signal, $siginfo);
        }, $output, ConsoleOutput::class)(
            $signal,
            $siginfo
        );
    }

    private function executeHandlerRaw(ConsoleOutput $output, ...$signals): void
    {
        Closure::bind(function (int ...$signals) {
            foreach ($signals as $signal) {
                foreach ($this->signalHandlers[$signal] ?? [] as $signalHandler) {
                    $signalHandler($signal);
                }
            }
        }, $output, ConsoleOutput::class)(
            ...$signals,
        );
    }

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

    public function testTrap()
    {
        $var = null;
        $output = new ConsoleOutput();

        $output->trap(
            function () use (&$var) {
                $var = func_get_args();
            },
            SIGINT,
        );

        $this->executeHandler(
            $output, SIGINT,
            [
                "signo" => SIGINT,
                "errno" => 0,
                "code" => 0,
            ]
        );

        $this->assertEquals($var, [
            SIGINT,
            [
                "signo" => SIGINT,
                "errno" => 0,
                "code" => 0,
            ]
        ]);
    }

    public function testRetrap()
    {
        $actual = null;
        $output = new ConsoleOutput();

        $output->trap(
            function () use (&$actual, $output) {
                $actual = 1;
                $output->trap(
                    function () use (&$actual) {
                        $actual += 3;
                    },
                    SIGINT
                );
            },
            SIGINT,
        );

        $this->executeHandler(
            $output, SIGINT
        );

        $this->executeHandler(
            $output, SIGINT
        );

        $this->assertEquals(4, $actual);
    }

    public function testTrapMultipleSignals()
    {
        $var = 0;
        $output = new ConsoleOutput();
        $output->trap(
            function ($signal) use (&$var) {
                $var += $signal;
            },
            SIGINT,
            SIGTERM
        );

        $this->executeHandlerRaw($output, SIGINT, SIGTERM);

        $this->assertEquals(SIGINT + SIGTERM, $var);
    }

    public function testTrapMultipleHandlers()
    {
        $var = 0;
        $output = new ConsoleOutput();

        $this->mockExit($output, function () use (&$var) {
            $var += 4;
        });

        $output->trap(
            function () use (&$var) {
                $var += 1;
            },
            SIGINT
        );

        $output->trap(
            function () use (&$var) {
                $var += 2;
            },
            SIGINT
        );
        $this->executeHandler($output, SIGINT);

        $this->assertEquals(3, $var);
    }

    public function testTrapMultipleHandlersExit()
    {
        $var = 0;
        $output = new ConsoleOutput();
        $this->mockExit($output, function () use (&$var) {
            $var += 4;
        });

        $output->trap(
            function () use (&$var) {
                $var += 1;
                return true;
            },
            SIGINT
        );

        $output->trap(
            function () use (&$var) {
                $var += 2;
            },
            SIGINT
        );
        $this->executeHandler($output, SIGINT);

        $this->assertEquals(7, $var);
    }

    public function testTrapMultipleHandlersExitWithOneReturningFalse()
    {
        $var = 0;
        $output = new ConsoleOutput();
        $this->mockExit($output, function () use (&$var) {
            $var += 4;
        });

        $output->trap(
            function () use (&$var) {
                $var += 1;
                return true;
            },
            SIGINT
        );

        $output->trap(
            function () use (&$var) {
                $var += 2;
                return false;
            },
            SIGINT
        );
        $this->executeHandler($output, SIGINT);

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

    public function testShouldMergeOptions(): void
    {
        $output = new ConsoleOutput();
        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );
        $inputDefinition->addOption(
            new InputOption('anOption', '', InputOption::VALUE_REQUIRED, '', 'actual')
        );

        $inputDefinition->addOption(
            new InputOption('anOtherOption', '', InputOption::VALUE_REQUIRED, '', 'expected')
        );

        $output->mergeOptions([
            'anOption' => 'expected'
        ]);

        $this->assertEquals(
            [
                'anOption' => 'expected',
                'anOtherOption' => 'expected',
            ],
            $output->options()
        );

        $this->assertEquals('expected', $output->option('anOption'));
    }

    public function testShouldMergeArguments(): void
    {
        $output = new ConsoleOutput();
        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );

        $inputDefinition->addArgument(
            new InputArgument('anArgument', InputArgument::OPTIONAL, '', 'actual')
        );

        $inputDefinition->addArgument(
            new InputArgument('anOtherArgument', InputArgument::OPTIONAL, '', 'expected')
        );

        $output->mergeArguments([
            'anArgument' => 'expected'
        ]);

        $this->assertEquals(
            [
                'anArgument' => 'expected',
                'anOtherArgument' => 'expected',
            ],
            $output->arguments()
        );

        $this->assertEquals('expected', $output->argument('anArgument'));
    }
}
