<?php

namespace Henzeb\Console\Output;

use Illuminate\Console\BufferedConsoleOutput;
use RuntimeException;

class BufferedOutput extends BufferedConsoleOutput
{
    /**
     * @var resource|null
     */
    private $stream = null;

    /**
     * @return resource
     */
    public function getStream()
    {
        if ($this->stream === null) {
            $stream = fopen('php://memory', 'r+');

            if ($stream === false) {
                throw new RuntimeException('Unable to open a stream for ' . static::class);
            }

            $this->stream = $stream;
        }

        return $this->stream;
    }

    protected function doWrite(string $message, bool $newline): void
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
