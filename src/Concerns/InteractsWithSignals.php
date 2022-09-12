<?php

namespace Henzeb\Console\Concerns;

use Closure;

trait InteractsWithSignals
{
    private array $signalHandlers = [];

    public function trap(callable $callable, int ...$signals): void
    {
        foreach ($signals as $signal) {
            if (!isset($this->signalHandlers[$signal])) {
                $this->setPcntlSignalHandler($signal);
            }

            $this->signalHandlers[$signal][] = Closure::fromCallable($callable);
        }
    }

    public function untrap(): void
    {
        $this->signalHandlers = [];
    }

    protected function shouldUseSignals(): bool
    {
        return app()->runningInConsole() &&
            !app()->runningUnitTests() &&
            \extension_loaded('pcntl');
    }

    /**
     * @param callable $onSignal
     * @param int ...$signalNumbers
     * @return void
     * @deprecated in favor of trap
     *
     */
    public function onSignal(callable $onSignal, int ...$signalNumbers): void
    {
        $this->trap($onSignal, ...$signalNumbers);
    }

    /**
     * @param int $signal
     * @return void
     */
    protected function setPcntlSignalHandler(int $signal): void
    {
        if (!$this->shouldUseSignals()) {
            return;
        }

        pcntl_signal(
            $signal,
            Closure::fromCallable([$this, 'handleSignal'])
        );
    }

    protected function handleSignal(int $signal, $siginfo = null): void
    {
        $shouldExit = false;

        foreach ($this->signalHandlers[$signal] ?? [] as $callable) {
            $shouldExit = $callable(...func_get_args()) ? true : $shouldExit;
        }

         $this->untrap();

        if ($shouldExit) {
            $this->exit();
        }
    }
}
