<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Component\InteractiveHandle;
use Inhere\Console\Console;
use Inhere\Console\Util\Show;
use function stripos;
use function trim;
use function ucfirst;

/**
 * Class Confirm
 *
 * @package Inhere\Console\Component\Interact
 */
class Confirm extends InteractiveHandle
{
    /**
     * Send a message request confirmation
     *
     * @param string $question The question message
     * @param bool   $default  Default value
     *
     * @return bool
     */
    public static function ask(string $question, bool $default = true): bool
    {
        if (!$question = trim($question)) {
            Show::warning('Please provide a question message!', 1);
            return false;
        }

        $defText  = $default ? 'yes' : 'no';
        $question = ucfirst(trim($question, '?'));
        $message  = "<comment>$question ?</comment>\nPlease confirm (yes|no)[default:<info>$defText</info>]: ";

        while (true) {
            $answer = Console::readChar($message);
            if ('' === $answer) {
                return $default;
            }

            if (0 === stripos($answer, 'y')) {
                return true;
            }

            if (0 === stripos($answer, 'n')) {
                return false;
            }
        }
    }
}
