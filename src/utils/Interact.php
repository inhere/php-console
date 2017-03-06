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

use inhere\console\ConsoleHelper;

/**
 * Class Interact
 * @package inhere\console\utils
 */
class Interact
{

/////////////////////////////////////////////////////////////////
/// Interactive method (select/confirm/question/loopAsk)
/////////////////////////////////////////////////////////////////

    /**
     * Select one of the options 在多个选项中选择一个
     * @param  string  $description 说明
     * @param  mixed   $options     选项数据
     * e.g
     * [
     *    // option => value
     *    '1' => 'chengdu',
     *    '2' => 'beijing'
     * ]
     * @param  mixed   $default     默认选项
     * @param  bool    $allowExit   有退出选项 默认 true
     * @return string
     */
    public static function select($description, $options, $default = null, $allowExit=true)
    {
        return self::choice($description, $options, $default, $allowExit);
    }
    public static function choice($description, $options, $default = null, $allowExit=true)
    {
        if ( !$description = trim($description) ) {
            self::error('Please provide a description text!', 1);
        }

        self::write("  <comment>$description</comment>");

        $keys = [];
        $optStr = '';
        $options = is_array($options) ? $options : explode(',', $options);

        // If defaut option is error
        if ( null === $default && !isset($options[$default]) ) {
            self::error("The default option [{$default}] don't exists.", true);
        }

        foreach ($options as $key => $value) {
            $keys[] = $key;
            $optStr .= "\n    <info>$key</info>) $value";
        }

        if ($allowExit) {
            $keys[] = 'q';
            $optStr .= "\n    q) quit";
        }

        $defaultText = $default ? "[default:<comment>{$default}</comment>]" : '';
        $r = self::read($optStr . "\n  You choice{$defaultText} : ");

        // error, allow try again once.
        if ( !in_array($r, $keys) ) {
            $r = self::read("Warning! Option <info>$r</info>) don't exists! Please entry again! : ");
        }

        // exit
        if ( $r === 'q' ) {
            self::write("\n  Quit,ByeBye.", true, true);
        }

        // error
        if ( !in_array($r, $keys) ) {
            if ( null === $default ) {
                self::write("\n  Select error. Quit,ByeBye.", true, true);
            }

            $r = $default;
        }

        return $r;
    }

    /**
     * 确认, 发出信息要求确认
     * @param string $question 发出的信息
     * @param bool   $default  Default value
     * @return bool
     */
    public static function confirm($question, $default = true)
    {
        if ( !$question = trim($question) ) {
            self::error('Please provide a question text!', 1);
        }

        $question = ucfirst($question);
        $defaultText = (bool)$default ? 'yes' : 'no';

        $message = " <comment>$question ?</comment>\n Please confirm (yes|no) [default:<info>$defaultText</info>]: ";
        $answer = self::read($message);

        return $answer ? !strncasecmp($answer, 'y', 1) : (bool)$default;
    }

    /**
     * 询问，提出问题；返回 输入的结果
     * @param string      $question   问题
     * @param null|string $default    默认值
     * @param \Closure    $validator  The validate callback. It must return bool.
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
        if ( !$question = trim($question) ) {
            self::error('Please provide a question text!', 1);
        }

        $defaultText = null !== $default ? "(default: <info>$default</info>)" : '';
        $answer = self::read( "<comment>" . ucfirst($question) . "</comment>$defaultText " );

        if ( '' === $answer ) {
            if ( null === $default) {
                self::error('A value is required.', false);

                return static::question($question, $default, $validator);
            }

            return $default;
        }

        if ( $validator ) {
            return $validator($answer) ? $answer : static::question($question, $default, $validator);
        }

        return $answer;
    }

    /**
     * 有次数限制的询问,提出问题
     *   若输入了值且验证成功则返回 输入的结果
     *   否则，会连续询问 $allowed 次， 若仍然错误，退出
     * @param string      $question 问题
     * @param null|string $default    默认值
     * @param \Closure    $validator (默认验证输入是否为空)自定义回调验证输入是否符合要求; 验证成功返回true 否则 可返回错误消息
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
    public static function loopAsk($question, $default = null, \Closure $validator = null, $times=3)
    {
        if ( !$question = trim($question) ) {
            self::error('Please provide a question text!', 1);
        }

        $result = false;
        $answer = '';
        $question = ucfirst($question);
        $back = $times = ((int)$times > 6 || $times < 1) ? 3 : (int)$times;
        $defaultText = null !== $default ? "(default: <info>$default</info>)" : '';

        while ($times--) {
            if ( $defaultText ) {
                $answer = self::read("<comment>{$question}</comment>{$defaultText} ");

                if ( '' === $answer ) {
                    $answer = $default;
                    $result = true;

                    break;
                }
            } else {
                $num = $times + 1;
                $answer = self::read("<comment>{$question}</comment>\n(You have a [<bold>$num</bold>] chance to enter!) ");
            }

            // If setting verify callback
            if ($validator && ($result = $validator($answer)) === true ) {
                break;
            }

            // no setting verify callback
            if ( !$validator && $answer !== '') {
                $result = true;

                break;
            }
        }

        if ( !$result ) {

            if ( null !== $default ) {
                return $default;
            }

            self::write("\n  You've entered incorrectly <danger>$back</danger> times in a row. exit!\n", true, 1);
        }

        return $answer;
    }

/////////////////////////////////////////////////////////////////
/// Output Format Message(title/section/helpPanel/panel/table)
/////////////////////////////////////////////////////////////////

    /**
     * @param string $msg   The title message
     * @param int    $width The title section width
     */
    public static function title($msg, $width = 50)
    {
        self::section($msg, $width, '=');
    }

