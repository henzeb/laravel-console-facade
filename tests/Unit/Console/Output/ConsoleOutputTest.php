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

    public function testShouldReturnComponentsFactory(): void
    {
        if(!class_exists('Illuminate\Console\View\Components\Factory')) {
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
}
