<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;

use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Output\VerboseOutputStyle;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class VerboseOutputStyleTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
    }

    public function testGetStream(): void
    {
        $console = new ConsoleOutput();
        $section = $console->section('test');

        $this->assertSame(
            $section->getOutput()->getStream(),
            $section->debug()->getOutput()->getStream()
        );
    }

    public function providesWriteTestcases(): array
    {
        return [
            [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'level' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'expected' => 'test',
                'actual' => 'test'
            ],
            [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'level' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'expected' => '',
                'actual' => 'test'
            ],
            [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'level' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'expected' => '',
                'actual' => 'test'
            ],
            [
                'verbosity' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'level' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'expected' => '',
                'actual' => 'test'
            ]

        ];
    }

    /**
     * @param int $verbosity
     * @param int $verbosityLevel
     * @param string $expected
     * @param string $actual
     * @return void
     *
     * @dataProvider providesWriteTestcases
     */
    public function testWrite(int $verbosity, int $verbosityLevel, string $expected, string $actual): void
    {
        $bufferedOutput = new BufferedOutput();
        $bufferedOutput->setVerbosity($verbosity);


        $outputStyle = new VerboseOutputStyle($verbosityLevel, new StringInput(''), $bufferedOutput);

        $outputStyle->write($actual);

        $this->assertEquals($expected, $bufferedOutput->fetch());

    }
}
