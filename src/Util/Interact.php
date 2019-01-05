<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used: CliInteract 命令行交互
 * file: CliInteract.php
 */

namespace Inhere\Console\Util;

use Toolkit\Sys\Sys;

/**
 * Class Interact
 * @package Inhere\Console\Util
 */
class Interact extends Show
{
    /**
     * read CLI input
     * @param mixed $message
     * @param bool  $nl
     * @param array $opts
     * [
     *   'stream' => \STDIN
     * ]
     * @return string
     */
    public static function read($message = null, $nl = false, array $opts = []): string
    {
        if ($message) {
            self::write($message, $nl);
        }

        $stream = $opts['stream'] ?? \STDIN;

        return \trim(fgets($stream));
    }

    /**
     * 读取输入信息
     * @param  mixed $message 若不为空，则先输出文本
     * @param  bool  $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public static function readRow($message = null, $nl = false): string
    {
        return self::read($message, $nl);
    }

    /**
     * @param null|mixed $message
     * @param bool       $nl
     * @return string
     */
    public static function readFirst($message = null, $nl = false): string
    {
        $input = self::read($message, $nl);

        if ($input && ($f = $input[0])) {
            return $f;
        }

        return '';
    }

    /**************************************************************************************************
     * Interactive method (select/confirm/question/loopAsk)
     **************************************************************************************************/

    /**
     * alias of the `select()`
     * @param  string       $description 说明
     * @param  string|array $options 选项数据
     * e.g
     * [
     *    // option => value
     *    '1' => 'chengdu',
     *    '2' => 'beijing'
     * ]
     * @param  mixed        $default 默认选项
     * @param  bool         $allowExit 有退出选项 默认 true
     * @return string
     */
    public static function select(string $description, $options, $default = null, bool $allowExit = true): string
    {
        return self::choice($description, $options, $default, $allowExit);
    }

    /**
     * choice one of the options 在多个选项中选择一个
     * @param              $description
     * @param string|array $options
     * @param null         $default
     * @param bool         $allowExit
     * @return string
     */
    public static function choice(string $description, $options, $default = null, bool $allowExit = true): string
    {
        if (!$description = \trim($description)) {
            self::error('Please provide a description text!', 1);
        }

        $options = \is_array($options) ? $options : \explode(',', $options);

        // If default option is error
        if (null !== $default && !isset($options[$default])) {
            self::error("The default option [{$default}] don't exists.", true);
        }

        if ($allowExit) {
            $options['q'] = 'quit';
        }

        $text = "<comment>$description</comment>";
        foreach ($options as $key => $value) {
            $text .= "\n  <info>$key</info>) $value";
        }

        $defaultText = $default ? "[default:<comment>{$default}</comment>]" : '';
        self::write($text);

        beginChoice:
        $r = self::read("Your choice{$defaultText} : ");

        // error, allow try again once.
        if (!\array_key_exists($r, $options)) {
            goto beginChoice;
        }

        // exit
        if ($r === 'q') {
            self::write("\n  Quit,ByeBye.", true, true);
        }

        return $r;
    }

    /**
     * alias of the `multiSelect()`
     * @param string       $description
     * @param string|array $options
     * @param null|mixed   $default
     * @param bool         $allowExit
     * @return array
     */
    public static function checkbox(string $description, $options, $default = null, $allowExit = true): array
    {
        return self::multiSelect($description, $options, $default, $allowExit);
    }

