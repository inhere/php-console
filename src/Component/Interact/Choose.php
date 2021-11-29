<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Console;
use Inhere\Console\Util\Show;
use Toolkit\Cli\Cli;
use function array_key_exists;
use function array_keys;
use function explode;
use function implode;
use function is_array;
use function trim;

/**
 * Class Choose
 *
 * @package Inhere\Console\Component\Interact
 */
class Choose extends SingleSelect
{
    /**
     * Choose one of several options
     *
     * @param string          $description
     * @param array|string $options Option data
     *                                 e.g
     *                                 [
     *                                 // option => value
     *                                 '1' => 'chengdu',
     *                                 '2' => 'beijing'
     *                                 ]
     * @param int|string|null $default Default option
     * @param bool            $allowExit
     * @param array           $opts
     *
     * @psalm-param array{returnVal: bool, retFilter: callable}  $opts
     *
     * @return string
     */
    public static function one(string $description, array|string $options, int|string $default = null, bool $allowExit = true, array $opts = []): string
    {
        if (!$description = trim($description)) {
            Show::error('Please provide a description text!', 1);
        }

        $options = is_array($options) ? $options : explode(',', $options);

        // If default option is error
        if (null !== $default && !isset($options[$default])) {
            Show::error("The default option [$default] don't exists.", true);
        }

        if ($allowExit) {
            $options['q'] = 'quit';
        }

        $text = "<comment>$description</comment>";
        foreach ($options as $key => $value) {
            $text .= "\n  <info>$key</info>) $value";
        }

        $defaultText = $default !== null ? "[default:<info>$default</info>]" : '';
        Console::write($text);

        beginChoice:
        $r = Console::readln("Your choice$defaultText : ");

        if ($r === '' && $default !== null) {
            return $default;
        }

        // error, allow try again once.
        if (!array_key_exists($r, $options)) {
            Cli::warn('[WARN] input must in the list: ' . implode(',', array_keys($options)));
            goto beginChoice;
        }

        // exit
        if ($r === 'q') {
            Console::write("\n  Quit,ByeBye.", true, true);
        }

        // return value
        if ($opts['returnVal'] ?? false) {
            $r = $options[$r];
        }

        if ($retFn = $opts['retFilter'] ?? null) {
            $r = $retFn($r);
        }

        return $r;
    }
}
