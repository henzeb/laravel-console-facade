# Console Output

[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/laravel-console-facade.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/laravel-console-facade.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)

This package allows you to manage console output from places that are not directly inside the command classes.

As my applications require the logic not to be directly inside the command classes, I found myself adding the output to
the constructors, creating real ugly not reusable code. This simplifies the process for me, and now for you.

## Installation

Just install with the following command.

```bash
composer require henzeb/laravel-console-facade
```

## Usage

Under the hood it uses the `InteractsWithIO` trait, so everything you can do with the output inside a command, you can
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
Laravel released a new style for their commands, and they use a special Factory for that. With this method, you can use them 
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

The facade also allows you to manage and use sections. Inside the section you can only use the output methods from
`InteractsWithIO` like `table`, `progressbar` or `info`, so that means asking questions cannot be done.

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
like a table, that takes a lot of time. With this, everything is generated first before
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
The default `overwrite` method of Symfony is kinda slow when it comes to repeated rendering.
If you find your console application is flickering, `replace` is a good `replacement`.

Note: `render` and the callback method on `section` are both using `replace` under the hood.

### exit
Exit allows you to call exit anywhere in your code while making it easy to test.

```php
Console::exit();
Console::exit(1);
```
#### exit hooks
You can also add hooks that will execute when you call `exit`. Be aware that it does not register them as exit 
functions. 

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

#### onSignal
`onSignal` uses pcntl_signal to register the callbacks. But what's different, `onsignal` will allow you to register
multiple handlers. This gives you more granular control over what is executed on certain signals without overriding 
existing handlers.

In below scenario, all three will run when a `SIGINT` signal is given and the second will also run when a `SIGTERM` signal
is given. The first handler returns true. This means that when all handlers are executed, an exit is given.
```php
Console::onSignal(
    function () {
        print('first handler');
        return true;
    },
    SIGINT
);

Console::onSignal(
    function () {
        print('second handler');
        var_dump(func_get_args());
        return false;
    },
    SIGINT,
    SIGTERM
);

Console::onSignal(
    function () {
        print('third handler');
    },
    SIGINT
);
```

Tip: When a handler was already registered the normal way, you can use `pcntl_signal_get_handler` to pass this in to
`onSignal`

## Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email henzeberkheij@gmail.com instead of using the issue tracker.

## Credits

- [Henze Berkheij](https://github.com/henzeb)

## License

The GNU AGPLv. Please see [License File](LICENSE.md) for more information.
