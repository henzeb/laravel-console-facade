<?php

namespace Henzeb\Console\Output;

use RuntimeException;
use Symfony\Component\Console\Output\NullOutput as SymfonyNullOutput;

class NullOutput extends SymfonyNullOutput
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
            $stream = fopen($this->isWindows() ? 'NUL' : '/dev/null', 'rw+');

            if ($stream === false) {
                throw new RuntimeException('Unable to open a stream for ' . static::class);
            }

            $this->stream = $stream;
        }

        return $this->stream;
    }

    protected function isWindows(): bool
    {
        return is_readable('NUL');
    }
}
