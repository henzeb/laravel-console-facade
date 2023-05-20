<?php

namespace Henzeb\Console\Providers;

use Closure;
use Henzeb\Console\Facades\Console;
use Henzeb\Console\Output\NullOutput;
use Henzeb\Console\Stores\OutputStore;
use Illuminate\Console\Command;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
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
        $this->app->singleton(
            'henzeb.outputstyle',
            function () {
                $inConsole = App::runningInConsole();
                $unitTests = App::runningUnitTests();

                return new OutputStyle(
                    $inConsole && !$unitTests ? new ArgvInput() : new ArrayInput([]),
                    $inConsole ? new SymfonyConsoleOutput() : new NullOutput()
                );
            }
        );

        $this->afterResolvingOutputStyle();

        $this->afterResolvingCommand();

        $this->listenToCommandEvents();
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
        $this->app->beforeResolving(
            Command::class,
            function (string $command) {
                Console::setCommandForValidation($command);
            }
        );

        $this->app->afterResolving(
            Command::class,
            function (Command $command) {

                $command->ignoreValidationErrors();

                $command->setCode(
                    Closure::bind(function (InputInterface $input, OutputInterface $output) {
                        $this->ignoreValidationErrors();
                        Console::setCommandForValidation($this::class);
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

    private function listenToCommandEvents(): void
    {
        Event::listen(
            CommandStarting::class,
            function (CommandStarting $command) {
                if (!$command->command) {
                    return;
                }

                Console::setCommandForValidation(
                    Artisan::all()[$command->command]->getName()
                );
            }
        );

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
