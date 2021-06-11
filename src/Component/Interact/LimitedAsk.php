<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Closure;
use Inhere\Console\Component\InteractiveHandle;
use Inhere\Console\Console;
use Inhere\Console\Util\Show;
use function sprintf;
use function trim;
use function ucfirst;

/**
 * Class LimitedAsk
 *
 * @package Inhere\Console\Component\Interact
 */
class LimitedAsk extends InteractiveHandle
{
    /**
     * Ask a question, ask for a limited number of times
     *   若输入了值且验证成功则返回 输入的结果
     *   否则，会连续询问 $times 次， 若仍然错误，退出
     *
     * @param string       $question  问题
     * @param string       $default   默认值
     * @param Closure|null $validator (默认验证输入是否为空)自定义回调验证输入是否符合要求; 验证成功返回true 否则 可返回错误消息
     * @param int          $times     Allow input times
     *
     * @return string
     * @example This is an example
     *
     * ```php
     * // no default value
     * Interact::limitedAsk('please entry you age?', null, function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         Interact::error('Allow the input range is 1-100');
     *         return false;
     *     }
     *     return true;
     * } );
     *
     * // has default value
     * Interact::limitedAsk('please entry you age?', 89, function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         Interact::error('Allow the input range is 1-100');
     *         return false;
     *     }
     *     return true;
     * } );
     * ```
     *
     */
    public static function ask(
        string $question,
        string $default = '',
        Closure $validator = null,
        int $times = 3
    ): string {
        if (!$question = trim($question)) {
            Show::error('Please provide a question text!', 1);
        }

        $answer = '';
        $back   = $times = ($times > 6 || $times < 1) ? 3 : $times;

        $question   = ucfirst($question);
        $hasDefault = '' !== $default;

        if ($hasDefault) {
            $message = "<comment>{$question}</comment>(default: <info>$default</info>) ";
        } else {
            $message = "<comment>{$question}</comment>";
            Console::write($message);
        }

        while ($times--) {
            if ($hasDefault) {
                $answer = Console::readln($message);

                if ('' === $answer) {
                    $answer = $default;
                    break;
                }
            } else {
                $num    = $times + 1;
                $answer = Console::readln(sprintf('(You have [<bold>%s</bold>] chances to enter!) ', $num));
            }

            // If setting verify callback
            if ($validator && true === $validator($answer)) {
                break;
            }

            // no setting verify callback
            if (!$validator && $answer !== '') {
                break;
            }
        }

        if ('' !== $answer) {
            return $answer;
        }

        if ($hasDefault) {
            return $default;
        }

        Console::write("\n  You've entered incorrectly <danger>$back</danger> times in a row. exit!", true, 1);
        return '';
    }
}