    /**
     * @param string       $description
     * @param string|array $options
     * @param null|mixed   $default
     * @param bool         $allowExit
     * @return array
     */
    public static function multiSelect(string $description, $options, $default = null, $allowExit = true): array
    {
        if (!$description = \trim($description)) {
            self::error('Please provide a description text!', 1);
        }

        $sep = ','; // ',' ' '
        $options = \is_array($options) ? $options : \explode(',', $options);

        // If default option is error
        if (null !== $default && !isset($options[$default])) {
            self::error("The default option [{$default}] don't exists.", true);
        }

        if ($allowExit) {
            $options['q'] = 'quit';
        }

        $text = "<comment>$description</comment>";
        foreach ($options as $key => $value) {
            $text .= "\n  <info>$key</info>) $value";
        }

        self::write($text);
        $defaultText = $default ? "[default:<comment>{$default}</comment>]" : '';
        $filter = function ($val) use ($options) {
            return $val !== 'q' && isset($options[$val]);
        };

        beginChoice:
        $r = self::read("Your choice{$defaultText} : ");
        $r = $r !== '' ? \str_replace(' ', '', \trim($r, $sep)) : '';

        // empty
        if ($r === '') {
            goto beginChoice;
        }

        // exit
        if ($r === 'q') {
            self::write("\n  Quit,ByeBye.", true, true);
        }

        $rs = \strpos($r, $sep) ? \array_filter(\explode($sep, $r), $filter) : [$r];

        // error, try again
        if (!$rs) {
            goto beginChoice;
        }

        return $rs;
    }

    /**
     * 确认, 发出信息要求确认
     * @param string $question 发出的信息
     * @param bool   $default Default value
     * @return bool
     */
    public static function confirm(string $question, $default = true): bool
    {
        if (!$question = \trim($question)) {
            self::warning('Please provide a question message!', 1);
        }

        $question = \ucfirst(\trim($question, '?'));
        $default = (bool)$default;
        $defaultText = $default ? 'yes' : 'no';
        $message = "<comment>$question ?</comment>\nPlease confirm (yes|no)[default:<info>$defaultText</info>]: ";

        while (true) {
            $answer = self::read($message);

            if (empty($answer)) {
                return $default;
            }

            if (0 === \stripos($answer, 'y')) {
                return true;
            }

            if (0 === \stripos($answer, 'n')) {
                return false;
            }
        }

        return false;
    }

    /**
     * Usage:
     *
     * ```php
     *  echo "are you ok?";
     *  $ok = Interact::answerIsYes();
     * ```
     *
     * @param bool|null $default
     * @return bool
     */
    public static function answerIsYes(bool $default = null): bool
    {
        $mark = ' [yes|no]: ';

        if ($default !== null) {
            $defText = $default ? 'yes' : 'no';
            $mark = \sprintf(' [yes|no](default <cyan>%s</cyan>): ', $defText);
        }

        if ($answer = self::readFirst($mark)) {
            $answer = \strtolower($answer);

            if ($answer === 'y') {
                return true;
            }

            if ($answer === 'n') {
                return false;
            }
        } elseif ($default !== null) {
            return $default;
        }

        print 'Please try again';
        return self::answerIsYes();
    }

    /**
     * alias of the `question()`
     * @param string      $question 问题
     * @param null|string $default 默认值
     * @param \Closure    $validator The validate callback. It must return bool.
     * @return string|null
     */
    public static function ask(string $question, $default = null, \Closure $validator = null)
    {
        return self::question($question, $default, $validator);
    }

