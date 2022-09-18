<?php

namespace Henzeb\Console\Concerns;

use RuntimeException;
use Illuminate\Support\Facades\App;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Console\Concerns\InteractsWithIO as IlluminateInteractsWithIO;

trait InteractsWithIO
{
    use IlluminateInteractsWithIO;

    public function components(): Factory
    {
        if(version_compare(App::version(), '9.21.0', '>=')) {
            return resolve(Factory::class, ['output' => $this->getOutput()]);
        }

        throw new RuntimeException('This version of Laravel does not support components.');
    }
}
