<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Component\InteractiveHandle;
use Inhere\Console\Console;
use Inhere\Console\Util\Show;
use function array_key_exists;
use function explode;
use function is_array;
use function trim;

/**
 * Class Choose
 *
 * @package Inhere\Console\Component\Interact
 */
class Choose extends InteractiveHandle
{
    /**
     * Choose one of several options
     *
     * @param string       $description
     * @param string|array $options Option data
     *                              e.g
     *                              [
     *                              // option => value
     *                              '1' => 'chengdu',
     *                              '2' => 'beijing'
     *                              ]
     * @param string|int   $default Default option
     * @param bool         $allowExit
     *
     * @return string
     */
    public static function one(string $description, $options, $default = null, bool $allowExit = true): string
    {
        if (!$description = trim($description)) {
            Show::error('Please provide a description text!', 1);
        }

        $options = is_array($options) ? $options : explode(',', $options);

        // If default option is error
        if (null !== $default && !isset($options[$default])) {
            Show::error("The default option [{$default}] don't exists.", true);
        }

        if ($allowExit) {
            $options['q'] = 'quit';
        }

        $text = "<comment>$description</comment>";
        foreach ($options as $key => $value) {
            $text .= "\n  <info>$key</info>) $value";
        }

        $defaultText = $default ? "[default:<comment>$default</comment>]" : '';
        Console::write($text);

        beginChoice:
        $r = Console::readln("Your choice$defaultText : ");

        // error, allow try again once.
        if (!array_key_exists($r, $options)) {
            goto beginChoice;
        }

        // exit
        if ($r === 'q') {
            Console::write("\n  Quit,ByeBye.", true, true);
        }

        return $r;
    }
}
