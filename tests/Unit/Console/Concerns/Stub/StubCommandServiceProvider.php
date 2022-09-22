<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns\Stub;

use Henzeb\Console\Facades\Console;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Artisan;

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
                    '--test'=>'size:2'
                ]);
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
