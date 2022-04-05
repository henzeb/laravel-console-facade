<?php

namespace Henzeb\Console\Providers;

use Illuminate\Console\OutputStyle;
use Henzeb\Console\Facades\Console;
use Illuminate\Support\ServiceProvider;


class ConsoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->afterResolving(OutputStyle::class, function (OutputStyle $outputStyle) {
            if (!Console::isMock()) {
                Console::partialMock();
            }
            Console::setOutput($outputStyle);

        });
    }
}
