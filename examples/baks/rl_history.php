<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-08-11
 * Time: 17:35
 */

$history_file = __DIR__ . '/.interactive_history';

# read history from previous session
if (is_file($history_file)) {
    readline_read_history($history_file);
}

require __DIR__ . '/auto_complete.php';

# put this at the end of yur script to save history and take care of $_SERVER['HISTSIZE']
if (!readline_write_history($history_file)) {
    exit("write history to file is failed!\n");
}

if (!function_exists('readline_list_history')) {
    echo "readline_list_history() not exists\n";
    exit;
}

# clean history if too long
$hist = readline_list_history();

if (($histSize = count($hist)) > $_SERVER['HISTSIZE']) {
    $hist = array_slice($hist, $histSize - $_SERVER['HISTSIZE']);

    # in php5 you can replaces thoose line with a file_puts_content()
    if ($fp = fopen($history_file, 'wb')) {
        fwrite($fp, implode("\n", $hist));
        fclose($fp);
    }
}
