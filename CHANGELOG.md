# CHANGELOG

## v4.0.x

> begin at: 2020.08.21, branch `master`

## v3.1.21

## v3.1.20

**update**

- update some for error tips and single list
- add before/after methods on run controller method.
- support controller object cache on repeat fetch
- enhance built in php serve util logic

## v3.1.19

> publish at 2021.04.20

- up: use `Str::padByWidth` support zh-CN words on render table/list/panel

## v3.0.x

> publish at: 2019.01.03

- modify some dir structure
- remove some helper methods, use deps lib instead
- fix some bugs, format code

## v2.4.0

> publish at: 2018.07.03

- **update deps**: add dep toolkit/cli-utils and toolkit/sys-utils
- fix: no des when display alone command help
- add new interactive method: `Interact::answerIsYes()`
- update PharCompiler.php, some bug fixed
- adjust help information display
- remove some invalid classes

## v2.3.3

> publish at: 2018.03.15

**update**

- prompt password input use sh instead bash
- command help display modify
- modify error display
- Update README.md
- add more param type declare 

**new**

- add running sub-command support
- add disable command support in controller
- add a built in command for run php dev server

**bug fixed**

- token_get_all not exist for Highlighter
- fix some errors for phar build

## v2.3.2

> publish at: 2018.01.26

- now can disable a controller or command by method `isEnabled()`
- fixed: should not display 'isAlone' command in a controller
- format codes, add more param type define
- some update for process util
- phar compiler can only pack changed files(by git status)
- group/command allow define alias by aliases() in class
- support run command by coroutine, base on swoole.
- update demo classes. add changelog file
- Update README.md

## v2.3.1

- fixed for alone command description message dispaly on use `-h`
- add global options for a method help info
- method annotation format update
- complete phar package tool. add a example controller for pack phar
    - you can run: `php examples/app phar:pack` to see demo

   
## v2.3.0

- move Style to Components dir
- move some demo files and tool class to `inhere/console-components`

## v2.2.5

- add a profiler tool class
- add new show method: `pointing`, `tree`, `splitLine`
- some update for params parse. add 'arrayValues' option
- add new method `writeStyle()` for output message

## v2.2.4

- add console code Highlighter tool, from 'jakub-onderka/php-console-highlighter'
- complete a simple template generator, add a new input class
- update readme

## v2.2.3

- simple ascii font display
- add a simple process util
- annotation var support in others tag: `options`,  `arguments`
- add new notify message method: `Show::spinner()` `Show::pending()`
- add new screenshots images, other update ...

## v2.2.2

- some modify for user interactive method. bug fixed for `write()` need quit
- new feature: alias support for command(app command alias and controller command alias)
- add new interactive : `multi` `select`
- Update README.md

## ....
