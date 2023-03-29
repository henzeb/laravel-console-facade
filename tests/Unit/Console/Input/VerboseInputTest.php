<?php

namespace Henzeb\Console\Tests\Unit\Console\Input;

use Henzeb\Console\Input\VerboseInput;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Mockery;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class VerboseInputTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class];
    }

    public function testParse(): void
    {
        $stringInput = Mockery::mock(StringInput::class);
        $stringInput->shouldAllowMockingProtectedMethods()
            ->expects('parse')->once();
        $input = new VerboseInput(
            $stringInput,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
        );

        $input->bind(new InputDefinition([]));
    }

    public function testFirstArgument(): void
    {
        $stringInput = Mockery::mock(StringInput::class);
        $stringInput->expects('getFirstArgument')->andReturn('string');
        $stringInput->expects('getFirstArgument')->andReturnNull();

        $input = new VerboseInput(
            $stringInput,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
        );

        $this->assertEquals('string', $input->getFirstArgument());

        $this->assertNull($input->getFirstArgument());
    }

    public function testHasParameterOption(): void
    {
        $stringInput = Mockery::mock(StringInput::class);
        $stringInput->expects('hasParameterOption')->with('option', false)->andReturnTrue();
        $stringInput->expects('hasParameterOption')->with('missingOption', true)->andReturnFalse();

        $input = new VerboseInput(
            $stringInput,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
        );

        $this->assertTrue($input->hasParameterOption('option'));

        $this->assertFalse($input->hasParameterOption('missingOption', true));
    }

    public function testGetParameterOption(): void
    {
        $stringInput = Mockery::mock(StringInput::class);
        $stringInput->expects('getParameterOption')->with('option', null, false)->andReturn('aValue');
        $stringInput->expects('getParameterOption')->with('missingOption', null, true)->andReturnNull();
        $stringInput->expects('getParameterOption')->with('hasDefault', 'aDefaultValue', false)->andReturn('aDefaultValue');

        $input = new VerboseInput(
            $stringInput,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
        );

        $this->assertEquals('aValue', $input->getParameterOption('option'));

        $this->assertNull($input->getParameterOption('missingOption', null, true));

        $this->assertEquals('aDefaultValue', $input->getParameterOption('hasDefault', 'aDefaultValue'));
    }

    public function testSetOption(): void
    {
        $stringInput = new StringInput('');
        $stringInput->bind(
            new InputDefinition([
                new InputOption('test')
            ])
        );
        $verboseInput = Mockery::mock(VerboseInput::class, [
            $stringInput,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
            ConsoleOutputInterface::VERBOSITY_DEBUG
        ]);

        $verboseInput->expects('isInteractive')->andReturnFalse();
        $verboseInput->expects('isInteractive')->andReturnTrue();

        $verboseInput = $verboseInput->makePartial();
        $verboseInput->setOption('test', true);

        $this->assertFalse($stringInput->getOption('test'));
        $this->assertEquals($stringInput->getOption('test'), $verboseInput->getOption('test'));

        $verboseInput->setOption('test', true);
        $this->assertTrue($stringInput->getOption('test'));
        $this->assertEquals($stringInput->getOption('test'), $verboseInput->getOption('test'));
    }

    public function testSetArgument(): void
    {
        $stringInput = new StringInput('');
        $stringInput->bind(
            new InputDefinition([
                new InputArgument('test')
            ])
        );
        $verboseInput = Mockery::mock(VerboseInput::class, [
            $stringInput,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
            ConsoleOutputInterface::VERBOSITY_DEBUG
        ]);

        $verboseInput->expects('isInteractive')->andReturnFalse();
        $verboseInput->expects('isInteractive')->andReturnTrue();

        $verboseInput = $verboseInput->makePartial();
        $verboseInput->setArgument('test', 'given');

        $this->assertNull($stringInput->getArgument('test'));
        $this->assertEquals($stringInput->getArgument('test'), $verboseInput->getArgument('test'));

        $verboseInput->setArgument('test', 'given');
        $this->assertEquals('given', $stringInput->getArgument('test'));
        $this->assertEquals($stringInput->getArgument('test'), $verboseInput->getArgument('test'));
    }


    public function testSetStream(): void
    {
        $stringInput = new StringInput('');

        $verboseInput = Mockery::mock(VerboseInput::class, [
            $stringInput,
            ConsoleOutputInterface::VERBOSITY_DEBUG,
            ConsoleOutputInterface::VERBOSITY_DEBUG
        ]);

        $verboseInput->expects('isInteractive')->andReturnFalse();
        $verboseInput->expects('isInteractive')->andReturnTrue();

        $verboseInput = $verboseInput->makePartial();

        $resource = fopen('php://memory', 'rw+');
        $verboseInput->setStream($resource);
        $this->assertNull($verboseInput->getStream());

        $verboseInput->setStream($resource);
        $this->assertSame($resource, $verboseInput->getStream());

        fclose($resource);
    }

    public function providesInteractiveTestcases(): array
    {
        return [
            'normal-normal' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'output' => ConsoleOutputInterface::VERBOSITY_NORMAL
            ],
            'normal-verbose' => [
                'expected' => false,
                'input' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_NORMAL
            ],
            'normal-very-verbose' => [
                'expected' => false,
                'input' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_NORMAL
            ],
            'normal-debug' => [
                'expected' => false,
                'input' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'output' => ConsoleOutputInterface::VERBOSITY_NORMAL
            ],

            'verbose-normal' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'output' => ConsoleOutputInterface::VERBOSITY_VERBOSE
            ],
            'verbose-verbose' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_VERBOSE
            ],
            'verbose-very-verbose' => [
                'expected' => false,
                'input' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_VERBOSE
            ],
            'verbose-debug' => [
                'expected' => false,
                'input' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'output' => ConsoleOutputInterface::VERBOSITY_VERBOSE
            ],

            'very-verbose-normal' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'output' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE
            ],
            'very-verbose-verbose' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE
            ],
            'very-verbose-very-verbose' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE
            ],
            'very-verbose-debug' => [
                'expected' => false,
                'input' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'output' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE
            ],

            'debug-normal' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_NORMAL,
                'output' => ConsoleOutputInterface::VERBOSITY_DEBUG
            ],
            'debug-verbose' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_DEBUG
            ],
            'debug-very-verbose' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_VERY_VERBOSE,
                'output' => ConsoleOutputInterface::VERBOSITY_DEBUG
            ],
            'debug-debug' => [
                'expected' => true,
                'input' => ConsoleOutputInterface::VERBOSITY_DEBUG,
                'output' => ConsoleOutputInterface::VERBOSITY_DEBUG
            ]
        ];
    }

    /**
     * @param bool $expected
     * @param int $inputVerbosity
     * @param int $outputVerbosity
     * @return void
     *
     * @dataProvider providesInteractiveTestcases
     */
    public function testIsInteractive(
        bool $expected,
        int  $inputVerbosity,
        int  $outputVerbosity
    )
    {
        $input = new VerboseInput(
            new ArgvInput(),
            $inputVerbosity,
            $outputVerbosity
        );

        $this->assertEquals($expected, $input->isInteractive());
    }

    public function testIsInteractiveByInput()
    {
        $stringInput = new StringInput('');
        
        $verboseInput = new VerboseInput($stringInput, ConsoleOutputInterface::VERBOSITY_NORMAL, ConsoleOutputInterface::VERBOSITY_NORMAL);

        $this->assertTrue($verboseInput->isInteractive());

        $stringInput->setInteractive(false);
        $this->assertFalse($verboseInput->isInteractive());
    }

}
