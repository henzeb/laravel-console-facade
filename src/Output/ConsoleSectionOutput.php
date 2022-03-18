<?php

namespace Henzeb\Console\Output;

use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput as SymfonyConsoleSectionOutput;

class ConsoleSectionOutput extends SymfonyConsoleSectionOutput
{
    use InteractsWithIO {
        anticipate as private;
        ask as private;
        askWithCompletion as private;
        choice as private;
        confirm as private;
        question as private;
        secret as private;
    }

    public function __construct(
        $stream,
        array &$sections,
        int $verbosity,
        bool $decorated,
        OutputFormatterInterface $formatter,
        InputInterface $input,
    )
    {
        $this->input = $input;
        $this->output = $this;

        parent::__construct($stream, $sections, $verbosity, $decorated, $formatter);
    }

    public function createProgressBar(int $max = 0): ProgressBar
    {
        $progressBar = new ProgressBar($this->output, $max);

        if ('\\' !== \DIRECTORY_SEPARATOR || 'Hyper' === getenv('TERM_PROGRAM')) {
            $progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
            $progressBar->setProgressCharacter('');
            $progressBar->setBarCharacter('▓'); // dark shade character \u2593
        }

        return $progressBar;
    }

    public function setVerbosity(int $level)
    {
        parent::setVerbosity($level);
    }
}
