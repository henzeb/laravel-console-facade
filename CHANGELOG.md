# Changelog

All notable changes to `Laravel Console Facade` will be documented in this file

## 1.17.0 - 2023-04-11

- allow sections without names
- `getOutput` on sections now return `OutputStyle` instead of itself.

## 1.16.0 - 2023-04-09

- added [tail](README.md#tail) for easy scrolling functionality
- added [console](README.md#console-helper) helper function
- fixed some issues with validation

## 1.15.0 - 2023-03-29

- added easy-to-use interface for [verbose](README.md#verbosity) output
- added [silence](README.md#silence) to allow you to silence certain parts based
- added [unsilence](README.md#unsilence) to allow you to show certain parts based
  on a boolean.

## 1.13.0 - 2022-09-22

- added Validation for easy validating input from commandline

## 1.12.0 - 2022-09-18

- added `watch` to emulate the watch application on Linux
- added a lot of tests
- added a few convenient methods for your testing purposes

## 1.11.0 - 2022-09-16

- Console is now isolated within `Artisan::call` commands.
- trap/untrap can now operate the same as the Laravel implementation

## 1.10.0 - 2022-09-15

- added `optionGiven` and `argumentGiven`
- added [Conditionable](https://laravel.com/api/master/Illuminate/Support/Traits/Conditionable.html)
- added [Macroable](https://laravel.com/api/master/Illuminate/Support/Traits/Macroable.html)

## 1.9.0 - 2022-09-14

- added `mergeOptions` and `mergeArguments`

## 1.8.0 - 2022-09-12

- added `trap`/`untrap` to replace `onSignal` (deprecated now)

## 1.7.0 - 2022-08-30

- added `onSignal`, to allow more control over what happens on given signals.

## 1.6.0 - 2022-08-19

- added components method to the new Components Factory of Laravel

## 1.5.0 - 2022-04-08

- added ability to execute callables on exit

## 1.4.0 - 2022-04-05

- added exit method to make testing with exits a lot easier.
- no longer need to expect setOutput when mocking the facade.

## 1.3.0 - 2022-03-21

- adds faster section replacement method

## 1.2.0 - 2022-03-19

- adds delayed rendering

## 1.1.0 - 2022-03-18

- adds section management

## 1.0.0 - 2022-03-15

- initial release
