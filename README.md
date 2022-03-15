# Jukebox Crawler Framework

[![Latest Version on Packagist](https://img.shields.io/packagist/v/henzeb/laravel-console-facade.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)
[![Total Downloads](https://img.shields.io/packagist/dt/henzeb/laravel-console-facade.svg?style=flat-square)](https://packagist.org/packages/henzeb/query-filter-builder)

This package allows you to manage console output from places that are not directly inside 
the command classes. 

As my applications require the logic not to be directly inside the command classes, I found 
myself adding the output to the constructors, creating real ugly not reusable code. This 
simplifies the process for me, and now for you. 

## Installation
Just install with the following command.

```bash
composer require henzeb/laravel-console-facade
```

## Usage
Under the hood it uses the `InteractsWithIO` trait, so everything you can do with 
the output inside a command, you can call using the facade.
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

### Testing

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
