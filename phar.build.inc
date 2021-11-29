<?php
/**
 * Created by PhpStorm.
 * - the is a config file for compile phar package.
 * User: Inhere
 * Date: 2018/1/26 0026
 * Time: 22:11
 * @var \Inhere\Console\Component\PharCompiler $compiler
 */

// config
$compiler
  // ->stripComments(false)
  ->setShebang(true)
  ->addExclude([
    'demo',
    'example',
    'runtime',
    'node_modules',
    'test',
    'tmp',
  ])
  ->addFile([
    'LICENSE',
    'composer.json',
    'README.md',
    'test/bootstrap.php',
  ])
  ->setCliIndex('examples/app')
  // ->setWebIndex('web/index.php')
  // ->setVersionFile('config/config.php')
;

// Console 下的 Command Controller 命令类不去除注释，注释上是命令帮助信息
$compiler->setStripFilter(static function ($file) {
  /** @var SplFileInfo $file */
  $name = $file->getFilename();

  return !str_contains($name, 'Command.php') && !str_contains($name, 'Controller.php');
});
