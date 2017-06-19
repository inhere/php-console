<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used: CliInteract 命令行交互
 * file: CliInteract.php
 */

namespace inhere\console\utils;

/**
 * Class Interact
 * @package inhere\console\utils
 */
class Interact extends Show
{

/////////////////////////////////////////////////////////////////
/// Interactive method (select/confirm/question/loopAsk)
/////////////////////////////////////////////////////////////////

    /**
     * Select one of the options 在多个选项中选择一个
     * @param  string $description 说明
     * @param  mixed $options 选项数据
     * e.g
     * [
     *    // option => value
     *    '1' => 'chengdu',
     *    '2' => 'beijing'
     * ]
     * @param  mixed $default 默认选项
     * @param  bool $allowExit 有退出选项 默认 true
     * @return string
     */
    public static function select($description, $options, $default = null, $allowExit = true)
    {
        return self::choice($description, $options, $default, $allowExit);
    }

    public static function choice($description, $options, $default = null, $allowExit = true)
    {
        if (!$description = trim($description)) {
            self::error('Please provide a description text!', 1);
        }

        $options = is_array($options) ? $options : explode(',', $options);

        // If default option is error
        if (null !== $default && !isset($options[$default])) {
            self::error("The default option [{$default}] don't exists.", true);
        }

        if ($allowExit) {
            $options['q'] = 'quit';
        }

        beginChoice:
        $text = " <comment>$description</comment>";
        foreach ($options as $key => $value) {
            $text .= "\n  <info>$key</info>) $value";
        }

        $defaultText = $default ? "[default:<comment>{$default}</comment>]" : '';
        $r = self::read($text . "\n You choice{$defaultText} : ");

        // error, allow try again once.
        if (!array_key_exists($r, $options)) {
            goto beginChoice;
        }

        // exit
        if ($r === 'q') {
            self::write("\n  Quit,ByeBye.", true, true);
        }

        return $r;
    }

    public static function mSelect($description, $options, $default = null, $allowExit = true)
    {

    }

    /**
     * 确认, 发出信息要求确认
     * @param string $question 发出的信息
     * @param bool $default Default value
     * @return bool
     */
    public static function confirm($question, $default = true)
    {
        if (!$question = trim($question)) {
            self::error('Please provide a question text!', 1);
        }

        $question = ucfirst(trim($question, '?'));
        $default = (bool)$default;
        $defaultText = $default ? 'yes' : 'no';
        $message = " <comment>$question ?</comment>\n Please confirm (yes|no) [default:<info>$defaultText</info>]: ";

        while (true) {
            $answer = self::read($message);

            if (empty($answer)) {
                return $default;
            }

            if (0 === stripos($answer, 'y')) {
                return true;
            }

            if (0 === stripos($answer, 'n')) {
                return false;
            }
        }

        return false;
    }

    /**
     * 询问，提出问题；返回 输入的结果
     * @param string $question 问题
     * @param null|string $default 默认值
     * @param \Closure $validator The validate callback. It must return bool.
     * @example This is an example
     *
     * ```
     *  $answer = Interact::ask('Please input your name?', null, function ($answer) {
     *      if ( !preg_match('/\w+/', $answer) ) {
     *          Interact::error('The name must match "/\w+/"');
     *
     *          return false;
     *      }
     *
     *      return true;
     *   });
     * ```
     *
     * @return string
     */
    public static function ask($question, $default = null, \Closure $validator = null)
    {
        return self::question($question, $default, $validator);
    }

    public static function question($question, $default = null, \Closure $validator = null)
    {
        if (!$question = trim($question)) {
            self::error('Please provide a question text!', 1);
        }

        $defaultText = null !== $default ? "(default: <info>$default</info>)" : '';
        $answer = self::read('<comment>' . ucfirst($question) . "</comment>$defaultText ");

        if ('' === $answer) {
            if (null === $default) {
                self::error('A value is required.', false);

                return static::question($question, $default, $validator);
            }

            return $default;
        }

        if ($validator) {
            return $validator($answer) ? $answer : static::question($question, $default, $validator);
        }

        return $answer;
    }

    /**
     * 有次数限制的询问,提出问题
     *   若输入了值且验证成功则返回 输入的结果
     *   否则，会连续询问 $allowed 次， 若仍然错误，退出
     * @param string $question 问题
     * @param null|string $default 默认值
     * @param \Closure $validator (默认验证输入是否为空)自定义回调验证输入是否符合要求; 验证成功返回true 否则 可返回错误消息
     * @example This is an example
     *
     * ```
     * // no default value
     * Interact::loopAsk('please entry you age?', null, function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         Interact::error('Allow the input range is 1-100');
     *         return false;
     *     }
     *
     *     return true;
     * } );
     *
     * // has default value
     * Interact::loopAsk('please entry you age?', 89, function($age)
     * {
     *     if ($age<1 || $age>100) {
     *         Interact::error('Allow the input range is 1-100');
     *         return false;
     *     }
     *
     *     return true;
     * } );
     * ```
     *
     * @param int $times Allow input times
     * @return string
     */
    public static function loopAsk($question, $default = null, \Closure $validator = null, $times = 3)
    {
        return limitedAsk($question, $default, $validator, $times);
    }
    public static function limitedAsk($question, $default = null, \Closure $validator = null, $times = 3)
    {
        if (!$question = trim($question)) {
            self::error('Please provide a question text!', 1);
        }

        $result = false;
        $answer = '';
        $question = ucfirst($question);
        $back = $times = ((int)$times > 6 || $times < 1) ? 3 : (int)$times;
        $defaultText = null !== $default ? "(default: <info>$default</info>)" : '';

        while ($times--) {
            if ($defaultText) {
                $answer = self::read("<comment>{$question}</comment>{$defaultText} ");

                if ('' === $answer) {
                    $answer = $default;
                    $result = true;

                    break;
                }
            } else {
                $num = $times + 1;
                $answer = self::read("<comment>{$question}</comment>\n(You have a [<bold>$num</bold>] chance to enter!) ");
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

            self::write("\n  You've entered incorrectly <danger>$back</danger> times in a row. exit!\n", true, 1);
        }

        return $answer;
    }

    public static $startTime = 0;
    public static $endTime = 0;
    public static $stepWidth = 2;
    public static $step = 0;
    public static $max = 0;
    private static $options = [
        'format' => '[{bar}] {percent:3s}%({current}/{max})',
    ];

    /**
     * @param array $options
     */
    public static function progressBarOptions(array $options)
    {
        self::$options = array_merge(self::$options, $options);
    }

    public static function progressBarStart($max)
    {
        self::$startTime = time();
        self::$max = time();
    }

    public static function progressBarUp($step = 1)
    {

    }

    public static function progressBarEnd()
    {
        self::$endTime = time();

        self::$max = 0;
    }

    /**
     * 读取输入信息
     * @param  string $message 若不为空，则先输出文本
     * @param  bool $nl true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public static function readRow($message = null, $nl = false)
    {
        return self::read($message, $nl);
    }

    public static function read($message = null, $nl = false)
    {
        if ($message) {
            self::write($message, $nl);
        }

        return trim(fgets(STDIN));
    }

} // end class
