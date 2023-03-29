<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Conditionable;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class InteractsWithIOTest extends TestCase
{
    use Conditionable;

    protected function getPackageProviders($app)
    {
        return [
            ConsoleServiceProvider::class
        ];
    }

    public function providesOldVersions()
    {
        return [
            '8.83.23' => ['8.83.23'],
            '9.20.23' => ['9.20.23'],
        ];
    }

    public function providesNewVersions()
    {
        return [
            '9.21.0' => ['9.21.0'],
            '9.23.0' => ['9.23.0'],
            '10.23.0' => ['10.23.0'],
        ];
    }

    /**
     * @param string $version
     * @return void
     *
     * @dataProvider providesOldVersions
     */
    public function testFactoryShouldFailWhenVersionIsIncorrect(string $version): void
    {
        App::partialMock()->shouldReceive('version')->andReturn($version);

        $this->expectException(RuntimeException::class);

        $output = new ConsoleOutput();

        $output->components();
    }

    /**
     * @param string $version
     * @return void
     *
     * @dataProvider providesNewVersions
     */
    public function testFactoryShouldSucceedWhenVersionIsIncorrect(string $version): void
    {
        App::partialMock()->shouldReceive('version')->andReturn($version);

        $output = new ConsoleOutput();

        if (!class_exists('Illuminate\Console\View\Components\Factory')) {
            $this->expectException(BindingResolutionException::class);
            $output->components();
        } else {
            $this->assertInstanceOf(Factory::class, $output->components());
        }
    }
}
