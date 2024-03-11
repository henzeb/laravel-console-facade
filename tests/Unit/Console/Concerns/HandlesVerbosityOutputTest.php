<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;


use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Henzeb\Console\Tests\Unit\Console\Concerns\Stub\StubCommandServiceProvider;
use Illuminate\Console\OutputStyle;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class HandlesVerbosityOutputTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class, StubCommandServiceProvider::class];
    }

    public static function providesVerbosityCases(): array
    {
        return [
            'verbose' => ['method' => 'verbose', 'verbosity' => ConsoleOutputInterface::VERBOSITY_VERBOSE],
            'veryVerbose' => ['method' => 'veryVerbose', 'verbosity' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE],
            'debug' => ['method' => 'debug', 'verbosity' => ConsoleOutputInterface::VERBOSITY_DEBUG],
        ];
    }


    public static function providesVerbosityOutputTestcases()
    {
        return [
            'debug-debug' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'command' => 'debug',
                'expected' => '<henzeb.console.debug>debug</henzeb.console.debug>' . PHP_EOL
            ],
            'debug-very-verbose' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'command' => 'debug',
                'expected' => ''
            ],
            'debug-verbose' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'command' => 'debug',
                'expected' => ''
            ],
            'debug-normal' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'command' => 'debug',
                'expected' => ''
            ],
            'debug-quiet' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_QUIET,
                'command' => 'debug',
                'expected' => ''
            ],

            'very-verbose-debug' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'command' => 'veryVerbose',
                'expected' => '<henzeb.console.very.verbose>veryVerbose</henzeb.console.very.verbose>' . PHP_EOL
            ],
            'very-verbose-very-verbose' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'command' => 'veryVerbose',
                'expected' => '<henzeb.console.very.verbose>veryVerbose</henzeb.console.very.verbose>' . PHP_EOL
            ],
            'very-verbose-verbose' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'command' => 'veryVerbose',
                'expected' => ''
            ],
            'very-verbose-normal' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'command' => 'veryVerbose',
                'expected' => ''
            ],
            'very-verbose-quiet' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_QUIET,
                'text' => 'veryVerbose',
                'expected' => ''
            ],

            'verbose-debug' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'command' => 'verbose',
                'expected' => '<henzeb.console.verbose>verbose</henzeb.console.verbose>' . PHP_EOL
            ],
            'verbose-very-verbose' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'command' => 'verbose',
                'expected' => '<henzeb.console.verbose>verbose</henzeb.console.verbose>' . PHP_EOL
            ],
            'verbose-verbose' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'command' => 'verbose',
                'expected' => '<henzeb.console.verbose>verbose</henzeb.console.verbose>' . PHP_EOL
            ],
            'verbose-normal' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'command' => 'verbose',
                'expected' => ''
            ],
            'verbose-quiet' => [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_QUIET,
                'command' => 'verbose',
                'expected' => ''
            ]
        ];
    }

    /**
     * @param int $verbosity
     * @param string $command
     * @param string $expectedOutput
     * @return void
     *
     * @dataProvider providesVerbosityOutputTestcases
     */

    public function testPrintsVerbosityOutput(
        int    $verbosity,
        string $command,
        string $expectedOutput
    )
    {
        $bufferedOutput = new BufferedOutput();
        $output = new ConsoleOutput();

        $output->setOutput(new OutputStyle($output->getInput(), $bufferedOutput));

        $bufferedOutput->setDecorated(true);

        $bufferedOutput->setVerbosity($verbosity);

        $output->$command($command);

        $actualOutput = $bufferedOutput->fetch();

        $this->assertEquals($expectedOutput, $actualOutput);
    }

    public function testSilence(): void
    {
        $bufferedOutput = new BufferedOutput();
        $output = new ConsoleOutput();

        $output->setOutput(new OutputStyle($output->getInput(), $bufferedOutput));

        $output->silence(true)->info('test');
        $this->assertEmpty($bufferedOutput->fetch());

        $output->silence(false)->line('test');
        $this->assertEquals('test' . PHP_EOL, $bufferedOutput->fetch());

        $this->assertEquals(
            ConsoleOutputInterface::VERBOSITY_DEBUG * 2,
            $output->silence(true)->getCurrentVerbosity()
        );
    }

    public function testUnsilence(): void
    {
        $bufferedOutput = new BufferedOutput();
        $output = new ConsoleOutput();

        $output->setOutput(new OutputStyle($output->getInput(), $bufferedOutput));

        $output->unsilence(false)->info('test');
        $this->assertEmpty($bufferedOutput->fetch());

        $output->unsilence(true)->line('test');
        $this->assertEquals('test' . PHP_EOL, $bufferedOutput->fetch());

        $this->assertEquals(
            ConsoleOutputInterface::VERBOSITY_DEBUG * 2,
            $output->unsilence(false)->getCurrentVerbosity()
        );
    }
}
