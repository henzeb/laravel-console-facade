<?php

namespace Henzeb\Console\Tests\Unit\Console\Concerns;

use Illuminate\Console\Parser;
use Orchestra\Testbench\TestCase;
use Henzeb\Console\Facades\Console;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\InputDefinition;
use Henzeb\Console\Providers\ConsoleServiceProvider;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Henzeb\Console\Tests\Unit\Console\Concerns\Stub\StubCommandServiceProvider;

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

        Artisan::call('test:test', ['--test' => 'a']);
    }

    public function testAutomatedValidationSucceeds()
    {
        $this->assertEquals(0, Artisan::call('test:test', ['--test' => 'ab']));
    }

    public function testClosureCommand()
    {
        $this->expectException(InvalidArgumentException::class);

        Artisan::call('test:closure', ['--test' => 'a']);
    }
}
