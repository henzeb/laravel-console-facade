# Console Output

[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/laravel-console-facade.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/laravel-console-facade.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)

This package allows you to manage console output from places that are not
directly inside the command classes.

As my applications require the logic not to be directly inside the command
classes, I found myself adding the output to
the constructors, creating real ugly not reusable code. This simplifies the
process for me, and now for you.

## Installation

Just install with the following command.

```bash
composer require henzeb/laravel-console-facade
```

## Usage

Under the hood it uses the `InteractsWithIO` trait, so everything you can do
with the output inside a command, you can
use through the facade.

```php
use Henzeb\Console\Facades\Console;
class MyClass {
   
    public function writeMessage(): void
    {   
        Console::ask('Would you like to be able to do this?');
        Console::info('This message was brought to you by Henzeb');
    }
}
```

### Laravel's components factory

Laravel released a new style for their commands, and they use a special Factory
for that. With this method, you can use them
within your own classes.

```php
use Henzeb\Console\Facades\Console;
class MyClass {
   
    public function writeMessage(): void
    {   
        Console::components()->ask('Would you like to be able to do this?');
        Console::components()->info('This message was brought to you by Henzeb');
        Console::components()->bulletList(['this one', 'Another one']);
    }
}
```

### Section management

The facade also allows you to manage and use sections. Inside the section you
can only use the output methods from
`InteractsWithIO` like `table`, `progressbar` or `info`, so that means asking
questions cannot be done.

```php
use Henzeb\Console\Facades\Console;
class MyClass {
   
    public function useSection(): void
    {   
        Console::section('section1')->table(['header'=>'title'], [[]]);
        Console::section('section2')->withProgressBar(100, fn()=>true);
        Console::section('section1')->components()->bulletList(['this one', 'Another one']);
        Console::section('section1')->clear();
        Console::section('section3')->info('This message was brought to you by Henzeb');
    }
}
```

### delayed rendering

Delayed rendering is useful when you have to rebuild things from scratch,
like a table, that takes a lot of time. With this, everything is generated first
before
outputting it to the console.

```php
use Henzeb\Console\Facades\Console;

use Henzeb\Console\Output\ConsoleSectionOutput; 

class MyClass {

    public function renderWhenReady(): void
    {   
        Console::section('section1')->render(
            function(ConsoleSectionOutput $section){
                $section->table(['header'=>'title'], [[]]);
            }
        );
        
        Console::section(
            'section2', 
            function(ConsoleSectionOutput $section){
                $section->table(['header'=>'title'], [[]]);
            }
        );
        
    }
}
```

### replace

The default `overwrite` method of Symfony is kinda slow when it comes to
repeated rendering.
If you find your console application is flickering, `replace` is a
good `replacement`.

Note: `render` and the callback method on `section` are both using `replace`
under the hood.

### watch

`watch` is a method that mimics the `watch` command in Linux. By default it
will execute the given callback every 2 seconds.

```php
Console::watch(
    function (ConsoleSectionOutput $output) {
        $output->info(now()->toDateTimeString());
    },
);
```

You can specify the refresh rate to speed up or slow down the loop.

```php
Console::watch(
    function (ConsoleSectionOutput $output) {
        $output->info(now()->toDateTimeString());
    },
    1
);
```

It is also possible to specify the name for the section yourself. That way
you can manipulate the section inside for example a `trap` signal.

```php
Console::watch(
    function (ConsoleSectionOutput $output) {
        $output->info(now()->toDateTimeString());
    },
    sectionName: 'yourName'
);
```

### exit

Exit allows you to call exit anywhere in your code while making it easy to test.

```php
Console::exit();
Console::exit(1);
```

#### exit hooks

You can also add hooks that will execute when you call `exit`. Be aware that it
does not register them as shutdown functions.

```php
Console::onExit(
    function(int $exitcode) {
        Console::info('exited with code '.$exitcode);
    }
);

Console::onExit(
    function() {
        Console::info('exited with code 123');
    },
    123
);
```

### trap

