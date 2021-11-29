<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

/**
 * @param int $total
 * @param string $msg
 * @param string $char
 *
 * @return Generator
 */
function progress_bar(int $total, string $msg, string $char = '='): Generator
{
    $finished = false;

    while (true) {
        if ($finished) {
            return;
        }

        $current = yield;
        $progress = ceil(($current/$total) * 100);

        if ($progress >= 100) {
            $progress = 100;
            $finished = true;
        }

        printf(
            "\r[%-100s] %d%% %s",
            str_repeat($char, $progress) . ($finished ? '' : '>'),
            $progress,
            $msg
        );// ♥

        if ($finished) {
            echo "\n";
            break;
        }
    }
}

$i = 0;
$total = 120;
$bar = progress_bar($total, 'Msg Text', '#');
echo "progress:\n";
while ($i <= $total) {
    $bar->send($i);
    usleep(50000);
    $i++;
}
//var_dump($bar->valid());
