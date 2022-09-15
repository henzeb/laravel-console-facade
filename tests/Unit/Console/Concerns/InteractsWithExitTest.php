<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use Closure;
use PHPUnit\Framework\TestCase;
use Henzeb\Console\Output\ConsoleOutput;

class InteractsWithExitTest extends TestCase
{
    private function mockExit(ConsoleOutput $output, callable $callback = null): void
    {
        Closure::bind(function () use ($callback) {
            $this->exitMethod = $callback ?? fn() => true;
        }, $output, ConsoleOutput::class)();
    }

    public function testShouldExit(): void
    {
        $output = new ConsoleOutput();

        $this->mockExit($output, function (int $exitcode) {
            $this->assertEquals(0, $exitcode);
        });

        $output->exit();
    }

    public function testShouldCallOnExit()
    {
        $output = new ConsoleOutput();
        $this->mockExit($output);
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
        $this->mockExit($output);
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
        $this->mockExit($output);
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
        $this->mockExit($output);
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
        $this->mockExit($output);
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
        $this->mockExit($output);
        $actual = false;

        $output->onExit(function () use (&$actual) {
            $actual = true;
        }, 1);

        $output->exit(1);

        $this->assertTrue($actual);
    }
}
