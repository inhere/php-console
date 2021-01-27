# PHP Console

[![License](https://img.shields.io/packagist/l/inhere/console.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=7.2.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/inhere/console)
[![Latest Stable Version](http://img.shields.io/packagist/v/inhere/console.svg)](https://packagist.org/packages/inhere/console)
[![Github Actions Status](https://github.com/inhere/php-console/workflows/Unit-tests/badge.svg)](https://github.com/inhere/php-console/actions)

A simple, full-featured php command line application library. 

Provide console parameter parsing, command run, color style output, user information interaction, and special format information display.

> **[中文README](./README.zh-CN.md)**

## Command line preview

![app-command-list](https://raw.githubusercontent.com/inhere/php-console/master/docs/screenshots/app-command-list.png)

## Features

> Easy to use. Can be easily integrated into any existing project.

- Command line application, `controller`, `command` parsing run on the command line
- Support for setting aliases for commands. A command can have multiple aliases. Support command display/hide, enable/disable
- Full-featured command line option parameter parsing (named parameters, short options `-s`, long options `--long`). 
- The `input`, `output` of the command line, management, use
- Command method comments are automatically parsed as help information (by default, `@usage` `@arguments` `@options` `@example`)
- Support for outputting message texts of multiple color styles (`info`, `comment`, `success`, `warning`, `danger`, `error` ... )
- Commonly used special format information display (`section`, `panel`, `padding`, `helpPanel`, `table`, `tree`, `title`, `list`, `multiList`)
- Rich dynamic information display (`pending/loading`, `pointing`, `spinner`, `counterTxt`, `dynamicText`, `progressTxt`, `progressBar`)
- Common user information interaction support (`select`, `multiSelect`, `confirm`, `ask/question`, `askPassword/askHiddenInput`)
- Support for predefined parameter definitions like `symfony/console` (giving parameter values by position, recommended when strict parameter restrictions are required)
- The color output is `windows` `linux` `mac` compatible. Environments that do not support color will automatically remove the relevant CODE.
- Quickly generate auto-completion scripts for the current application in the `bash/zsh` environment
- NEW: Support start an interactive shell for run application

### Built-in tools

- Built-in Phar packaging tool class, which can be easily packaged into `phar` files. Easy to distribute and use
  - Run the command `php examples/app phar:pack` in the example, which will package this console library into an `app.phar`
- Built-in file download tool class under command line with progress bar display
- Command line php code highlighting support (from `jakub-onderka/php-console-highlighter` and making some adjustments)
- Simple Terminal screen, cursor control operation class
- Simple process operations using classes (fork, run, stop, wait ..., etc.)

> All features, effects; can be run in the example code `phps/app` in `examples/`. Basically covers all the features and can be tested directly

## Installation

```bash
composer require inhere/console
```

## Document List

> Please go to WIKI for detailed usage documentation

- **[Document Home](https://github.com/inhere/php-console/wiki/home)**
- **[Feature Overview](https://github.com/inhere/php-console/wiki/overview)**
- **[Install](https://github.com/inhere/php-console/wiki/install)**
- **[Create Application](https://github.com/inhere/php-console/wiki/quick-start)**
- **[Add Command](https://github.com/inhere/php-console/wiki/add-command)**
- **[Add Command Group](https://github.com/inhere/php-console/wiki/add-group)**
- **[Register Command](https://github.com/inhere/php-console/wiki/register-command)**
- **[Error/Exception Capture](https://github.com/inhere/php-console/wiki/error-handle)**
- **[Input Object](https://github.com/inhere/php-console/wiki/input-instance)**
- **[output object](https://github.com/inhere/php-console/wiki/output-instance)**
- **[Formatted Output](https://github.com/inhere/php-console/wiki/format-output)**
- **[Progress Dynamic Output](https://github.com/inhere/php-console/wiki/process-output)**
- **[User Interaction](https://github.com/inhere/php-console/wiki/user-interactive)**
- **[Extension Tools](https://github.com/inhere/php-console/wiki/extra-tools)**

## Project address

- **github** https://github.com/inhere/php-console.git
- **gitee** https://gitee.com/inhere/php-console.git

## Unit test

```bash
phpunit
// output coverage without xdebug
phpdbg -dauto_globals_jit=Off -qrr /usr/local/bin/phpunit --coverage-text
```

## License

[MIT](LICENSE)

## My other projects

- [inhere/php-validate](https://github.com/inhere/php-validate) A compact and full-featured php verification library
- [inhere/sroute](https://github.com/inhere/php-srouter) Lightweight and fast HTTP request routing library
