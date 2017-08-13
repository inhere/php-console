<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 12:46
 *
 * @var inhere\console\App $app
 */

use inhere\console\examples\HomeController;
use inhere\console\examples\TestCommand;
use inhere\console\io\Input;
use inhere\console\io\Output;

$app->command('exam', function (Input $in, Output $out) {
    $cmd = $in->getCommand();

    $out->info('hello, this is a test command: ' . $cmd);
});

$app->command('test', TestCommand::class);
$app->command(\inhere\console\examples\DemoCommand::class);
$app->controller('home', HomeController::class);
