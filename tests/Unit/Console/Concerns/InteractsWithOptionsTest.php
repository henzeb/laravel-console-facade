<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;


use Orchestra\Testbench\TestCase;
use Illuminate\Console\OutputStyle;
use Henzeb\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

class InteractsWithOptionsTest extends TestCase
{
    public function testShouldMergeOptions(): void
    {
        $output = new ConsoleOutput();
        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );
        $inputDefinition->addOption(
            new InputOption('anOption', '', InputOption::VALUE_REQUIRED, '', 'actual')
        );

        $inputDefinition->addOption(
            new InputOption('anOtherOption', '', InputOption::VALUE_REQUIRED, '', 'expected')
        );

        $output->mergeOptions([
            'anOption' => 'expected'
        ]);

        $this->assertEquals(
            [
                'anOption' => 'expected',
                'anOtherOption' => 'expected',
            ],
            $output->options()
        );

        $this->assertEquals('expected', $output->option('anOption'));
    }

    public function testShouldDetectIfOptionWasGiven()
    {
        $output = new ConsoleOutput();

        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );
        $inputDefinition->addOption(
            new InputOption('anOption', '', InputOption::VALUE_REQUIRED, '', 'actual')
        );

        $output->setOutput(
            new OutputStyle(
                new ArrayInput(['--anOption' => 'given'], $inputDefinition),
                new SymfonyConsoleOutput()
            )
        );

        $this->assertTrue($output->optionGiven('anOption'));
    }

    public function testShouldDetectIfOptionWasGivenWithMerge()
    {
        $output = new ConsoleOutput();

        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );
        $inputDefinition->addOption(
            new InputOption('anOption', '', InputOption::VALUE_REQUIRED, '', 'actual')
        );

        $this->assertFalse($output->optionGiven('anOption'));
        $output->mergeOptions(['anOption' => false]);
        $this->assertTrue($output->optionGiven('anOption'));
    }
}
