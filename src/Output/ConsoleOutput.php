<?php

namespace Henzeb\Console\Output;

use Closure;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

class ConsoleOutput
{
    use InteractsWithIO;

    private array $sections = [];
    private array $onExit = [
        'always' => []
    ];

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

    public function onExit(callable $onExit, int $exitCode = null): void
    {
        $this->onExit[$exitCode ?? 'always'][] = Closure::fromCallable($onExit)->bindTo(null, null);
    }

    private function getExitMethod(): callable
    {
        return $this->exitMethod =  $this->exitMethod ?? fn(int $exitcode) => exit($exitcode);
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
