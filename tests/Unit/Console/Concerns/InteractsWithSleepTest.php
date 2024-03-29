<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Support\Facades\App;
use Mockery;
use Mockery\Exception\InvalidCountException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;
use RuntimeException;


class InteractsWithSleepTest extends TestCase
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
            'shouldSleep' => ['shouldSleep'],
            'shouldNotSleep' => ['shouldNotSleep'],
            'shouldSleepWith' => ['shouldSleepWith'],
        ];
    }

    /**
     * @param string $method
     * @return void
     *
     * @dataProvider providesMethods
     */
    public function testShouldSleepWithThrowExceptionWhenNotInUnitTests(string $method)
    {
        App::shouldReceive('runningUnitTests')
            ->andReturn(false);

        App::shouldReceive('runningInConsole')
            ->andReturn(true);

        $this->expectException(RuntimeException::class);
        $output = new ConsoleOutput();

        $output->$method(1);
    }

    public function testShouldSleep()
    {
        $output = new ConsoleOutput();
        $output->shouldSleep();
        $output->sleep(1);
    }

    public function testShouldSleepFails()
    {
        $output = new ConsoleOutput();

        $this->expectException(InvalidCountException::class);
        $output->shouldSleep();
        Mockery::close();
    }

    public function testShouldNotSleep()
    {
        $output = new ConsoleOutput();
        $output->shouldNotsleep();
    }

    public function testShouldNotSleepFails()
    {
        $output = new ConsoleOutput();

        $this->expectException(InvalidCountException::class);
        $output->shouldNotsleep();
        $output->sleep(1);
        Mockery::close();
    }

    public function testShouldSleepWith()
    {
        $output = new ConsoleOutput();
        for ($seconds = 0; $seconds < 10; $seconds++) {
            $output->shouldSleepWith($seconds);
            $output->sleep($seconds);
        }
    }

    public function testShouldSleepWithFails()
    {
        $output = new ConsoleOutput();
        $this->expectException(ExpectationFailedException::class);
        $output->shouldSleepWith(5);
        $output->sleep(6);
    }
}
