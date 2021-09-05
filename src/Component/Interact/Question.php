<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Closure;
use Inhere\Console\Component\InteractiveHandle;
use Inhere\Console\Console;
use Inhere\Console\Util\Show;
use function trim;
use function ucfirst;

/**
 * Class Question
 *
 * @package Inhere\Console\Component\Interact
 */
class Question extends InteractiveHandle
{
    /**
     * Ask a question, ask for results; return the result of the input
     *
     * @param string       $question
     * @param string       $default
     * @param Closure|null $validator Validator, must return bool.
     *
     * @return string
     * @example This is an example
     *          ```php
     *          $answer = Interact::ask('Please input your name?', null, function ($answer) {
     *          if (!preg_match('/\w{2,}/', $answer)) {
     *          // output error tips.
     *          Interact::error('The name must match "/\w{2,}/"');
     *          return false;
     *          }
     *
     *      return true;
     *   });
     *
     *  echo "Your input: $answer";
     * ```
     *
     * ```php
     *  // use the second arg in the validator.
     *  $answer = Interact::ask('Please input your name?', null, function ($answer, &$err) {
     *      if (!preg_match('/\w{2,}/', $answer)) {
     *          // setting error message.
     *          $err = 'The name must match "/\w{2,}/"';
     *
     *          return false;
     *      }
     *
     *      return true;
     *   });
     *
     *  echo "Your input: $answer";
     * ```
     */
    public static function ask(string $question, string $default = '', Closure $validator = null): string
    {
        if (!$question = trim($question)) {
            Show::error('Please provide a question text!', 1);
        }

        $defText = '' !== $default ? "(default: <info>$default</info>)" : '';
        $message = '<comment>' . ucfirst($question) . "</comment>$defText ";

        askQuestion:
        $answer = Console::readln($message);

        if ('' === $answer) {
            if ('' === $default) {
                Show::error('A value is required.');
                goto askQuestion;
            }

            return $default;
        }

        // has answer validator
        if ($validator) {
            $error = null;

            if ($validator($answer, $error)) {
                return $answer;
            }

            if ($error) {
                Show::warning($error);
            }

            goto askQuestion;
        }

        return $answer;
    }
}
