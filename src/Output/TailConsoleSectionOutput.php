<?php

namespace Henzeb\Console\Output;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TailConsoleSectionOutput extends ConsoleSectionOutput
{
    private bool $allowWriteTail = true;

    public function __construct(
        private int     $maxheight,
        string          $name,
        mixed           $stream,
        array           &$sections,
        OutputInterface $section,
        InputInterface  $input,
    )
    {
        parent::__construct(
            $name,
            $stream,
            $sections,
            $section,
            $input
        );
    }

    public function setMaxHeight(int $maxHeight): void
    {
        $this->maxheight = $maxHeight;
    }

    public function getMaxHeight(): int
    {
        return $this->maxheight;
    }

    public function tail(int $maxHeight = 10): TailConsoleSectionOutput
    {
        $this->setMaxHeight($maxHeight);
        return $this;
    }

    protected function doWrite(string $message, bool $newline)
    {
        if ($this->allowWriteTail && $this->contentEndsWithNewLine()) {
            $this->writeTail($message, $newline);
            $this->allowWriteTail = true;
            return;
        }

        parent::doWrite($message, $newline);

    }

    private function getContentAsArray(string $message): array
    {
        return explode(PHP_EOL, $this->getContent() . $message);
    }

    private function writeTail(string $message = '', bool $newline = false): void
    {
        $content = array_slice(
            $this->getContentAsArray($message),
            -$this->getMaxHeight(),
            $this->getMaxHeight()
        );

        $this->allowWriteTail = false;

        $this->replace(
            implode(
                PHP_EOL, $content
            ) . ($newline ? PHP_EOL : ''),
            false
        );
    }
}
