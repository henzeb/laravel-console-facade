<?php

namespace Henzeb\Console\Output;

use Closure;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Traits\Macroable;
use Henzeb\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Traits\Conditionable;
use Henzeb\Console\Concerns\InteractsWithExit;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Henzeb\Console\Concerns\InteractsWithSignals;
use Henzeb\Console\Concerns\InteractsWithOptions;
use Symfony\Component\Console\Input\InputInterface;
use Henzeb\Console\Concerns\InteractsWithArguments;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

class ConsoleOutput
{
    use InteractsWithIO,
        Conditionable,
        Macroable,
        InteractsWithSignals,
        InteractsWithExit,
        InteractsWithOptions,
        InteractsWithArguments;

    private array $sections = [];

    private Closure $exitMethod;

    public function __construct()
    {
        $this->exitMethod = fn(int $exitcode) => exit($exitcode);
        $this->setOutput(
            new OutputStyle(
                new ArrayInput([]),
                new SymfonyConsoleOutput()
            )
        );
    }

    public function setOutput(OutputStyle $output)
    {
        $this->output = $output;

        /**
         * InteractsWithIo does not use getInput
         */
        $this->input = $this->getInput();
    }

    public function getInput(): InputInterface
    {
        return Closure::bind(
            fn() => $this->input,
            $this->output,
            SymfonyStyle::class
        )();
    }

    public function section(string $name, callable $render = null): ConsoleSectionOutput
    {
        $section = $this->getSection($name);
        if ($render) {
            $section->render($render);
        }
        return $section;
    }

    private function getSection(string $name): ConsoleSectionOutput
    {
        if (isset($this->sections[$name])) {
            return $this->sections[$name];
        }

        return $this->sections[$name] = new ConsoleSectionOutput(
            $this->getOutput()->getOutput()->getStream(),
            $this->sections,
            $this->output->getVerbosity(),
            $this->output->isDecorated(),
            $this->output->getFormatter(),
            $this->getInput(),
        );
    }


}
