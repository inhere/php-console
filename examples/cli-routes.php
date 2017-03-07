<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 12:46
 *
 * @var inhere\console\App $app
 */

require 'TestCommand.php';
require 'HomeController.php';

$app->command('demo',function($in, \inhere\console\io\Output $out){
    $out->info('hello, this is a test.');
});

$app->command('test', TestCommand::class);
$app->controller('home', HomeController::class);
