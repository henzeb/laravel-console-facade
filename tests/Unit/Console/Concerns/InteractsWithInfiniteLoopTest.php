<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use RuntimeException;
use PHPUnit\Framework\Assert;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\App;
use Henzeb\Console\Concerns\InteractsWithInfiniteLoop;


class InteractsWithInfiniteLoopTest extends TestCase
{
    public function testShouldBlockWhenNotInUnitTest()
    {
        App::partialMock()->shouldReceive('runningUnitTests')->andReturn(false);

        $infiniteLoops = new class {
            use InteractsWithInfiniteLoop {
                infiniteLoop as public;
            }
        };

        $this->assertTrue($infiniteLoops->infiniteLoop());
        $this->assertTrue($infiniteLoops->infiniteLoop());
        $this->assertTrue($infiniteLoops->infiniteLoop());

        $this->expectException(RuntimeException::class);
        $infiniteLoops->watchShouldLoop(1);
    }

    public function providesLoopCounts()
    {
        return [
            '1' => [1],
            '5' => [5],
            '100' => [100],
            '1000' => [1000],
        ];
    }

    /**
     * @param int $loops
     * @return void
     *
     * @dataProvider providesLoopcounts
     */
    public function testShouldLoopGivenTimes(int $loops)
    {
        $infiniteLoops = new class {
            use InteractsWithInfiniteLoop {
                infiniteLoop as public;
            }
            public function shouldSleepWith(int $seconds) {
                Assert::assertEquals(2, $seconds);
            }
        };

        $infiniteLoops->watchShouldLoop($loops, 2);

        for ($i = 0; $i < $loops; $i++) {
            $this->assertTrue($infiniteLoops->infiniteLoop());
        }

        for ($i = 0; $i < $loops; $i++) {
            $this->assertFalse($infiniteLoops->infiniteLoop());
        }
    }
}
