<?php declare(strict_types=1);

/*
\r 回到行首
chr 转换为 ASCI 码
*/

/**
 * @param int $total
 * @param string $msg
 * @param string $char
 * @return Generator
 */
function progress_bar($total, $msg, $char = '=')
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
