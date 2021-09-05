<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Component\InteractiveHandle;
use Inhere\Console\Console;
use Inhere\Console\Util\Show;
use function array_filter;
use function explode;
use function is_array;
use function str_replace;
use function strpos;
use function trim;

/**
 * Class Checkbox
 *
 * @package Inhere\Console\Component\Interact
 */
class Checkbox extends InteractiveHandle
{
    /**
     * List multiple options and allow multiple selections
     *
     * @param string       $description
     * @param string|array $options
     * @param null|mixed   $default
     * @param bool         $allowExit
     *
     * @return array
     */
    public static function select(string $description, $options, $default = null, bool $allowExit = true): array
    {
        if (!$description = trim($description)) {
            Show::error('Please provide a description text!', 1);
        }

        $sep     = ','; // ',' ' '
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

        Console::write($text);
        $defText = $default ? "[default:<comment>{$default}</comment>]" : '';
        $filter  = function ($val) use ($options) {
            return $val !== 'q' && isset($options[$val]);
        };

        beginChoice:
        $r = Console::readln("Your choice{$defText} : ");
        $r = $r !== '' ? str_replace(' ', '', trim($r, $sep)) : '';

        // empty
        if ($r === '') {
            goto beginChoice;
        }

        // exit
        if ($r === 'q') {
            Console::write("\n  Quit,ByeBye.", true, true);
        }

        $rs = strpos($r, $sep) ? array_filter(explode($sep, $r), $filter) : [$r];

        // error, try again
        if (!$rs) {
            goto beginChoice;
        }

        return $rs;
    }
}
