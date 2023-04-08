<?php

namespace Henzeb\Console\Output;

use Closure;
use Henzeb\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput as SymfonyConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;

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
        InteractsWithIO::withProgressBar as private parentWithProgressbar;
    }

    protected array $sections = [];
    private ?TailConsoleSectionOutput $tail = null;

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
            $this->sections = &$sections,
            $output->getVerbosity(),
            $output->isDecorated(),
            $output->getFormatter()
        );
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    protected function contentEndsWithNewLine(): bool
    {
        return str_ends_with($this->getContent(), PHP_EOL);
    }

    public function createProgressBar(int $max = 0): ProgressBar
    {
        return (new OutputStyle(self::getInput(), $this->output))
            ->createProgressBar($max);
    }

    public function withProgressBar($totalSteps, Closure $callback)
    {
        return tap($this->parentWithProgressbar($totalSteps, $callback),
            function () {
                if (!$this->contentEndsWithNewLine()) {
                    (function () {
                        $this->content[] = PHP_EOL;
                    })->bindTo($this, SymfonyConsoleSectionOutput::class)();
                }
            }
        );
    }

    public function setVerbosity(int $level)
    {
        parent::setVerbosity($level);
    }

    public function replace(iterable|string $message): void
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

    public function tail(int $maxHeight = 10): TailConsoleSectionOutput
    {
        return ($this->tail ??= new TailConsoleSectionOutput(
            $maxHeight,
            $this->output
        ))->tail($maxHeight);
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

    public function delete(int $lines = null): self
    {
        $this->clear($lines);

        return $this;
    }

    public function newLine($count = 1)
    {
        $this->write(str_repeat(PHP_EOL, $count - 1));
    }
}
