<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 12:46
 *
 * @var Inhere\Console\Application $app
 */

use Inhere\Console\Examples\HomeController;
use Inhere\Console\Examples\TestCommand;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Utils\ProgressBar;

$app->command(\Inhere\Console\Examples\DemoCommand::class);
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
