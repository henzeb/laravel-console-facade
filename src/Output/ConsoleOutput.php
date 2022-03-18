<?php

namespace Henzeb\Console\Output;

use ReflectionProperty;
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

    public function __construct()
    {
        $this->setOutput(
            new OutputStyle(
                new ArrayInput([]),
                new SymfonyConsoleOutput()
            )
        );
    }

    private function getInput(): InputInterface
    {
        if ($this->input) {
            return $this->input;
        }

        return $this->input = (new ReflectionProperty(
            SymfonyStyle::class,
            'input'
        ))->getValue($this->output);
    }

    public function section(string $name): ConsoleSectionOutput
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