    /**
     * @param string $msg The section message
     * @param int $width The section width
     * @param string $char
     */
    public static function section($msg, $width = 50, $char = '-')
    {
        $msg = ucwords(trim($msg));
        $msgLength = mb_strlen($msg, 'UTF-8');
        $width = is_int($width) && $width > 10 ? $width : 50;

        $indentSpace = str_pad(' ', ceil($width/2) - ceil($msgLength/2), ' ');
        $charStr = str_pad($char, $width, $char);

        self::write("  {$indentSpace}{$msg}   \n  {$charStr}\n");
    }

    /**
     * Show console help message
     * @param  string $usage    The usage message text. e.g 'command [options] [arguments]'
     * @param  array  $commands The command list
     * e.g
     * [
     *     // command => description
     *     'start'    => 'Start the app server',
     *     ... ...
     * ]
     * @param  array  $options The option list
     * e.g
     * [
     *     // option    => description
     *     '-d'         => 'Run the server on daemonize.(default: <comment>false</comment>)',
     *     '-h, --help' => 'Display this help message'
     *     ... ...
     * ]
     * @param  array  $examples The command usage example. e.g 'php server.php {start|reload|restart|stop} [-d]'
     * @param  string $description The description text. e.g 'Composer version 1.3.2'
     * @param  bool   $showAfterQuit Show help after quit
     */
    public static function consoleHelp($usage, $commands = [], $options = [], $examples = [], $description = '', $showAfterQuit = true)
    {
        self::helpPanel($usage, $commands, $options, $examples, $description, $showAfterQuit);
    }
    public static function helpPanel($usage, $commands = [], $options = [], $examples = [], $description = '', $showAfterQuit = true)
    {
        // description
        if ( $description ) {
            self::write($description . PHP_EOL);
        }

        // usage
        self::write("<comment>Usage</comment>:\n  {$usage}\n");

        // options list
        if ( $options ) {
            // translate array to string
            if ( is_array($options)) {
                $options = ConsoleHelper::spliceKeyValue($options, [
                    'leftChar' => '  ',
                    'keyStyle' => 'info',
                ]);
            }

            if ( is_string($options) ) {
                self::write("<comment>Options</comment>:\n{$options}");
            }
        }

        // command list
        if ( $commands ) {
            // translate array to string
            if ( is_array($commands)) {
                $commands = ConsoleHelper::spliceKeyValue($commands, [
                    'leftChar' => '  ',
                    'keyStyle' => 'info',
                ]);
            }

            if ( is_string($commands) ) {
                self::write("<comment>Commands</comment>:\n{$commands}");
            }
        }

        // examples list
        if ( $examples ) {
            $examples = is_array($examples) ? implode(PHP_EOL . '  ', $examples) : (string)$examples;
            self::write("<comment>Examples</comment>:\n  {$examples}\n");
        }

        if ($showAfterQuit) {
            exit(0);
        }
    }

