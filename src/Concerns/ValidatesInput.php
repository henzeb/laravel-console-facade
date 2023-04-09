<?php

namespace Henzeb\Console\Concerns;

use Closure;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputDefinition;

trait ValidatesInput
{
    private array $rules = [];
    private array $messages = [];
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

    public function validateWith(array $rules, array $messages = []): void
    {
        $this->rules[$this->getCommandForValidation()] = $rules;
        $this->messages[$this->getCommandForValidation()] = $messages;
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
                $this->rules[$command] ?? $this->rules['default'],
                $this->messages[$command] ?? $this->messages['default']
            );

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
}
