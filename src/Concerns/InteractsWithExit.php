<?php

namespace Henzeb\Console\Concerns;

use Closure;
use Mockery;
use Mockery\Expectation;
use RuntimeException;
use Illuminate\Support\Facades\App;

trait InteractsWithExit
{
    private ?Closure $exitMethod = null;
    private bool $exitCalled = false;

    /**
     * @var array<int|string, array<int, Closure>>
     */
    private array $onExit = [
        'always' => []
    ];

    public function onExit(callable $onExit, ?int $exitCode = null): void
    {
        $this->onExit[$exitCode ?? 'always'][] = Closure::fromCallable($onExit);
    }

    public function exit(int $exitcode = 0): void
    {
        foreach ($this->onExit['always'] as $always) {
            $always($exitcode);
        }

        foreach ($this->onExit[$exitcode] ?? [] as $onExit) {
            $onExit();
        }

        ($this->exitMethod ?? exit($exitcode))($exitcode);
    }

    public function shouldExit(): void
    {
        $this->shouldExitWith(0);
    }

    public function shouldExitWith(int $code): void
    {
        $this->getExitMock()->once()->with($code);
    }

    public function shouldNotExit(): void
    {
        $this->getExitMock()
            ->never();
    }

    /**
     * @return Expectation
     */
    private function getExitMock()
    {
        if (!App::runningUnitTests()) {
            throw new RuntimeException('Not running inside a UnitTest');
        }

        $mock = Mockery::mock(self::class);

        $this->exitMethod = function (int $code) use ($mock): void {
            $partial = $mock->makePartial();

            if (method_exists($partial, 'exit')) {
                $partial->exit($code);
            }
        };

        return $mock->expects('exit');
    }
}
