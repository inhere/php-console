<?php declare(strict_types=1);

echo "start\n\n";

/*
\r 回到行首
chr 转换为 ASCI 码
*/

$i = 1;
$total = 100;
while ($i <= 100) {
    // printf("\rMade some progress, downloaded %2d kb so far", $i);
    // echo "\rMade some progress, downloaded $i kb so far";
    // printf("\rMade some progress, completed items: %d%% (%d/%d)", ceil(($i/$total) * 100), $i, $total);

    $length = $i; // ■ = #
    $tfKb = ceil($i*0.95);
//     printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat('#', $length) . '>', $length, $tfKb, $i * 10);
     // mac is not support there are chars.
    // printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat(chr(22), $length) . '>', $length, $tfKb, $i * 10); // ■
    // printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat(chr(1), $length) . '>', $length, $tfKb, $i * 10);// ☺
    // printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat(chr(2), $length) . '>', $length, $tfKb, $i * 10);// ☻
    printf("\r[%-100s] %d%% (%2d/%2d kb)", str_repeat(chr(3), $length) . '>', $length, $tfKb, $i * 10);// ♥

    usleep(50000);
    $i++;
}

echo "end\n";
