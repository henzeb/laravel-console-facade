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

// leave it, for Laravel 9.21+
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Henzeb\Console\Tests\Unit\Console\Helpers\InfiniteLoop;
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
        Console::partialMock()->expects('setOutput')->twice();

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

    /**
     * Test for Laravel 9.21+
     * @return void
     */
    public function testShouldReturnComponentsFactory(): void
    {
        if (!class_exists('Illuminate\Console\View\Components\Factory')) {
            $this->markTestSkipped('skipped as it is not available for this version');
        }
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

    public function testWatchDoesNotAllowZeroRefreshRate()
    {
        $output = (new ConsoleOutput());

        $this->expectException(\RuntimeException::class);

        $output->watch(fn() => true, 0);
    }

    public function testWatchDoesNotAllowNegativeRefreshRate()
    {
        $output = (new ConsoleOutput());

        $this->expectException(\RuntimeException::class);

        $output->watch(fn() => true, -1);
    }

    public function providesWatchRuns()
    {
        return [
            ['loops' => 2, 'sleep' => 1],
            ['loops' => 4, 'sleep' => 5],
            ['loops' => 4, 'sleep' => 5, 'name'=>'section_name'],
        ];
    }


    /**
     * @param int $loops
     * @param int $sleep
     * @return void
     *
     * @dataProvider providesWatchRuns
     */
    public function testWatchShouldRun(int $loops, int $sleep, string $name = null)
    {
        $output = Mockery::mock(ConsoleOutput::class)->makePartial();
        $output->__construct();


        $output->watchShouldLoop($loops, $sleep);

        if($name) {
            $output->shouldReceive('section')->once()->passthru()->with($name);
        }
        $output->shouldReceive('section')->passthru();

        $actual = 0;

        $output->watch(
            function () use (&$actual) {
                $actual++;
            },
            $sleep,
            $name
        );
        $this->assertEquals($loops, $actual);
    }
}
