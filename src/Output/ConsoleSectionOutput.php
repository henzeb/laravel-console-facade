<?php

namespace Henzeb\Console\Output;

use Closure;
use Henzeb\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput as SymfonyConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use const DIRECTORY_SEPARATOR;

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
        OutputInterface $output,
        InputInterface $input,
    )
    {
        $this->input = $input;
        $this->output = $this;

        parent::__construct(
            $stream,
            $sections,
            $output->getVerbosity(),
            $output->isDecorated(),
            $output->getFormatter()
        );
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function createProgressBar(int $max = 0): ProgressBar
    {
        $progressBar = new ProgressBar($this->output, $max);

        if ('\\' !== DIRECTORY_SEPARATOR || 'Hyper' === getenv('TERM_PROGRAM')) {
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

    /**
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

        $messages = is_iterable($message) ? $message : explode(PHP_EOL, $message);


        $stream = $this->getStream();

        //move to beginning of section
        if (!empty($previousContent)) {
            fwrite($stream, chr(27) . "[" . count($previousContent) . "A");
        }

        // clear the cached content
        $this->clearContentCache();

        //overwriting existing lines
        foreach ($messages as $key => $message) {
            if (!empty($message)) {
                $this->write(chr(27) . '[2K' . $message, isset($messages[$key + 1]));
            }
        }

        //clear everything else.
        fwrite($stream, "\x1b[0J");
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

    public function render(callable $message)
    {
        $array = [];
        $streamer = new ConsoleSectionOutput(
            fopen('php://memory', 'rw+'),
            $array,
            $this->getOutput(),
            $this->input
        );

        Closure::fromCallable($message)($streamer);

        $this->replace($streamer->getContent());
    }
}
