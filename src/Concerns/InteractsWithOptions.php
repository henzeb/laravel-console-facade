<?php

namespace Henzeb\Console\Concerns;

use Closure;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;

trait InteractsWithOptions
{
    public function mergeOptions(array $options): void
    {
        Closure::bind(
            function() use ($options){
                $this->options = array_merge(
                    $this->options,
                    $options,
                );
            },
            $this->getInput(),
            Input::class
        )();
    }

    public function optionGiven(string $option): bool {
        return Closure::bind(
            function($option){
                return array_key_exists($option, $this->options);
            },
            $this->getInput(),
            Input::class
        )($option);
    }
}
