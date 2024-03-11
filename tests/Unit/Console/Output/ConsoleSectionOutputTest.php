<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;


use Closure;
use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\BufferedOutput;
use Henzeb\Console\Output\ConsoleSectionOutput;
use Henzeb\Console\Output\TailConsoleSectionOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\View\Components\Factory;
use Mockery;
use Orchestra\Testbench\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use ValueError;

// leave it, for Laravel 9.21+


class ConsoleSectionOutputTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ConsoleServiceProvider::class
        ];
    }

    public function testShouldGetCorrectInput(): void
    {
        $output = new \Henzeb\Console\Output\ConsoleOutput();
        $section = $output->section('test');

        $this->assertSame($output->getInput(), $section->getInput());
    }

    public function testShouldReturnProgressBar(): void
    {
        $outputStyle = $this->mock(OutputStyle::class);
        $outputStyle->expects('isDecorated')->andReturn(true);
        $outputStyle->expects('getVerbosity')->andReturn(true);
        $outputStyle->expects('getFormatter')->atLeast()->times(3)->andReturn(new OutputFormatter());
        $outputStyle->expects('createProgressBar')->andReturns(new ProgressBar($outputStyle));

        $outputStyle->expects('write')->times(4);
        $outputStyle->expects('getVerbosity')->twice()->andReturn(1);

        $output = $this->mock(ConsoleSectionOutput::class);
        $output->shouldAllowMockingProtectedMethods();
        $output->expects('contentEndsWithNewLine')->andReturn(true);
        (function () use ($outputStyle) {
            $this->output = $outputStyle;
        })->bindTo($output, ConsoleSectionOutput::class)();


        $output->makePartial()->withProgressBar(100, fn() => true);
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

        Closure::bind(function () {
            $this->output = $this;
        }, $output, ConsoleSectionOutput::class)();


        $output->setVerbosity(ConsoleOutput::VERBOSITY_NORMAL);
        $output->expects('name')->andReturn('test');
        $output->expects('isDecorated')->andReturn(true);
        $output->expects('replace')->with('expectedOutput' . PHP_EOL);

        $output->render(
            function (ConsoleSectionOutput $section) {
                $section->write('expectedOutput', true);
            }
        );
    }

    public function testShouldDoNothing(): void
    {
        $stream = fopen('php://memory', 'rw+');
        $array = [];
        $console = new ConsoleSectionOutput(
            'name',
            $stream, $array,
            new ConsoleOutput(decorated: false),
            new ArrayInput([])
        );
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
            'test',
            $stream, $array,
            new ConsoleOutput(decorated: true),
            new ArrayInput([])
        );
        $console->replace('test');
        rewind($stream);
        $this->assertEquals("\e[2Ktest\n\e[0J", stream_get_contents($stream));
    }

    public function testShouldReplace(): void
    {
        $stream = fopen('php://memory', 'rw+');

        $array = [];
        $console = new ConsoleSectionOutput(
            'test',
            $stream,
            $array,
            new ConsoleOutput(decorated: true),
            new ArrayInput([])
        );
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

    public static function providesVerbosityLevels(): array
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
        if (!class_exists('Illuminate\Console\View\Components\Factory')) {
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

    public function testClear()
    {
        $section = $this->mock(ConsoleSectionOutput::class);
        $section->expects('write')->with("\033[0m")->once();
        $section->makePartial()->clear();
    }

    public function testClearWithLines()
    {
        $section = $this->mock(ConsoleSectionOutput::class);
        $section->expects('write')->never();
        $section->makePartial()->clear(1);
    }

    public function testShouldDelete()
    {
        $reflector = new ReflectionClass(ConsoleSectionOutput::class);
        $rewindMethod = $reflector->getMethod('delete');

        if (!$rewindMethod->isPublic()) {
            $this->fail('The visibility of rewind method is not public');
        }

        $section = $this->mock(ConsoleSectionOutput::class);
        $section->expects('clear')->with(null)->once();
        $section->expects('clear')->with(1)->once();
        $section = $section->makePartial();
        $this->assertSame($section, $section->delete());

        $section->delete(1);
    }

    public function testNewLine(): void
    {
        $buffer = new BufferedOutput();
        $buffer->setDecorated(true);

        Console::setOutput(new OutputStyle(Console::getInput(), $buffer));
        $section = Console::section('test');

        $section->newLine();

        $this->assertEquals(PHP_EOL, $buffer->fetch());

        $section->newLine(2);
        $this->assertEquals(PHP_EOL . PHP_EOL, $buffer->fetch());

        $this->expectException(ValueError::class);
        $section->newLine(0);
    }

    public function testReturnTail()
    {
        $console = (new \Henzeb\Console\Output\ConsoleOutput())->section('test');

        $this->assertSame(
            $console->tail(1),
            $console->tail(2)
        );
        $tail = $console->tail();

        $this->assertInstanceOf(TailConsoleSectionOutput::class, $tail);

        $this->assertEquals(10, $tail->getMaxHeight());

        $tail = $console->tail(2);
        $this->assertEquals(2, $tail->getMaxHeight());
    }

    public function testGetName(): void
    {
        $console = (new \Henzeb\Console\Output\ConsoleOutput())->section('test');

        $this->assertEquals('test', $console->name());
    }

    public function testGetNameOfUniqueID(): void
    {
        $console = new \Henzeb\Console\Output\ConsoleOutput();
        $section = $console->section();

        $this->assertSame($section, $console->section($section->name()));
    }
}
