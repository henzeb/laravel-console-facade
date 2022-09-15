<?php

namespace Henzeb\Console\Concerns;

use Closure;

trait InteractsWithExit
{
    private array $onExit = [
        'always' => []
    ];

    public function onExit(callable $onExit, int $exitCode = null): void
    {
        $this->onExit[$exitCode ?? 'always'][] = Closure::fromCallable($onExit);
    }

    private function getExitMethod(): callable
    {
        return $this->exitMethod = $this->exitMethod ?? fn(int $exitcode) => exit($exitcode);
    }

    public function exit(int $exitcode = 0): void
    {
        foreach ($this->onExit['always'] as $always) {
            $always($exitcode);
        }

        foreach ($this->onExit[$exitcode] ?? [] as $onExit) {
            $onExit();
        }

        $this->getExitMethod()($exitcode);
    }
}
