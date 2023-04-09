<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns\Stub;

use Henzeb\Console\Facades\Console;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class StubCommandServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->commands(
            TestValidationCommand::class,
        );

        Artisan::command(
            'test:closure {--test=}',
            function () {
                Console::validateWith([
                    '--test' => 'size:2'
                ]);
                Console::validate();
            }
        );

        Artisan::command(
            'test:second-closure {--test=}',
            function () {
                Console::validateWith([
                    '--test' => 'size:4'
                ]);
                Console::validate();
            }
        );

        Artisan::command(
            'test:no-validation-rules {--test=}',
            function () {
                Console::validate();
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
    }
}
