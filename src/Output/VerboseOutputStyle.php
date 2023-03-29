<?php

namespace Henzeb\Console\Output;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VerboseOutputStyle extends OutputStyle
{
    public function __construct(private int $verbosityLevel, InputInterface $input, OutputInterface $output)
    {
        parent::__construct(
            $input,
            $output
        );
    }

    private function shouldWrite(): bool
    {
        return $this->verbosityLevel <= $this->getVerbosity();
    }

    public function write(iterable|string $messages, bool $newline = false, int $options = 0)
    {
        if ($this->shouldWrite()) {
            parent::write($messages, $newline, $options);
        }
    }

    public function writeln(iterable|string $messages, int $type = OutputInterface::OUTPUT_NORMAL)
    {
        if ($this->shouldWrite()) {
            parent::writeln($messages, $type);
        }
    }

    public function getStream()
    {
        return $this->getOutput()->getOutput()->getStream();
    }
}
