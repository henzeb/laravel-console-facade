<?php

namespace Henzeb\Console\Tests\Unit\Console\Stores;

use Closure;
use Henzeb\Console\Stores\OutputStore;
use Illuminate\Console\OutputStyle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class OutputStoreTest extends TestCase
{

    protected function setUp(): void
    {
        Closure::bind(function () {
            OutputStore::$outputs = [];
        }, null, OutputStore::class)();
    }

    private function buildOutput(string $name): OutputStyle
    {
        return new OutputStyle(
            new StringInput($name),
            new ConsoleOutput()
        );
    }

    private function assertFirstArgument(string $expected, OutputStyle $output): void
    {
        Closure::bind(
            function (TestCase $unit, string $expected) {
                $unit->assertEquals(
                    $expected,
                    $this->input->getFirstArgument()
                );
            },
            $output,
            SymfonyStyle::class
        )(
            $this,
            $expected
        );
    }

    public function testPopShouldReturnNullIfNothingInStore(): void
    {
        $this->assertNull(OutputStore::pop());
    }

    public function testHasOutputsShouldReturnFalseWhenEmpty()
    {
        $this->assertFalse(OutputStore::hasOutputs());
    }

    public function testHasOutputsShouldReturnTrueWhenNotEmpty()
    {
        OutputStore::add($this->buildOutput('myApplication'));
        $this->assertTrue(OutputStore::hasOutputs());
    }

    public function testShouldStackOutputs()
    {
        OutputStore::add(
            $this->buildOutput('myApplication')
        );

        OutputStore::add(
            $this->buildOutput('myOtherApplication')
        );

        $this->assertFirstArgument('myOtherApplication', OutputStore::pop());

        $this->assertFirstArgument('myApplication', OutputStore::pop());
    }
}
