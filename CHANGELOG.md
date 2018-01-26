# CHANGELOG

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
