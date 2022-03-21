<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;


use Mockery;
use ReflectionProperty;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\Output;
use Henzeb\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;


class ConsoleSectionOutputTest extends TestCase
{
    public function testShouldReturnProgressBar(): void
    {
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $output->expects('getVerbosity')->times(5)->andReturn(1);

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

    public function testShouldRenderTableDelayed(): void
    {
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();

        (new ReflectionProperty(Output::class, 'formatter'))->setValue($output, new OutputFormatter());
        (new ReflectionProperty(ConsoleSectionOutput::class, 'input'))->setValue($output, new ArrayInput([]));

        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

        $output->expects('isDecorated')->andReturn(true);
        $output->expects('overwrite')->with('expectedOutput' . PHP_EOL);


        $output->render(
            function (ConsoleSectionOutput $section) {
                $section->write('expectedOutput');
            }
        );
    }

    public function testShouldDoNothing(): void
    {
        $stream = fopen('php://memory', 'rw+');
        $array = [];
        $console = new ConsoleSectionOutput(
            $stream, $array,
            ConsoleOutput::VERBOSITY_NORMAL,
            false, new OutputFormatter(
            false, []),
            new ArrayInput([]));
        $console->replace('test');
        $console->replace('test2');
        rewind($stream);
        $this->assertEquals("" ,stream_get_contents($stream));
    }

    public function testShouldJustWrite(): void
    {
        $stream = fopen('php://memory', 'rw+');
        $array = [];
        $console = new ConsoleSectionOutput(
            $stream, $array,
            ConsoleOutput::VERBOSITY_NORMAL,
            true, new OutputFormatter(
            true, []),
            new ArrayInput([]));
        $console->replace('test');
        rewind($stream);
        $this->assertEquals("\e[2Ktest\n\e[0J", stream_get_contents($stream));
    }

    public function testShouldReplace(): void
    {
        $stream = fopen('php://memory', 'rw+');
        $array = [];
        $console = new ConsoleSectionOutput(
            $stream, $array,
            ConsoleOutput::VERBOSITY_NORMAL,
            true, new OutputFormatter(
            true, []),
            new ArrayInput([]));
        $console->replace('test');
        $console->replace('test2');
        rewind($stream);
        $this->assertEquals("\e[2Ktest\n\e[0J\e[1A\e[2Ktest2\n\e[0J" ,stream_get_contents($stream));
    }

    public function testReplaceUsesWriteMethod(): void
    {
        /**
         * @var $output ConsoleSectionOutput|Mockery\MockInterface
         */
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $output->expects('isDecorated')->andReturnTrue();
        $output->expects('getStream')->andReturn(fopen('php://memory', 'rw+'));
        // $output->expects('getVerbosity')->times(5)->andReturn(1);
        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

        (new ReflectionProperty($output, 'output'))->setValue($output, $output);

        (new ReflectionProperty(Output::class, 'formatter'))->setValue($output, new OutputFormatter());

        $output->expects('write')->once();

        $output->replace('test');
    }

    public function testReplaceUsesWriteMethodTwice(): void
    {
        /**
         * @var $output ConsoleSectionOutput|Mockery\MockInterface
         */
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $output->expects('isDecorated')->andReturnTrue();
        $output->expects('getStream')->andReturn(fopen('php://memory', 'rw+'));
        // $output->expects('getVerbosity')->times(5)->andReturn(1);
        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

        (new ReflectionProperty($output, 'output'))->setValue($output, $output);

        (new ReflectionProperty(Output::class, 'formatter'))->setValue($output, new OutputFormatter());

        $output->expects('write')->twice();

        $output->replace("test\nwrite");
    }

    public function testReplaceUsesWriteMethodTwiceWithArray(): void
    {
        /**
         * @var $output ConsoleSectionOutput|Mockery\MockInterface
         */
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $output->expects('isDecorated')->andReturnTrue();
        $output->expects('getStream')->andReturn(fopen('php://memory', 'rw+'));
        // $output->expects('getVerbosity')->times(5)->andReturn(1);
        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

        (new ReflectionProperty($output, 'output'))->setValue($output, $output);

        (new ReflectionProperty(Output::class, 'formatter'))->setValue($output, new OutputFormatter());

        $output->expects('write')->twice();

        $output->replace(['test', 'test2']);
    }
}
