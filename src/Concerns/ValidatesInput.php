<?php

namespace Henzeb\Console\Concerns;

use Closure;
use Illuminate\Contracts\Validation\Validator as ValidatorInstance;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputDefinition;

trait ValidatesInput
{
    private array $rules = [];
    private array $messages = [];
    private array $attributes = [];
    private array $valueNames = [];
    private array $beforeValidation = [];
    private string $command = 'default';

    public function setCommandForValidation(string $command): void
    {
        $this->command = $command;
    }

    private function getCommandForValidation(): string
    {
        return $this->command;
    }

    private function getDefinition(): InputDefinition
    {
        return Closure::bind(
            function () {
                return $this->definition;
            },
            $this->getInput(),
            Input::class
        )();
    }

    public function validateWith(array $rules, array $messages = [], array $attributes = [], array $valueNames = []): void
    {
        $command = $this->getCommandForValidation();

        $this->rules[$command] = $rules;
        $this->messages[$command] = $messages;
        $this->attributes[$command] = $attributes;
        $this->valueNames[$command] = $valueNames;
    }

    public function beforeValidation(callable $beforeValidation): void
    {
        $this->beforeValidation[$this->getCommandForValidation()] = $beforeValidation;
    }

    private function getData(): array
    {
        return array_merge(
            collect($this->arguments())
                ->filter(
                    fn($a, $b) => $this->argumentGiven($b)
                )
                ->toArray(),

            collect($this->options())
                ->filter(fn($value, $name) => $this->optionGiven($name))
                ->mapWithKeys(
                    fn($value, $name) => ['--' . ltrim($name, '-') => $value]
                )->toArray()
        );
    }

    public function shouldValidate(): bool
    {
        return !empty($this->rules[$this->getCommandForValidation()]);
    }

    /**
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->shouldValidate()) {
            $command = $this->getCommandForValidation();
            $validator = Validator::make(
                $this->getData(),
                $this->rules[$command] ?? [],
                $this->messages[$command] ?? [],
                $this->attributes[$command] ?? [],
            );

            $validator->setValueNames($this->valueNames[$command] ?? []);

            $this->executeBeforeValidation($command, $validator);

            if ($validator->fails()) {
                $this->throwNiceException($validator->getMessageBag());
            }
        }
    }

    private function throwNiceException(MessageBag $messages): void
    {
        throw new InvalidArgumentException(
            $this->messageBagToString(
                $messages
            )
        );
    }

    private function messageBagToString(MessageBag $messages): string
    {
        $definition = $this->getDefinition();

        return
            collect($messages)
                ->mapWithKeys(
                    function ($value, $name) use ($definition) {
                        if (!$definition->hasArgument($name)) {
                            $name = $this->asOptionKey($name, $definition);
                        }

                        return [
                            $name => $value
                        ];
                    }
                )
                ->map(
                    function (array $value, string $name) {
                        return $name . PHP_EOL . collect($value)->map(
                                fn(string $value) => "  " . $value
                            )->implode(PHP_EOL);
                    }
                )->implode(PHP_EOL);
    }


    private function asOptionKey(string $name, InputDefinition $definition): string
    {
        $name = ltrim($name, '-');
        [$key] = explode('.', $name);
        $option = $definition->getOption($key);

        return sprintf(
            '--%s%s%s',
            $name,
            $option->isNegatable() ? '|--no-' . $name : '',
            $option->getShortcut() ? '|-' . $option->getShortcut() : ''
        );
    }

    /**
     * @param string $command
     * @param ValidatorInstance $validator
     * @return void
     */
    public function executeBeforeValidation(string $command, ValidatorInstance $validator): void
    {
        ($this->beforeValidation[$command] ?? fn() => null)($validator);
    }
}
