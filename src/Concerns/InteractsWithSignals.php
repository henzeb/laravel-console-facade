<?php

namespace Henzeb\Console\Concerns;

use Closure;

trait InteractsWithSignals
{
    private array $signalHandlers = [];

    private function getCommandName(): string
    {
        return $this->input->getFirstArgument() ?? 'default';
    }

    public function trap(callable $callable, int ...$signals): void
    {
        $commandName = $this->getCommandName();

        foreach ($signals as $signal) {
            if (!isset($this->signalHandlers[$commandName][$signal])) {
                $this->setPcntlSignalHandler($signal);
            }

            $this->signalHandlers[$commandName][$signal][] = Closure::fromCallable($callable);
        }
    }

    public function untrap(): void
    {
        $commandName = $this->getCommandName();

        $this->signalHandlers[$commandName] = [];
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

        pcntl_async_signals(true);

        pcntl_signal(
            $signal,
            Closure::fromCallable([$this, 'handleSignal'])
        );
    }

    protected function handleSignal(int $signal, $siginfo = null): void
    {
        $shouldExit = false;

        $commandName = $this->getCommandName();

        $signalHandlers = $this->signalHandlers[$commandName][$signal] ?? [];

        $this->untrap();

        foreach ($signalHandlers as $callable) {
            $shouldExit = $callable(...func_get_args()) ? true : $shouldExit;
        }

        if ($shouldExit) {
            $this->exit();
        }
    }
}
