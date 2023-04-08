<?php

namespace Henzeb\Console\Output;

use Symfony\Component\Console\Output\BufferedOutput as SymfonyBufferedOutput;

class BufferedOutput extends SymfonyBufferedOutput
{
    /**
     * @var false|resource
     */
    private $stream = null;

    public function getStream()
    {
        return $this->stream ??= fopen('php://memory', 'r+');
    }

    protected function doWrite(string $message, bool $newline)
    {
        fwrite($this->getStream(), $message . ($newline ? PHP_EOL : ''));
        fflush($this->getStream());
    }

    public function fetch(): string
    {
        if (!is_resource($this->stream)) {
            return '';
        }

        rewind($this->getStream());
        
        return tap(
            stream_get_contents($this->getStream()),
            function () {
                rewind($this->getStream());
                ftruncate($this->getStream(), 0);
            }
        );
    }

    public function __destruct()
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
    }
}
