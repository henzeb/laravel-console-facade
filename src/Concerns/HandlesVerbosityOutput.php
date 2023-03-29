<?php

namespace Henzeb\Console\Concerns;

use Henzeb\Console\Input\VerboseInput;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Output\ConsoleSectionOutput;
use Henzeb\Console\Output\VerboseOutputStyle;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait HandlesVerbosityOutput
{
    protected static array $verbosityInstances = [];

    private function getVerbosityConsoleOutput(int $verbosity): self
    {
        if (!isset(self::$verbosityInstances[$verbosity])) {
            self::$verbosityInstances[$verbosity] = clone $this;
        }

        /**
         * @var $console ConsoleOutput|ConsoleSectionOutput
         */
        $console = self::$verbosityInstances[$verbosity];

        $input = new VerboseInput(
            $this->getInput(),
            $verbosity,
            $console->output->getVerbosity()
        );

        $console->setOutput(
            new VerboseOutputStyle(
                $verbosity,
                $input,
                new VerboseOutputStyle(
                    $verbosity,
                    $input,
                    $this->getOutput()
                )
            )
        );
        $console->setInput($input);

        return $console;
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
        if (in_array($this, self::$verbosityInstances)) {
            return array_search($this, self::$verbosityInstances);
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

    public function unsilence(bool $output): self
    {
        return $this->silence(!$output);
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
