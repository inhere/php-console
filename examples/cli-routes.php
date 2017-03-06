<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 12:46
 *
 * @var inhere\console\App $app
 */

$app->command('test',function($in, \inhere\console\io\Output $out){
    $out->info('hello, this is a test.');
});

$app->command('test1', \inhere\console\examples\TestCommand::class);
$app->controller('home', \inhere\console\examples\HomeController::class);
