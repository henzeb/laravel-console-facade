<?php

namespace Henzeb\Console\Concerns;

use Closure;
use Mockery;
use RuntimeException;
use Mockery\Expectation;
use PHPUnit\Framework\Assert;
use Mockery\ExpectationInterface;
use Illuminate\Support\Facades\App;

trait InteractsWithSleep
{
    private ?Closure $sleepMethod = null;

    public function sleep(int $seconds): void
    {
        $this->sleepMethod ? ($this->sleepMethod)($seconds) : sleep($seconds);
    }

    public function shouldSleep(): void
    {
        $this->setShouldSleep()
            ->atLeast()
            ->once();
    }

    public function shouldSleepWith(int $seconds): void
    {
        $this->setShouldSleep()
            ->atLeast()
            ->once()
            ->andReturnUsing(function ($actual) use ($seconds) {
                Assert::assertEquals(
                    $seconds,
                    $actual,
                    'Failed asserting shouldSleep'
                );
            });
    }

    public function shouldNotsleep(): void
    {
        $this->setShouldSleep()
            ->never();
    }

    /**
     * @return Expectation|ExpectationInterface|Mockery\HigherOrderMessage
     */
    private function setShouldSleep()
    {
        if (!App::runningUnitTests()) {
            throw new RuntimeException('Not running inside a UnitTest');
        }

        return tap(
            Mockery::mock('ConsoleOutput'),
            function ($mock) {
                $this->sleepMethod = function ($with) use ($mock) {
                    return $mock->makePartial()->sleep($with);
                };
            }
        )->shouldReceive('sleep');
    }
}
