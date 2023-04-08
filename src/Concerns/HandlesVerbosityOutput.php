<?php

namespace Henzeb\Console\Concerns;

use Henzeb\Console\Output\NullOutput;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use Symfony\Component\Console\Output\OutputInterface;

trait HandlesVerbosityOutput
{
    private ?int $verbosityLevel = null;

    private function getVerbosityConsoleOutput(int $verbosity): self
    {
        if ($verbosity <= $this->getOutput()->getVerbosity()) {
            return $this;
        }

        return tap(
            clone $this,
            function (self $clone) use ($verbosity) {
                $clone->verbosityLevel = $verbosity;
                $clone->setOutput(
                    new OutputStyle($this->input, new NullOutput())
                );
            }
        );
    }

    private function verboseOutput(
        int     $verbosity,
        string  $style,
        ?string $message
    ): self
    {
        return tap(
            $this->getVerbosityConsoleOutput($verbosity),
            function (self $verboseConsole) use ($message, $style, $verbosity) {
                if ($message) {
                    $verboseConsole->line(
                        $message,
                        'henzeb.' . $style
                    );
                }
            }
        );
    }

    public function getCurrentVerbosity(): int
    {
        if ($this->verbosityLevel) {
            return $this->verbosityLevel;
        }

        return $this->getOutput()->getVerbosity();
    }

    public function silence(bool $silence): self
    {
        if ($silence) {
            return $this->verboseOutput(
                ConsoleOutputInterface::VERBOSITY_DEBUG * 2,
                '',
                null
            );
        }

        return $this;
    }

    public function unsilence(bool $unsilence): self
    {
        return $this->silence(!$unsilence);
    }

    public function verbose(string $message = null): self
    {
        return $this->verboseOutput(
            OutputInterface::VERBOSITY_VERBOSE,
            'console.verbose',
            $message
        );
    }

    public function veryVerbose(string $message = null): self
    {
        return $this->verboseOutput(
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            'console.very.verbose',
            $message
        );
    }

    public function debug(string $message = null): self
    {
        return $this->verboseOutput(
            OutputInterface::VERBOSITY_DEBUG,
            'console.debug',
            $message
        );
    }
}
