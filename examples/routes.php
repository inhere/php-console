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
use inhere\console\utils\ProgressBar;

$app->command(\inhere\console\examples\DemoCommand::class);
$app->command('exam', function (Input $in, Output $out) {
    $cmd = $in->getCommand();

    $out->info('hello, this is a test command: ' . $cmd);
});

$app->command('test', TestCommand::class);
$app->command('prg', function () {
    $i = 0;
    $total = 120;
    $bar = new ProgressBar();
    $bar->start(120);

    while ($i <= $total) {
        $bar->advance();
        usleep(50000);
        $i++;
    }

    $bar->finish();

}, 'a description message');

$app->controller('home', HomeController::class);
