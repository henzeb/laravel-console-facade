<?php

namespace Henzeb\Console\Output;

use Symfony\Component\Console\Output\NullOutput as SymfonyNullOutput;

class NullOutput extends SymfonyNullOutput
{

    /**
     * @var false|resource
     */
    private $stream = null;

    public function getStream()
    {
        return $this->stream ??= fopen($this->isWindows() ? 'NUL' : '/dev/null', 'rw+');
    }

    protected function isWindows(): bool
    {
        return is_readable('NUL');
    }
}
