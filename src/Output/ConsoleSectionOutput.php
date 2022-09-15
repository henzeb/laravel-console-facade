<?php

namespace Henzeb\Console\Output;

use Closure;
use Generator;

use Illuminate\Support\Traits\Macroable;
use Henzeb\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Traits\Conditionable;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput as SymfonyConsoleSectionOutput;

class ConsoleSectionOutput extends SymfonyConsoleSectionOutput
{
    use Conditionable,
        Macroable,
        InteractsWithIO {
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
        InputInterface $input
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


    private function clearContentCache(): void
    {
        Closure::bind(
            function () {
                $this->content = [];
                $this->lines = 0;
            },
            $this,
            SymfonyConsoleSectionOutput::class
        )();
    }

    /**
     *
     * @param $message iterable|string
     * @return void
     */
    public function replace($message)
    {
        if (!$this->isDecorated()) {
            return;
        }

        $previousContent = array_filter(
            explode(PHP_EOL, $this->getContent())
        );

        $messages = $this->toGenerator(
            is_iterable($message) ? $message : explode(PHP_EOL, $message)
        );

        $stream = $this->getStream();

        //move to beginning of section
        if (!empty($previousContent)) {
            fwrite($stream, chr(27) . "[" . count($previousContent) . "A");
        }

        // clear the cached content
        $this->clearContentCache();

        //overwriting existing lines
        while (true) {
            $message = $messages->current();
            $messages->next();
            if (!empty($message)) {
                $this->write(chr(27) . '[2K' . $message, $messages->valid());
            }

            if (!$messages->valid()) {
                break;
            }
        }

        //clear everything else.
        fwrite($stream, "\x1b[0J");
    }

    private function toGenerator(iterable $iterable): Generator
    {
        foreach ($iterable as $key => $value) {
            yield $key => $value;
        }
    }

    public function render(callable $message)
    {
        $array = [];
        $streamer = new ConsoleSectionOutput(
            fopen('php://memory', 'rw+'),
            $array,
            $this->getVerbosity(),
            $this->isDecorated(),
            $this->getFormatter(),
            $this->input
        );

        $message($streamer);

        $this->replace($streamer->getContent());
    }
}