    /**
     * 询问，提出问题；返回 输入的结果
     * @example This is an example
     * ```php
     *  $answer = Interact::ask('Please input your name?', null, function ($answer) {
     *      if (!preg_match('/\w{2,}/', $answer)) {
     *          // output error tips.
     *          Interact::error('The name must match "/\w{2,}/"');
     *          return false;
     *      }
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
     * @param string        $question
     * @param null|mixed    $default
     * @param \Closure|null $validator Validator, must return bool.
     * @return null|string
     */
    public static function question(string $question, $default = null, \Closure $validator = null)
    {
        if (!$question = \trim($question)) {
            self::error('Please provide a question text!', 1);
        }

        $defText = null !== $default ? "(default: <info>$default</info>)" : '';
        $message = '<comment>' . \ucfirst($question) . "</comment>$defText ";

        askQuestion:
        $answer = self::read($message);

        if ('' === $answer) {
            if (null === $default) {
                self::error('A value is required.');

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

    /**
     * 有次数限制的询问,提出问题
     *   若输入了值且验证成功则返回 输入的结果
     *   否则，会连续询问 $times 次， 若仍然错误，退出
     * @param string      $question 问题
     * @param null|string $default 默认值
     * @param \Closure    $validator (默认验证输入是否为空)自定义回调验证输入是否符合要求; 验证成功返回true 否则 可返回错误消息
     * @example This is an example
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
     * @param int         $times Allow input times
     * @return string|null
     */
    public static function limitedAsk(string $question, $default = null, \Closure $validator = null, int $times = 3)
    {
        if (!$question = \trim($question)) {
            self::error('Please provide a question text!', 1);
        }

        $result = false;
        $answer = '';
        $question = ucfirst($question);
        $hasDefault = null !== $default;
        $back = $times = ($times > 6 || $times < 1) ? 3 : $times;

        if ($hasDefault) {
            $message = "<comment>{$question}</comment>(default: <info>$default</info>) ";
        } else {
            $message = "<comment>{$question}</comment>";
            Show::write($message);
        }

        while ($times--) {
            if ($hasDefault) {
                $answer = self::read($message);

                if ('' === $answer) {
                    $answer = $default;
                    $result = true;

                    break;
                }
            } else {
                $num = $times + 1;
                $answer = self::read(sprintf('(You have [<bold>%s</bold>] chances to enter!) ', $num));
            }

            // If setting verify callback
            if ($validator && ($result = $validator($answer)) === true) {
                break;
            }

            // no setting verify callback
            if (!$validator && $answer !== '') {
                $result = true;

                break;
            }
        }

        if (!$result) {
            if (null !== $default) {
                return $default;
            }

            self::write(
                "\n  You've entered incorrectly <danger>$back</danger> times in a row. exit!",
                true,
                1
            );
        }

        return $answer;
    }

    /**************************************************************************************************
     * password ask
     **************************************************************************************************/

    /**
     * Interactively prompts for input without echoing to the terminal.
     * Requires a bash shell or Windows and won't work with
     * safe_mode settings (Uses `shell_exec`)
     * @param string $prompt
     * @return string
     * @link https://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
     * @link http://www.sitepoint.com/blogs/2009/05/01/interactive-cli-password-prompt-in-php
     * @throws \RuntimeException
     */
    public static function promptSilent(string $prompt = 'Enter Password:'): string
    {
        $prompt = $prompt ? \addslashes($prompt) : 'Enter:';

        // $checkCmd = "/usr/bin/env bash -c 'echo OK'";
        // $shell = 'echo $0';

        // linux, unix, git-bash
        if (Sys::shIsAvailable()) {
            // COMMAND: sh -c 'read -p "Enter Password:" -s user_input && echo $user_input'
            $command = sprintf('sh -c "read -p \'%s\' -s user_input && echo $user_input"', $prompt);
            $password = Sys::execute($command, false);

            echo "\n";
            return $password;
        }

        // at windows cmd.
        if (Sys::isWindows()) {
            $vbScript = Sys::getTempDir() . '/hidden_prompt_input.vbs';

            file_put_contents($vbScript, 'wscript.echo(InputBox("' . $prompt . '", "", "password here"))');

            $command = 'cscript //nologo ' . escapeshellarg($vbScript);
            $password = rtrim(shell_exec($command));
            unlink($vbScript);

            return $password;
        }

        throw new \RuntimeException('Can not invoke bash shell env');
    }

    /**
     * alias of the method `promptSilent()`
     * @param string $prompt
     * @return string
     * @throws \RuntimeException
     */
    public static function askHiddenInput(string $prompt = 'Enter Password:'): string
    {
        return self::promptSilent($prompt);
    }

    /**
     * alias of the method `promptSilent()`
     * @param string $prompt
     * @return string
     * @throws \RuntimeException
     */
    public static function askPassword(string $prompt = 'Enter Password:'): string
    {
        return self::promptSilent($prompt);
    }
}
