<?php

namespace Henzeb\Console\Providers;

use Illuminate\Console\OutputStyle;
use Henzeb\Console\Facades\Console;
use Illuminate\Support\Facades\Event;
use Henzeb\Console\Stores\OutputStore;
use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Events\CommandFinished;


class ConsoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->afterResolving(
            OutputStyle::class,
            function (OutputStyle $outputStyle) {

                if ($this->app->runningUnitTests()) {
                    Console::partialMock();
                }

                if (Console::getOutput()) {
                    OutputStore::add(Console::getOutput());
                }

                Console::setOutput($outputStyle);
            }
        );

        Event::listen(
            CommandFinished::class,
            function () {
                if (OutputStore::hasOutputs()) {
                    Console::setOutput(
                        OutputStore::pop()
                    );
                }
            }
        );
    }
}