    /**
     * Show information data panel
     * @param  mixed  $data
     * @param  string $title
     * @param  string $borderChar
     * @return void
     */
    public static function panel($data, $title='Information Panel', $borderChar = '*')
    {
        $data = is_array($data) ? array_filter($data) : [trim($data)];
        $title = trim($title);

        $panelData = []; // [ 'label' => 'value' ]
        $labelMaxWidth = 0; // if label exists, label max width
        $valueMaxWidth = 0; // value max width

        foreach ($data as $label => $value) {
            // label exists
            if ( !is_numeric($label) ) {
                $width = mb_strlen($label, 'UTF-8');
                $labelMaxWidth = $width > $labelMaxWidth ? $width : $labelMaxWidth;
            }

            // translate array to string
            if ( is_array($value) ) {
                $temp = '';

                foreach ($value as $key => $val) {
                    if (is_bool($val)) {
                        $val = $val ? 'True' : 'False';
                    } else {
                        $val = (string)$val;
                    }

                    $temp .= (!is_numeric($key) ? "$key: " : '') . "<info>$val</info>, ";
                }

                $value = rtrim($temp, ' ,');
            } else if (is_bool($value)) {
                $value = $value ? 'True' : 'False';
            } else {
                $value = trim((string)$value);
            }

            // get value width
            $value = trim($value);
            $width = mb_strlen(strip_tags($value), 'UTF-8'); // must clear style tag
            $valueMaxWidth = $width > $valueMaxWidth ? $width : $valueMaxWidth;

            $panelData[$label] = $value;
        }

        $panelWidth = $labelMaxWidth + $valueMaxWidth;

        // output title
        if ($title) {
            $title = ucwords($title);
            $titleLength = mb_strlen($title, 'UTF-8');
            $panelWidth = $panelWidth > $titleLength ? $panelWidth : $titleLength;
            $indentSpace = str_pad(' ', ceil($panelWidth/2) - ceil($titleLength/2) + 2*2, ' ');
            self::write("  {$indentSpace}<bold>{$title}</bold>");
        }

        // output panel top border
        if ($borderChar) {
            $border = str_pad($borderChar, $panelWidth + (3*3), $borderChar);
            self::write('  ' . $border);
        }

        // output panel body
        $panelStr = ConsoleHelper::spliceKeyValue($panelData, [
            'leftChar'    => "  $borderChar ",
            'sepChar'     => ' | ',
            'keyMaxWidth' => $labelMaxWidth,
        ]);

        // already exists "\n"
        self::write($panelStr, false);

        // output panel bottom border
        if (isset($border)) {
            self::write("  $border\n");
        }

        unset($data, $panelData);
    }

    /**
     * 表格数据信息展示
     * @param  array $data
     * @param  string $title
     * @param  bool $showBorder
     * @return void
     */
    public static function table(array $data, $title='Info List', $showBorder = true)
    {
        $rowIndex = 0;
        $head = $table = [];
        $info = [
            'rowCount'  => count($data),
            'columnCount' => 0,     // how many column in the table.
            'columnMaxWidth' => [], // table column max width
            'tableWidth' => 0,      // table width. equals to all max column width's sum.
        ];

        // parse table data
        foreach ($data as $row) {
            // collection all field name
            if ($rowIndex === 0) {
                $head = array_keys($row);
                $info['columnCount'] = count($row);

                foreach ($head as $index => $name) {
                    $info['columnMaxWidth'][$index] = mb_strlen($name, 'UTF-8');
                }
            }

            $colIndex = 0;

            foreach ($row as $value) {
                // collection column max width
                if ( isset($info['columnMaxWidth'][$colIndex]) ) {
                    $colWidth = mb_strlen($value, 'UTF-8');

                    // If current column width gt old column width. override old width.
                    if ($colWidth > $info['columnMaxWidth'][$colIndex]) {
                        $info['columnMaxWidth'][$colIndex] = $colWidth;
                    }
                } else {
                    $info['columnMaxWidth'][$colIndex] = mb_strlen($value, 'UTF-8');
                }

                $colIndex++;
            }

            $rowIndex++;
        }

        $tableWidth = $info['tableWidth'] = array_sum($info['columnMaxWidth']);
        $columnCount = $info['columnCount'];

        // output title
        if ($title) {
            $title = ucwords(trim($title));
            $titleLength = mb_strlen($title, 'UTF-8');
            $indentSpace = str_pad(' ', ceil($tableWidth/2) - ceil($titleLength/2) + ($columnCount*2), ' ');
            self::write("  {$indentSpace}<bold>{$title}</bold>");
        }

        // output table top border
        if ($showBorder) {
            $border = str_pad('-', $tableWidth + ($columnCount*3) + 2, '-');
            self::write('  ' . $border);
        }

        // output table head
        $headStr = '  | ';
        foreach ($head as $index => $name) {
            $colMaxWidth = $info['columnMaxWidth'][$index];
            $name = str_pad($name, $colMaxWidth, ' ');
            $headStr .= " {$name} |";
        }

        self::write($headStr);

        // border: split head and body
        if (isset($border)) {
            self::write('  ' . $border);
        }

        $rowIndex = 0;

        // output table info
        foreach ($data as $row) {
            $colIndex = 0;
            $rowStr = '  | ';

            foreach ($row as $value) {
                $colMaxWidth = $info['columnMaxWidth'][$colIndex];
                $value = str_pad($value, $colMaxWidth, ' ');
                $rowStr .= " <info>{$value}</info> |";
                $colIndex++;
            }

            self::write("{$rowStr}");

            $rowIndex++;
        }

        // output table bottom border
        if (isset($border)) {
            self::write('  ' . $border);
        }

        echo "\n";
        unset($data);
    }

