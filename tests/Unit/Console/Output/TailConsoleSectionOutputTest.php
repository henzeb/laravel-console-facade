<?php

namespace Henzeb\Console\Tests\Unit\Console\Output;


use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\BufferedOutput;
use Henzeb\Console\Output\TailConsoleSectionOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Console\OutputStyle;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\StringInput;

class TailConsoleSectionOutputTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ConsoleServiceProvider::class
        ];
    }

    public static function providesConstructTestcases(): array
    {
        return [
            [1, true, 10],
            [32, false, 8],
        ];
    }

    /**
     * @return void
     * @dataProvider providesConstructTestcases
     */

    public function testConstruct(int $verbosity, bool $decorated, int $getMaxHeight)
    {
        $section = Console::section('section');
        $section->setVerbosity($verbosity);
        $section->setDecorated($verbosity);
        $tail = $section->tail($getMaxHeight);

        $this->assertEquals($getMaxHeight, $tail->getMaxHeight());
        $this->assertSame($section->getInput(), $tail->getInput());
        $this->assertSame($section->getStream(), $tail->getStream());
        $this->assertSame($section->getFormatter(), $tail->getFormatter());

        $this->assertEquals($section->isDecorated(), $tail->isDecorated());
        $this->assertEquals($section->getVerbosity(), $tail->getVerbosity());
    }

    public function testTailDefaultLines()
    {
        $tail = $this->mock(TailConsoleSectionOutput::class)->makePartial();
        $tail->setMaxHeight(10);
        $this->assertEquals(10, $tail->tail()->getMaxHeight());
    }

    public function testScrolling()
    {
        $buffer = new BufferedOutput();
        $buffer->setDecorated(true);

        Console::setOutput(new OutputStyle(new StringInput(''), $buffer));

        $tail = Console::tail(2);
        $tail->setDecorated(true);


        if (version_compare($this->getSymfonyConsoleVersion(), 'v6.2.0') >= 0) {
            $tail->write('line');
            $tail->write(' one', true);

            $this->assertEquals("line\n\e[1A\e[0Jline one\n", $buffer->fetch());
            $tail->writeln('second line');
            $this->assertEquals("\e[1A\e[2Kline one\n\e[2Ksecond line\n\e[0J", $buffer->fetch());


        } else {
            $tail->write('line');
            $tail->write(' one', true);


            $this->assertEquals("line\n\e[1A\e[2Kline\n\e[2K one\n\e[0J", $buffer->fetch());
            $tail->writeln('second line');

            $this->assertEquals("\e[2A\e[2K\e[2K one\n\e[2Ksecond line\n\e[0J", $buffer->fetch());
        }
        $tail->write('third line', true);
        $this->assertEquals("\e[2A\e[2K\e[2Ksecond line\n\e[2Kthird line\n\e[0J", $buffer->fetch());
    }

    private function getSymfonyConsoleVersion(): string
    {
        $composerLock = json_decode(file_get_contents('composer.lock'), true);

        $laravelFramework = collect($composerLock['packages'])->first(function ($package) {
            return $package['name'] === 'symfony/console';
        });

        return $laravelFramework['version'];
    }
}
