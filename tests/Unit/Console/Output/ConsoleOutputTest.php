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
            OutputStyle::class, (new ConsoleOutput())->getOutput()
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
            $this->exitMethod = $callback ?? fn()=>true;
        }, $output, ConsoleOutput::class)();
    }

    public function testShouldExit(): void
    {
        $output = new ConsoleOutput();

        $this->mockExit($output, function(int $exitcode){
            $this->assertEquals(0, $exitcode);
        });

        $output->exit();
    }

    public function testShouldCallOnExit() {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function() use (&$actual){
            $actual = true;
        });

        $output->exit();

        $this->assertTrue($actual);
    }

    public function testShouldCallOnExitWithDifferentStatus() {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function() use (&$actual){
            $actual = true;
        });

        $output->exit(1);

        $this->assertTrue($actual);
    }

    public function testShouldCallTwiceOnExit() {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = 0;

        $output->onExit(function() use (&$actual){
            $actual++;
        });

        $output->onExit(function() use (&$actual){
            $actual++;
        });

        $output->exit();

        $this->assertEquals(2, $actual);
    }

    public function testShouldCallTwiceOnExitWithDifferentStatusCode() {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = 0;

        $output->onExit(function() use (&$actual){
            $actual++;
        });

        $output->onExit(function() use (&$actual){
            $actual++;
        },1);

        $output->exit(1);

        $this->assertEquals(2, $actual);
    }

    public function testShouldNotCallWhenStatusCodeDoesNotMatch() {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function() use (&$actual){
            $actual = true;
        }, 0);

        $output->exit(1);

        $this->assertFalse($actual);
    }

    public function testShouldCallWhenStatusCodeDoesMatch() {
        $output = new ConsoleOutput();
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function() use (&$actual){
            $actual = true;
        }, 1);

        $output->exit(1);

        $this->assertTrue($actual);
    }
}
