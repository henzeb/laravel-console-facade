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
use Symfony\Component\Console\Output\ConsoleSectionOutput as SymfonyConsoleSectionOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class ConsoleOutput
{
    use
        Macroable;
    use Conditionable;
    use ValidatesInput;
    use InteractsWithIO;
    use InteractsWithExit;
    use InteractsWithSleep;
    use InteractsWithSignals;
    use InteractsWithOptions;
    use InteractsWithArguments;
    use InteractsWithInfiniteLoop;

    /**
     * @var array<int|string, SymfonyConsoleSectionOutput>
     */
    private array $sections = [];

    public function __construct()
    {
        $output = resolve('henzeb.outputstyle');

        if (!$output instanceof OutputStyle) {
            throw new RuntimeException('henzeb.outputstyle does not resolve to ' . OutputStyle::class);
        }

        $this->setOutput($output);
    }

    /**
     * @return void
     */
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
            fn () => $this->input,
            $this->output,
            SymfonyStyle::class
        )();
    }

    /**
     * @param iterable<mixed>|int $totalSteps
     * @return mixed
     */
    public function withProgressBar($totalSteps, Closure $callback)
    {
        return $this->section()->withProgressBar($totalSteps, $callback);
    }

    public function section(?string $name = null, ?callable $render = null): ConsoleSectionOutput
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
        ?string   $sectionName = null
    ): void {
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

    public function tail(int $maxHeight = 10, ?string $sectionName = null): TailConsoleSectionOutput
    {
        return $this->section($sectionName)->tail($maxHeight);
    }

    private function getSection(string $name): ConsoleSectionOutput
    {
        $section = $this->sections[$name] ?? null;

        if ($section instanceof ConsoleSectionOutput) {
            return $section;
        }

        $output = $this->getOutput()->getOutput();
        $stream = method_exists($output, 'getStream') ? $output->getStream() : null;

        $section = new ConsoleSectionOutput(
            $name,
            is_resource($stream) ? $stream : null,
            $this->sections,
            $this->getOutput(),
            $this->getInput()
        );

        $this->sections[$name] = $section;

        return $section;
    }
}
