<?php declare(strict_types=1);
require dirname(__DIR__) . '/../../autoload.php';

use Inhere\Console\Util\ProgressBar;

$i = 1;
$total = 100;
$bar = new ProgressBar;
var_dump($bar);

$bar->start($total);
while ($i <= $total) {
    $bar->advance();
    usleep(50000);
    $i++;
}
$bar->finish();
var_dump($bar);
