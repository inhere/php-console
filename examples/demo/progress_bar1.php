<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

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
