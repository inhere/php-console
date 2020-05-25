<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 12:46
 * @var Inhere\Console\Application $app
 */

use Inhere\Console\BuiltIn\PharController;
use Inhere\Console\BuiltIn\SelfUpdateCommand;
use Inhere\Console\Examples\Command\CorCommand;
use Inhere\Console\Examples\Command\DemoCommand;
use Inhere\Console\Examples\Command\TestCommand;
use Inhere\Console\Examples\Controller\HomeController;
use Inhere\Console\Examples\Controller\InteractController;
use Inhere\Console\Examples\Controller\ProcessController;
use Inhere\Console\Examples\Controller\ShowController;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;

$app->command(DemoCommand::class);
$app->command('exam', function (Input $in, Output $out) {
    $cmd = $in->getCommand();

    $out->info('hello, this is a test command: ' . $cmd);
}, 'a description message');

$app->command('test', TestCommand::class, [
    'aliases' => ['t']
]);

$app->command(SelfUpdateCommand::class, null, [
    'aliases' => ['selfUpdate']
]);

$app->command(CorCommand::class);

$app->controller('home', HomeController::class, [
    'aliases' => ['h']
]);

$app->controller(ProcessController::class, null, [
    'aliases' => 'prc'
]);
$app->controller(PharController::class);
$app->controller(ShowController::class);
$app->controller(InteractController::class);

// add alias for a group command.
$app->addAliases('home:test', 'h-test');
