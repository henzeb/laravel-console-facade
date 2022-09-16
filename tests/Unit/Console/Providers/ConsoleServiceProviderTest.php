<?php

namespace Henzeb\Console\Tests\Unit\Console\Providers;

use Orchestra\Testbench\TestCase;
use Henzeb\Console\Facades\Console;
use Illuminate\Support\Facades\Artisan;
use Henzeb\Console\Providers\ConsoleServiceProvider;

class ConsoleServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
    }

    public function testShouldIsolateConsoleWhenUsingArtisanCall()
    {
        $unit = $this;
        Artisan::command(
            'myApplication',
            function () use ($unit) {
                $unit->assertEquals('myApplication', Console::getInput()->getFirstArgument());

                Artisan::call('myOtherApplication');

                $unit->assertEquals('myApplication', Console::getInput()->getFirstArgument());
            }
        );

        Artisan::command(
            'myOtherApplication',
            function () use ($unit) {
                $unit->assertEquals('myOtherApplication', Console::getInput()->getFirstArgument());
            }
        );

        Artisan::call('myApplication');
    }
}
