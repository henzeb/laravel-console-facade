<?php

namespace Henzeb\Console\Providers;

use Closure;
use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\ConsoleOutput;
use Henzeb\Console\Stores\OutputStore;
use Illuminate\Console\Command;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput as SymfonyConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;


class ConsoleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton('henzeb.outputstyle', function () {
            return new OutputStyle(
                App::runningUnitTests() ? new ArrayInput([]) : new ArgvInput(),
                new SymfonyConsoleOutput()
            );
            /*return resolve(
                OutputStyle::class,
                [
                    'input' => App::runningUnitTests() ? new ArrayInput([]) : new ArgvInput(),
                    'output' => new SymfonyConsoleOutput()
                ]
            );*/
        });

        $this->afterResolvingOutputStyle();

        $this->afterResolvingCommand();

        $this->listenToCommandFinished();
    }

    public function register()
    {

    }

    /**
     * @return void
     */
    private function afterResolvingOutputStyle(): void
    {
        $this->app->afterResolving(
            OutputStyle::class,
            function (OutputStyle $outputStyle) {

                $this->registerOutputFormatterStyles($outputStyle);

                if ($this->app->resolved('henzeb.outputstyle')) {
                    if ($output = Console::getOutput()) {
                        OutputStore::add($output);
                    }

                    Console::setOutput($outputStyle);
                }

            }
        );
    }

    private function afterResolvingCommand(): void
    {
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

    private function listenToCommandFinished(): void
    {
        Event::listen(
            CommandStarting::class,
            function (CommandStarting $command) {

                Closure::bind(
                    function (string $command = null) {
                        /** @var $this ConsoleOutput */
                        $this->setCommandToValidateWith((string)$command);
                    },
                    Console::getFacadeRoot(),
                    ConsoleOutput::class
                )($command->command);
            });

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

    private function registerOutputFormatterStyles(OutputStyle $outputStyle): void
    {
        $styles = [
            'henzeb.console.verbose' => new OutputFormatterStyle('cyan'),
            'henzeb.console.very.verbose' => new OutputFormatterStyle('yellow'),
            'henzeb.console.debug' => new OutputFormatterStyle('magenta'),
        ];

        $formatter = $outputStyle->getFormatter();

        foreach ($styles as $name => $style) {
            if (!$formatter->hasStyle($name)) {
                $formatter->setStyle($name, $style);
            }
        }
    }
}
