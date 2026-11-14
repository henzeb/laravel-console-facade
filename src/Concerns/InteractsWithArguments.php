<?php

namespace Henzeb\Console\Concerns;

use Closure;
use Symfony\Component\Console\Input\Input;

trait InteractsWithArguments
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function mergeArguments(array $arguments): void
    {
        Closure::bind(
            function () use ($arguments) {

                $this->arguments = array_merge(
                    $this->arguments,
                    $arguments,
                );
            },
            $this->getInput(),
            Input::class
        )();
    }

    public function argumentGiven(string $argument): bool
    {
        return Closure::bind(
            function (string $argument) {
                return array_key_exists($argument, $this->arguments);
            },
            $this->getInput(),
            Input::class
        )($argument);
    }
}