    /**
     * @param mixed         $messages
     * @param string|null   $type
     * @param string        $style
     * @param int|boolean   $quit  If is int, settin it is exit code.
     */
    public static function block($messages, $type = null, $style='default', $quit = false)
    {
        $messages = is_array($messages) ? array_values($messages) : array($messages);

        // add type
        if (null !== $type) {
            $messages[0] = sprintf('[%s] %s', strtoupper($type), $messages[0]);
        }

        $text = implode(PHP_EOL, $messages);
        $color = static::getColor();

        if (is_string($style) && $color->hasStyle($style)) {
            $text = "<{$style}>{$text}</{$style}>";
        }

        // $this->write($text);
        self::write($text, true, $quit);
    }
    public static function primary($messages, $quit = false)
    {
        static::block($messages, 'IMPORTANT', 'primary', $quit);
    }
    public static function success($messages, $quit = false)
    {
        static::block($messages, 'SUCCESS', 'success', $quit);
    }
    public static function info($messages, $quit = false)
    {
        static::block($messages, 'INFO', 'info', $quit);
    }
    public static function warning($messages, $quit = false)
    {
        static::block($messages, 'WARNING', 'warning', $quit);
    }
    public static function danger($messages, $quit = false)
    {
        static::block($messages, 'DANGER', 'danger', $quit);
    }
    public static function error($messages, $quit = false)
    {
        static::block($messages, 'ERROR', 'error', $quit);
    }
    public static function notice($messages, $quit = false)
    {
        static::block($messages, 'NOTICE', 'comment', $quit);
    }

/////////////////////////////////////////////////////////////////
/// Helper Method
/////////////////////////////////////////////////////////////////


    /**
     * @var Color
     */
    private static $color;

    public static function getColor()
    {
        if (!static::$color) {
            static::$color = new Color();
        }

        return static::$color;
    }

    /**
     * 读取输入信息
     * @param  string $message  若不为空，则先输出文本
     * @param  bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
     * @return string
     */
    public static function readRow($message = null, $nl = false)
    {
        return self::read($message, $nl);
    }
    public static function read($message = null, $nl = false)
    {
        if ( $message ) {
            self::write($message, $nl);
        }

        return trim(fgets(STDIN));
    }

    /**
     * Write a message to standard output stream.
     * @param  string|array $messages    Output message
     * @param  boolean      $nl          true 会添加换行符 false 原样输出，不添加换行符
     * @param  int|boolean  $quit        If is int, settin it is exit code.
     */
    public static function write($messages, $nl = true, $quit = false)
    {
        // if ( is_array($messages) ) {
        //     $messages = implode( $nl ? PHP_EOL : '', $messages );
        // }

        $messages = static::getColor()->format($messages);

        fwrite(STDOUT, $messages . ($nl ? PHP_EOL : ''));

        if ( is_int($quit) || true === $quit) {
            $code = true === $quit ? 0 : $quit;
            exit($code);
        }

        fflush(STDOUT);
    }


} // end class
