<?php

namespace Henzeb\Console\Concerns;

use Illuminate\Console\Concerns\InteractsWithIO as IlluminateInteractsWithIO;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Facades\App;
use RuntimeException;

trait InteractsWithIO
{
    use IlluminateInteractsWithIO,
        HandlesVerbosityOutput;

    public function components(): Factory
    {
        if (version_compare(App::version(), '9.21.0', '>=')) {
            return resolve(Factory::class, ['output' => $this->getOutput()]);
        }

        throw new RuntimeException('This version of Laravel does not support components.');
    }
}
