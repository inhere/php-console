<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-11
 * Time: 15:37
 */

function rl_callback($ret)
{
    global $c, $prompting;

    echo "You entered: $ret\n";
    $c++;

    if ($c > 10) {
        $prompting = false;
        readline_callback_handler_remove();
    } else {
        readline_callback_handler_install("[$c] Enter something: ", 'rl_callback');
    }
}

$c = 1;
$prompting = true;

readline_callback_handler_install("[$c] Enter something: ", 'rl_callback');

while ($prompting) {
    $w = NULL;
    $e = NULL;
    $n = stream_select($r = array(STDIN), $w, $e, null);

    if ($n && in_array(STDIN, $r, true)) {
        // read a character, will call the callback when a newline is entered
        readline_callback_read_char();
    }
}

echo "Prompting disabled. All done.\n";