<?php

namespace Henzeb\Console\Output;


use Closure;
use Henzeb\Console\Concerns\InteractsWithArguments;
use Henzeb\Console\Concerns\InteractsWithExit;
use Henzeb\Console\Concerns\InteractsWithInfiniteLoop;
use Henzeb\Console\Concerns\InteractsWithIO;
use Henzeb\Console\Concerns\InteractsWithOptions;
use Henzeb\Console\Concerns\InteractsWithSignals;
use Henzeb\Console\Concerns\InteractsWithSleep;
use Henzeb\Console\Concerns\ValidatesInput;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleOutput
{
    use
        Macroable,
        Conditionable,
        ValidatesInput,
        InteractsWithIO,
        InteractsWithExit,
        InteractsWithSleep,
        InteractsWithSignals,
        InteractsWithOptions,
        InteractsWithArguments,
        InteractsWithInfiniteLoop;

    private array $sections = [];

    public function __construct()
    {
        $this->setOutput(
            resolve('henzeb.outputstyle')
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

    public function withProgressBar($totalSteps, Closure $callback)
    {
        return $this->section()->withProgressBar($totalSteps, $callback);
    }

    public function section(string $name = null, callable $render = null): ConsoleSectionOutput
    {
        $section = $this->getSection($name ?? uniqid());
        $section->setInput($this->getInput());

        if ($render) {
            $section->render($render);
        }

        return $section;
    }

    public function watch(
        callable $render,
        int      $refreshRate = 2,
        string   $sectionName = null
    ): void
    {
        if ($refreshRate <= 0) {
            throw new RuntimeException('The refresh rate for watch cannot be lower than 1');
        }

        if ($this->getCurrentVerbosity() > $this->getOutput()->getVerbosity()) {
            return;
        }

        $sectionName = $sectionName ?? uniqid();

        while ($this->infiniteLoop()) {
            $this->section($sectionName)
                ->render($render);

            $this->sleep($refreshRate);
        }
    }

    public function tail(int $maxHeight = 10, string $sectionName = null): TailConsoleSectionOutput
    {
        return $this->section($sectionName)->tail($maxHeight);
    }

    private function getSection(string $name): ConsoleSectionOutput
    {
        if (isset($this->sections[$name])) {
            return $this->sections[$name];
        }

        return $this->sections[$name] = new ConsoleSectionOutput(
            $name,
            $this->getOutput()->getOutput()->getStream(),
            $this->sections,
            $this->getOutput(),
            $this->getInput()
        );
    }
}
