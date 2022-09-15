<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use Closure;

use Orchestra\Testbench\TestCase;
use Henzeb\Console\Output\ConsoleOutput;

class InteractsWithSignalsTest extends TestCase
{
    private function mockExit(ConsoleOutput $output, callable $callback = null): void
    {
        Closure::bind(function () use ($callback) {
            $this->exitMethod = $callback ?? fn() => true;
        }, $output, ConsoleOutput::class)();
    }

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
            $output,
            SIGINT
        );

        $this->executeHandler(
            $output,
            SIGINT
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
}
