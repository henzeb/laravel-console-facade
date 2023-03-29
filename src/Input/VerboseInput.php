<?php

namespace Henzeb\Console\Input;

use Symfony\Component\Console\Input\Input;

class VerboseInput extends Input
{
    public function __construct(
        private Input $input,
        private int   $verbosityLevel,
        private int   $outputVerbosity
    )
    {
        parent::__construct();

        $this->definition = &$this->input->definition;
        $this->options = &$this->input->options;
        $this->arguments = &$this->input->arguments;
        $this->stream = &$this->input->stream;
    }

    protected function parse()
    {
        $this->input->parse();
    }

    public function getFirstArgument(): ?string
    {
        return $this->input->getFirstArgument();
    }

    public function hasParameterOption(array|string $values, bool $onlyParams = false): bool
    {
        return $this->input->hasParameterOption($values, $onlyParams);
    }

    public function getParameterOption(array|string $values, float|array|bool|int|string|null $default = false, bool $onlyParams = false)
    {
        return $this->input->getParameterOption($values, $default, $onlyParams);
    }

    public function setOption(string $name, mixed $value)
    {
        if ($this->isInteractive()) {
            parent::setOption($name, $value);
        }
    }

    public function setArgument(string $name, mixed $value)
    {
        if ($this->isInteractive()) {
            parent::setArgument($name, $value);
        }
    }

    public function setStream($stream)
    {
        if ($this->isInteractive()) {
            parent::setStream($stream);
        }
    }

    public function isInteractive(): bool
    {
        return $this->input->isInteractive() && $this->verbosityLevel <= $this->outputVerbosity;
    }
}
