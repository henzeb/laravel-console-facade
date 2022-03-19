<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;


use Mockery;
use ReflectionProperty;
use Orchestra\Testbench\TestCase;
use Henzeb\Console\Facades\Console;
use Illuminate\Console\OutputStyle;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Output\ConsoleSectionOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;


class ConsoleOutputTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
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
        $output = (new ConsoleOutput());
        $section = Mockery::mock(ConsoleSectionOutput::class)->makePartial();
        $expectedCallable = function (ConsoleSectionOutput $section) {
            $section->write('test');
        };
        $section->expects('render')->with($expectedCallable);

        (new ReflectionProperty($output, 'sections'))->setValue($output, ['mySection' => $section]);
        $output->section('mySection', $expectedCallable);
    }
}
