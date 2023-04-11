<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use Henzeb\Console\Facades\Console;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Henzeb\Console\Tests\Unit\Console\Concerns\Stub\StubCommandServiceProvider;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\Parser;
use Illuminate\Support\Facades\Artisan;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ValidatesInputTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [ConsoleServiceProvider::class, StubCommandServiceProvider::class];
    }


    private function setParameters(string $definition, string $args)
    {
        [$name, $arg, $opt] = Parser::parse($definition);

        $input = new StringInput($args);
        $input->bind(
            new InputDefinition(
                array_merge($arg, $opt)
            )
        );

        Console::setOutput(
            new OutputStyle(
                $input,
                new ConsoleOutput()
            )
        );
    }

    public function testOptionShouldValidate()
    {
        $this->setParameters('{opt_value} {--opt_value=}', 'wrong --opt_value correct');

        Console::validateWith(
            [
                '--opt_value' => 'size:7'
            ]
        );

        $this->assertTrue(Console::argumentGiven('opt_value'));

        $this->assertTrue(Console::optionGiven('opt_value'));

        Console::validate();
    }

    public function testOptionShouldFailValidation()
    {
        $this->setParameters('{opt_value} {--opt_value=}', 'correct --opt_value wrong');

        Console::validateWith(
            [
                '--opt_value' => 'size:7'
            ]
        );

        $this->assertTrue(Console::optionGiven('opt_value'));
        $this->assertTrue(Console::argumentGiven('opt_value'));

        $this->expectException(InvalidArgumentException::class);

        try {
            Console::validate();
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('--opt_value', $exception->getMessage());
            $this->assertStringNotContainsString('The opt_value', $exception->getMessage());
            throw $exception;
        }
    }

    public function testOptionShouldNotValidateIfNotGiven()
    {
        $this->setParameters('{--opt_value=?}', '');

        Console::validateWith(
            [
                'opt_value' => 'size:1'
            ]
        );

        $this->assertFalse(Console::optionGiven('opt_value'));

        Console::validate();
    }

    public function testArgumentShouldValidate()
    {
        $this->setParameters('{arg_required}, {--arg_required=}', 'correct --arg_required wrong');

        Console::validateWith(
            [
                'arg_required' => 'size:7'
            ]
        );

        $this->assertTrue(Console::optionGiven('arg_required'));
        $this->assertTrue(Console::argumentGiven('arg_required'));

        Console::validate();
    }


    public function testArgumenthouldFailValidation()
    {
        $this->setParameters('{arg_required}, {--arg_required=}', 'wrong --arg_required correct');

        Console::validateWith(
            [
                'arg_required' => 'size:7'
            ]
        );

        $this->assertTrue(Console::optionGiven('arg_required'));
        $this->assertTrue(Console::argumentGiven('arg_required'));

        $this->expectException(InvalidArgumentException::class);

        try {
            Console::validate();
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('The arg required', $exception->getMessage());
            $this->assertStringNotContainsString('--arg_required', $exception->getMessage());
            throw $exception;
        }
    }

    public function testValidateWithAttributeRenaming()
    {
        $this->setParameters('{--arg_required=}', '--arg_required fail');

        Console::validateWith(
            [
                '--arg_required' => 'size:7'
            ],
            [],
            [
                '--arg_required' => 'required arg'
            ]
        );

        $this->assertTrue(Console::optionGiven('arg_required'));

        $this->expectException(InvalidArgumentException::class);

        try {
            Console::validate();
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('The required arg', $exception->getMessage());
            throw $exception;
        }
    }

    public function testValidateWithValueNames()
    {
        $this->setParameters('{--arg1=} {--arg2=}', '--arg1 aa');

        Console::validateWith(
            [
                '--arg2' => 'required_if:--arg1,aa'
            ],
            [],
            [],
            [
                '--arg1' => [
                    'aa' => 'double-a'
                ]
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        try {
            Console::validate();
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('--arg1 is double-a', $exception->getMessage());
            throw $exception;
        }
    }

    public function testArgumentShouldNotValidateIfNotGiven()
    {
        $this->setParameters('{arg_required} {--arg_required}', '--arg_required');

        Console::validateWith(
            [
                'arg_required' => 'size:3'
            ]
        );

        $this->assertTrue(Console::optionGiven('arg_required'));
        $this->assertFalse(Console::argumentGiven('arg_required'));

        Console::validate();
    }

    public function testAutomatedValidationFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/The --test .*must be 3 characters./");

        Artisan::call('test:test', ['--test' => 'a']);
    }

    public function testAutomatedValidationSucceeds()
    {
        $this->assertEquals(0, Artisan::call('test:test', ['--test' => 'abc']));
    }

    public function testClosureCommand()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/The --test .*must be 2 characters./");

        Artisan::call('test:closure', ['--test' => 'a']);
    }

    public function testSecondClosureCommand()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/The --test .*must be 4 characters./");

        Artisan::call('test:second-closure', ['--test' => 'a']);
    }

    public function testClosureWithoutValidationCommand()
    {
        $this->expectNotToPerformAssertions();

        Artisan::call('test:no-validation-rules', ['--test' => 'a']);
    }

    public function testBeforeValidationCallback()
    {
        $actual = false;

        Console::beforeValidation(
            function () use (&$actual) {
                $actual = true;
            }
        );
        Console::validateWith([
            '--test' => 'string'
        ]);

        Console::validate();

        $this->assertTrue($actual);

        $actual = false;
        
        Console::setCommandForValidation('test');
        Console::validate();

        $this->assertFalse($actual);

    }
}
