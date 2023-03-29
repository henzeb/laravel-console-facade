<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;


use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Illuminate\Console\OutputStyle;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

class InteractsWithArgumentsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ConsoleServiceProvider::class
        ];
    }

    public function testShouldMergeArguments(): void
    {
        $output = new ConsoleOutput();
        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );

        $inputDefinition->addArgument(
            new InputArgument('anArgument', InputArgument::OPTIONAL, '', 'actual')
        );

        $inputDefinition->addArgument(
            new InputArgument('anOtherArgument', InputArgument::OPTIONAL, '', 'expected')
        );

        $output->mergeArguments([
            'anArgument' => 'expected'
        ]);

        $this->assertEquals(
            [
                'anArgument' => 'expected',
                'anOtherArgument' => 'expected',
            ],
            $output->arguments()
        );

        $this->assertEquals('expected', $output->argument('anArgument'));
    }

    public function testShouldDetectIfArgumentWasGiven()
    {
        $output = new ConsoleOutput();

        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );
        $inputDefinition->addArgument(
            new InputArgument('anArgument', InputArgument::OPTIONAL, '', 'actual')
        );

        $output->setOutput(
            new OutputStyle(
                new ArrayInput(['anArgument' => 'given'], $inputDefinition),
                new SymfonyConsoleOutput()
            )
        );

        $this->assertTrue($output->argumentGiven('anArgument'));
    }

    public function testShouldDetectIfArgumentWasGivenWithMerge()
    {
        $output = new ConsoleOutput();

        $inputDefinition = new InputDefinition();
        $output->getInput()->bind(
            $inputDefinition
        );
        $inputDefinition->addArgument(
            new InputArgument('anArgument', InputArgument::OPTIONAL, '', 'actual')
        );

        $this->assertFalse($output->argumentGiven('anArgument'));
        $output->mergeArguments([
            'anArgument' => false
        ]);

        $this->assertTrue($output->argumentGiven('anArgument'));
    }
}
