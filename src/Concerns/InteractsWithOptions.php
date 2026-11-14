<?php

namespace Henzeb\Console\Concerns;

use Closure;
use Symfony\Component\Console\Input\Input;

trait InteractsWithOptions
{
    /**
     * @param array<string, mixed> $options
     */
    public function mergeOptions(array $options): void
    {
        Closure::bind(
            function () use ($options) {
                $this->options = array_merge(
                    $this->options,
                    $options,
                );
            },
            $this->getInput(),
            Input::class
        )();
    }

    public function optionGiven(string $option): bool
    {
        return Closure::bind(
            function (string $option) {
                return array_key_exists($option, $this->options);
            },
            $this->getInput(),
            Input::class
        )($option);
    }
}
