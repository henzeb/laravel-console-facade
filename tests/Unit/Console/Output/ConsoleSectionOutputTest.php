<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;


use Mockery;
use Closure;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Output\Output;
use Henzeb\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Console\View\Components\Factory; // leave it, for Laravel 9.21+
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatter;


class ConsoleSectionOutputTest extends TestCase
{
    public function testShouldReturnProgressBar(): void
    {
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $output->expects('getVerbosity')->times(5)->andReturn(1);

        Closure::bind(
            function () use ($output) {
            $output->output = $output;
        }, $output, ConsoleSectionOutput::class)();

        Closure::bind(function () {
            $this->formatter = new OutputFormatter();
        }, $output, Output::class)();

        $output->expects('createProgressBar')->passthru();

        $output->withProgressBar(100, fn() => true);
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

        Closure::bind(function () {
            $this->formatter = new OutputFormatter();
        }, $output, Output::class)();

        Closure::bind(function () {
            $this->input = new ArrayInput([]);
        }, $output, ConsoleSectionOutput::class)();


        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

        $output->expects('isDecorated')->andReturn(true);
        $output->expects('replace')->with('expectedOutput' . PHP_EOL);

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
        $this->assertEquals("", stream_get_contents($stream));
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
        $this->assertEquals("\e[2Ktest\n\e[0J\e[1A\e[2Ktest2\n\e[0J", stream_get_contents($stream));
    }

    public function testReplaceUsesWriteMethod(): void
    {
        /**
         * @var $output ConsoleSectionOutput|Mockery\MockInterface
         */
        $output = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $output->expects('isDecorated')->andReturnTrue();
        $output->expects('getStream')->andReturn(fopen('php://memory', 'rw+'));

        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

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

        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

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

        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);

        $output->expects('write')->twice();

        $output->replace(['test', 'test2']);
    }

    public function providesVerbosityLevels(): array
    {
        return [
            [OutputInterface::VERBOSITY_DEBUG],
            [OutputInterface::OUTPUT_RAW],
            [OutputInterface::VERBOSITY_NORMAL],
            [OutputInterface::VERBOSITY_QUIET],
        ];
    }

    /**
     * Test for Laravel 9.21+
     * @return void
     */
    public function testShouldReturnComponentsFactory(): void
    {
        if(!class_exists('Illuminate\Console\View\Components\Factory')) {
            $this->markTestSkipped('skipped as it is not available for this version');
        }

        $output = new \Henzeb\Console\Output\ConsoleOutput();

        $expectedOutput = $output->getOutput();

        $output = $output->section('test');

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
