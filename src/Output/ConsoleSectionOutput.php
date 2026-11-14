<?php

namespace Henzeb\Console\Output;

use Closure;
use Henzeb\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use RuntimeException;
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

    /**
     * @var array<SymfonyConsoleSectionOutput>
     */
    protected array $sections = [];
    private ?TailConsoleSectionOutput $tail = null;
    private ?OutputInterface $forwardTo = null;

    /**
     * @param resource|null $stream
     * @param array<SymfonyConsoleSectionOutput> $sections
     */
    public function __construct(
        private string  $name,
        mixed           $stream,
        array           &$sections,
        OutputInterface $output,
        InputInterface  $input,
    ) {
        $this->input = $input;

        if (!is_resource($stream)) {
            $stream = fopen('php://memory', 'rw+');

            if ($stream === false) {
                throw new RuntimeException('Unable to open a memory stream');
            }

            $this->forwardTo = $output;
        }

        $this->sections = &$sections;

        parent::__construct(
            $stream,
            $sections,
            $output->getVerbosity(),
            $output->isDecorated(),
            $output->getFormatter()
        );

        $this->output = new OutputStyle($input, $this);
    }

    public function name(): string
    {
        return $this->name;
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
        return $this->getOutput()->createProgressBar($max);
    }

    /**
     * @param iterable<mixed>|int $totalSteps
     * @return mixed
     */
    public function withProgressBar($totalSteps, Closure $callback)
    {
        return tap(
            $this->parentWithProgressbar($totalSteps, $callback),
            function () {
                if (!$this->contentEndsWithNewLine()) {
                    Closure::bind(
                        function () {
                            $this->content[] = PHP_EOL;
                        },
                        $this,
                        SymfonyConsoleSectionOutput::class
                    )();
                }
            }
        );
    }

    public function setVerbosity(int $level): void
    {
        parent::setVerbosity($level);
    }

    /**
     * @param iterable<string>|string $message
     */
    public function replace(iterable|string $message): void
    {
        if (!$this->isDecorated()) {
            return;
        }

        $previousContent = array_filter(
            explode(PHP_EOL, $this->getContent())
        );

        $messages = array_values(is_iterable($message) ? iterator_to_array($message) : explode(PHP_EOL, $message));


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
            $this->name(),
            $this->forwardTo ? null : $this->getStream(),
            $this->sections,
            $this->forwardTo ?? $this->output->getOutput(),
            $this->getInput()
        ))->tail($maxHeight);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        if ($this->forwardTo) {
            $this->forwardTo->write($message, $newline, OutputInterface::OUTPUT_RAW);

            return;
        }

        parent::doWrite($message, $newline);
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
     * @return void
     */
    public function render(callable $message)
    {
        $array = [];
        $stream = fopen('php://memory', 'rw+');

        if ($stream === false) {
            throw new RuntimeException('Unable to open a memory stream');
        }

        $streamer = new ConsoleSectionOutput(
            $this->name(),
            $stream,
            $array,
            $this->getOutput(),
            $this->input
        );

        Closure::fromCallable($message)($streamer);

        $this->replace($streamer->getContent());
    }

    public function clear(?int $lines = null): void
    {
        if ($lines === null) {
            $this->write("\033[0m");
        }

        parent::clear($lines);
    }

    public function delete(?int $lines = null): self
    {
        $this->clear($lines);

        return $this;
    }

    /**
     * @param int $count
     * @return void
     */
    public function newLine($count = 1)
    {
        $this->write(str_repeat(PHP_EOL, $count - 1), true);
    }
}
