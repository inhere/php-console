<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-21
 * Time: 10:02
 * @link https://github.com/dealnews/Console
 */

class Status
{
    public static function clearLine()
    {
        echo "\033[2K"; // delete the current line
        echo "\r"; // return the cursor to the beginning of the line
    }

    /**
     * Spinner that updates every call up to 4 per second
     *
     * .*.
     */
    public static function spinner()
    {
        static $spinner = 0;
        static $mtime = null;

        // static $chars = '-\|/';
        static $chars = '-.*.-';

        $now = microtime(true);
        if (null === $mtime || ($mtime < $now - 0.1)) {
            $mtime = $now;
            self::clearLine();
            echo $chars[$spinner];
            $spinner++;

            if ($spinner > strlen($chars) - 1) {
                $spinner = 0;
            }
        }
    }

    /**
     * Uses `stty` to hide input/output completely.
     * @param boolean $hidden Will hide/show the next data. Defaults to true.
     */
    public static function hide($hidden = true)
    {
        system('stty ' . ($hidden? '-echo' : 'echo'));
    }

    /**
     * Prompts the user for input. Optionally masking it.
     *
     * @param   string  $prompt     The prompt to show the user
     * @param   bool    $masked     If true, the users input will not be shown. e.g. password input
     * @param   int     $limit      The maximum amount of input to accept
     * @return  string
     */
    public static function prompt($prompt, $masked=false, $limit=100)
    {
        echo "$prompt: ";
        if ($masked) {
            `stty -echo`; // disable shell echo
        }
        $buffer = '';
        $char = '';
        $f = fopen('php://stdin', 'r');
        while (strlen($buffer) < $limit) {
            $char = fread($f, 1);
            if ($char === "\n" || $char === "\r") {
                break;
            }
            $buffer.= $char;
        }
        if ($masked) {
            `stty echo`; // enable shell echo
            echo "\n";
        }
        return $buffer;
    }
}

Status::hide();
echo 'Password: ';
$input = fgets(STDIN);
Status::hide(false);
echo $input;
die;

$total = random_int(5000, 10000);
for ($x=1; $x<=$total; $x++) {
    Status::spinner();
    usleep(50);
}

Status::clearLine();

//
// $answer = Status::prompt("What is the secret word?", 0);
// if ($answer == "secret") {
//     echo "Yay! You got it!";
// } else {
//     echo "Boo! That is wrong!";
// }