Just like Laravel, there is a `trap` method to register signals. Under the hood,
this is not using the logic created by
Laravel and Symfony for backwards compatibility reasons, but it's similar.
See [#43933](https://github.com/laravel/framework/pull/43933) for more
information.

In below scenario, all three will run when a `SIGINT` signal is given and the
second will also run when a `SIGTERM` signal
is given. The first handler returns true. This means that when all handlers are
executed, an exit is given.

```php
Console::trap(
    function () {
        print('first handler');
        return true;
    },
    SIGINT
);

Console::trap(
    function () {
        print('second handler');
        var_dump(func_get_args());
        return false;
    },
    SIGINT,
    SIGTERM
);

Console::trap(
    function () {
        print('third handler');
    },
    SIGINT
);
```

#### Retrapping

trap allows you to trap a new signal handler. This is useful when you want
to be able to press `CTRL+C` twice. In the example below the next time the
signal is received, the application will forcibly exit.

```php
Console::trap(
    function () {
        print('first handler');
        
        Console::trap(
            function () {
                print('second handler');
                return true;
            }, 
            SIGINT
        );
    },
    SIGINT
);
```

Tip: When a handler was already registered the normal way or trough
Laravel's implementation, you can use `pcntl_signal_get_handler` to pass
this in to `trap`

Note: This was previously `onSignal`, but I have deprecated that method as
Laravel is using `trap`.

#### untrap

Just like laravel, there is an untrap method. This method is automatically
called just like the Laravel implementation, so you can use `Artisan::call`
within your command and not execute the wrong signal handlers.

```php
Console::untrap();
```

### Merging options and arguments

In some cases you may want to merge options or arguments, like resuming a
process with specific options or arguments stored in cache,
or to reconfigure a running daemon process.

```php
Console::mergeOptions(['env'=>'production']);

Console::mergeArguments(['yourArgument'=>true]);
```

When an option or argument is set through command line, that value will take
precedence.

### optionGiven and argumentGiven

In Laravel's `Command`, it can get pretty confusing to figure out if the user
has specified an option or an argument. An option with optional parameter
returns null either when set or not set. When you set a default, you could
figure it out, but it is not really userfriendly and feels hacky instead of
clean code.

The following methods tells you if a user has added the option or argument
to the commandline

```php

// artisan your:command --check --test=false
Console::optionGiven('check');   // returns true
Console::optionGiven('test');    // returns true
Console::optionGiven('verify');  // returns false

// artisan your:command verify
Console::argumentGiven('check');    //returns false
Console::argumentGiven('verify');   //returns true
```

## Validation
Whether you build a console application that is going to be distributed, or
just want to make sure no one can derail your application, you want to use
validation. Laravel Console Facade makes that very easy to do.

Suppose you want to validate the input from the following signature:

```
{id?} {--name=} {--age=*} {--birth=}
```

Inside the `configure` method you simply define the following:

```php
Console::validateWith(
    [
        'id' => 'bail|int|exists:users',
        '--name'=>'string|min:2',
        '--age.*' => 'bail|int|between:0,150',
        '--birth' => 'bail|prohibits:--age|date'
    ]
);
```

When running your command, the validation will automatically execute.

Under the hood, this uses Laravel's validation engine, so everything that 
the validation engine accepts, you can use here. 

Caveat: When you want to validate options against arguments or options that 
may not be passed, you may need to write your own `Rule` or closure.

### Messages
Since the translations are mainly based upon input coming from HTTP requests, 
you may want to give them different translations. Just add a second array 
like you would do with Laravel's validation engine:

````php

Console::validateWith(
    [
        'id' => 'bail|int|exists:users',
        '--name' => 'string|min:2',
        '--age.*' => 'bail|exclude_with:--birth|int|between:0,150',
        '--birth' => 'bail|prohibits:--age|date',
    ],
    [
        'exists' => 'User with given id does not exist!'
        'prohibits' => 'Cannot be used together with :other'
    ]   
);
````

### Closure based commands
When running `ClosureCommands` defined with `Artisan::command()` it does not
validate automatically. Instead, you can do the following:

````php
Artisan::command(
    'your:command {id?} {--name=} {--age=*} {--birth=}',
    function () {

        Console::validateWith(
            [
                //
            ]
        );

        Console::validate();
        
        //
    }
);
````

### macros

The Console facade and `Henzeb\Console\Output\ConsoleSectionOutput` are
Macroable using Laravel's Macroable trait.

```php
Console::macro(...)
Henzeb\Console\Output\ConsoleSectionOutput::macro(...)
```

See [documentation](https://laravel.com/api/master/Illuminate/Support/Traits/Macroable.html)

### conditions

You can use `when` and `unless` just like you are used to on the facade as
well as inside sections.
See [documentation](https://laravel.com/api/master/Illuminate/Support/Traits/Conditionable.html)

## Testing

Next to the usual Facade test options, I have added some convenient
methods for use inside your tests.

```php
Console::shouldExit();
Console::shouldNotExit();
Console::shouldExitWith(int $seconds);
Console::shouldSleep();
Console::shouldNotSleep();
Console::shouldSleepWith(int $seconds);
Console::watchShouldLoop(int $times, int $sleep = null);
```

## Testing this package

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed
recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email
henzeberkheij@gmail.com instead of using the issue tracker.

## Credits

- [Henze Berkheij](https://github.com/henzeb)

## License

The GNU AGPLv. Please see [License File](LICENSE.md) for more information.
