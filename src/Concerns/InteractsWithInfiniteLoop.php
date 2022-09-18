<?php

namespace Henzeb\Console\Concerns;

use RuntimeException;
use Illuminate\Support\Facades\App;

trait InteractsWithInfiniteLoop
{
    private int $loops = 0;

    private function infiniteLoop(): bool
    {
        if (!App::runningUnitTests()) {
            return true;
        }

        if ($this->loops <= 0) {
            return false;
        }

        $this->loops--;

        return true;
    }

    public function watchShouldLoop(int $times, int $sleep = 1)
    {
        if (!App::runningUnitTests()) {
            throw new RuntimeException('Not running inside a Unit Test');
        }
        $this->shouldSleepWith($sleep);
        $this->loops = $times;
    }
}
