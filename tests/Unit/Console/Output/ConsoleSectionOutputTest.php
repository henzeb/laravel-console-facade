<?php

namespace Henzeb\Jukebox\Tests\Unit\Console\Output;


use Mockery;
use ReflectionProperty;
use Orchestra\Testbench\TestCase;
use Henzeb\Console\Facades\Console;
use Symfony\Component\Console\Output\Output;
use Henzeb\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;


class ConsoleSectionOutputTest extends TestCase
{
    public function testShouldReturnProgressBar(): void
    {
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();

        (new ReflectionProperty($output, 'output'))->setValue($output, $output);

        (new ReflectionProperty(Output::class, 'formatter'))->setValue($output, new OutputFormatter());

        $output->expects('createProgressBar')->passthru();

        $output->withProgressBar(100, fn() => true);
    }

    protected function providesVerbosityLevels(): array
    {
        return [
            [OutputInterface::VERBOSITY_DEBUG],
            [OutputInterface::OUTPUT_RAW],
            [OutputInterface::VERBOSITY_NORMAL],
            [OutputInterface::VERBOSITY_QUIET],
        ];
    }

    /**
     * @param int $level
     * @return void
     *
     * @dataProvider providesVerbosityLevels
     */
    public function testShouldReturnProperVerbosityLevel(int $level): void
    {
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $output->setVerbosity($level);
        $this->assertEquals($level, $output->getVerbosity());
    }
}
