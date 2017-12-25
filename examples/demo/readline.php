<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-11
 * Time: 14:11
 */

printf("readline_list_history() exists: %s\n" , function_exists('readline_list_history') ? 'yes' : 'no');

if (!function_exists('readline')) {
    function readline($prefix) {
        echo $prefix;
        return stream_get_line(STDIN, 1024, PHP_EOL);
    }
}

$line = readline('>>> ');

if (!empty($line)) {
    readline_add_history($line);

    //dump history
//    print_r(readline_list_history());

//dump variables
    print_r(readline_info());
}
