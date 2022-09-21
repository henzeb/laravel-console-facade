<?php

namespace Henzeb\Console\Providers;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Henzeb\Console\Facades\Console;
use Illuminate\Support\Facades\Event;
use Henzeb\Console\Stores\OutputStore;
use Illuminate\Support\ServiceProvider;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Concerns\ValidatesInput;
use Illuminate\Console\Events\CommandFinished;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ConsoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        app(ConsoleOutput::class);
        $this->afterResolvingOutputStyle();

        $this->afterResolvingCommand();

        $this->listenToCommandFinished();
    }

    /**
     * @return void
     */
    protected function afterResolvingOutputStyle(): void
    {
        $this->app->afterResolving(
            OutputStyle::class,
            function (OutputStyle $outputStyle) {
                if (Console::getOutput()) {
                    OutputStore::add(Console::getOutput());
                }

                Console::setOutput($outputStyle);
            }
        );
    }

    /**
     * @return void
     */
    protected function listenToCommandFinished(): void
    {
        Event::listen(
            CommandFinished::class,
            function () {
                if (OutputStore::hasOutputs()) {
                    Console::setOutput(
                        OutputStore::pop()
                    );
                }
            }
        );
    }

    /**
     * @return void
     */
    protected function afterResolvingCommand(): void
    {
        $this->app->beforeResolving(
            Command::class,
            Closure::bind(
                function (string $command) {
                    $this->commandToValidateWith = $command;
                },
                Console::getFacadeRoot(),
                ConsoleOutput::class
            )
        );


        $this->app->afterResolving(
            Command::class,
            function (Command $command) {
                Console::setCommand($command);

                $command->ignoreValidationErrors();

                $command->setCode(
                    Closure::bind(function (InputInterface $input, OutputInterface $output) {
                        Console::setCommand($this);
                        Console::validate();

                        /**
                         * rebinding definition causes revalidation of original validation.
                         */
                        $input->bind($this->getDefinition());

                        return $this->execute($input, $output);
                    },
                        $command,
                        Command::class
                    )
                );
            }
        );
    }
}
