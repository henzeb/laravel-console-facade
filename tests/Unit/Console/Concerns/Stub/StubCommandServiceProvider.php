<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns\Stub;

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
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
    }
}
