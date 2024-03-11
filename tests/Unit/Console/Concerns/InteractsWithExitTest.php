<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Support\Facades\App;
use Orchestra\Testbench\TestCase;
use RuntimeException;


class InteractsWithExitTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ConsoleServiceProvider::class
        ];
    }

    public static function providesMethods(): array
    {
        return [
            'shouldExit' => ['shouldExit'],
            'shouldNotExit' => ['shouldNotExit'],
            'shouldExitWith' => ['shouldExitWith'],
        ];
    }

    /**
     * @param string $method
     * @return void
     *
     * @dataProvider providesMethods
     */
    public function testShouldThrowExceptionWhenNotInUnitTests(string $method)
    {
        App::shouldReceive('runningUnitTests')
            ->andReturn(false);
        App::shouldReceive('runningInConsole')
            ->andReturn(true);
        $this->expectException(RuntimeException::class);
        $output = new ConsoleOutput();

        $output->$method(1);
    }

    public function testShouldExit(): void
    {
        $output = new ConsoleOutput();

        $output->shouldExit();

        $output->exit();
    }

    public function testShouldCallOnExit()
    {
        $output = new ConsoleOutput();
        $output->shouldExit();
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        });

        $output->exit();

        $this->assertTrue($actual);
    }

    public function testShouldCallOnExitWithDifferentStatus()
    {
        $output = new ConsoleOutput();
        $output->shouldExitWith(1);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        });

        $output->exit(1);

        $this->assertTrue($actual);
    }

    public function testShouldCallTwiceOnExit()
    {
        $output = new ConsoleOutput();
        $output->shouldExit();
        $actual = 0;

        $output->onExit(function () use (&$actual) {
            $actual++;
        });

        $output->onExit(function () use (&$actual) {
            $actual++;
        });

        $output->exit();

        $this->assertEquals(2, $actual);
    }

    public function testShouldCallTwiceOnExitWithDifferentStatusCode()
    {
        $output = new ConsoleOutput();
        $output->shouldExitWith(1);
        $actual = 0;

        $output->onExit(function () use (&$actual) {
            $actual++;
        });

        $output->onExit(function () use (&$actual) {
            $actual++;
        }, 1);

        $output->exit(1);

        $this->assertEquals(2, $actual);
    }

    public function testShouldNotCallWhenStatusCodeDoesNotMatch()
    {
        $output = new ConsoleOutput();
        $output->shouldExitWith(1);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        }, 0);

        $output->exit(1);

        $this->assertFalse($actual);
    }

    public function testShouldCallWhenStatusCodeDoesMatch()
    {
        $output = new ConsoleOutput();
        $output->shouldExitWith(1);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        }, 1);

        $output->exit(1);

        $this->assertTrue($actual);
    }

    public function testShouldNotExit(): void
    {
        $output = new ConsoleOutput();
        $output->shouldNotExit();
    }
}
