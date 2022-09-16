<?php

namespace Henzeb\Console\Tests\Unit\Console\Providers;

use Closure;
use PHPUnit\Framework\TestCase;
use Illuminate\Console\OutputStyle;
use Henzeb\Console\Stores\OutputStore;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

class OutputStoreTest extends TestCase
{
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
