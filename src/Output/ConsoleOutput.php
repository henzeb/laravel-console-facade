<?php

namespace Henzeb\Console\Output;


use Closure;
use RuntimeException;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Macroable;
use Henzeb\Console\Concerns\ValidatesInput;
use Henzeb\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Traits\Conditionable;
use Henzeb\Console\Concerns\InteractsWithExit;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Henzeb\Console\Concerns\InteractsWithSleep;
use Symfony\Component\Console\Style\SymfonyStyle;
use Henzeb\Console\Concerns\InteractsWithSignals;
use Henzeb\Console\Concerns\InteractsWithOptions;
use Henzeb\Console\Concerns\InteractsWithCommand;
use Symfony\Component\Console\Input\InputInterface;
use Henzeb\Console\Concerns\InteractsWithArguments;
use Henzeb\Console\Concerns\InteractsWithInfiniteLoop;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

class ConsoleOutput
{
    private array $sections = [];

    use
        Macroable,
        Conditionable,
        ValidatesInput,
        InteractsWithIO,
        InteractsWithExit,
        InteractsWithSleep,
        InteractsWithSignals,
        InteractsWithOptions,
        InteractsWithCommand,
        InteractsWithArguments,
        InteractsWithInfiniteLoop;

    public function __construct()
    {
        $this->setOutput(
            new OutputStyle(
                App::runningUnitTests() ? new ArrayInput([]) : new ArgvInput(),
                new SymfonyConsoleOutput()
            )
        );
    }

    public function setOutput(OutputStyle $output)
    {
        $this->output = $output;

        /**
         * InteractsWithIO does not use getInput
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

    public function watch(
        callable $render,
        int $refreshRate = 2,
        string $sectionName = null
    ): void {
        if ($refreshRate <= 0) {
            throw new RuntimeException('The refresh rate for watch cannot be lower than 1');
        }

        $sectionName = $sectionName ?? uniqid();

        while ($this->infiniteLoop()) {
            $this->section($sectionName)
                ->render($render);

            $this->sleep($refreshRate);
        }
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
