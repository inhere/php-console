<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-06-20
 * Time: 15:10
 */

namespace Inhere\Console\Concern;

use Closure;
use Generator;
use Inhere\Console\Component\Formatter\HelpPanel;
use Inhere\Console\Component\Formatter\MultiList;
use Inhere\Console\Component\Formatter\Panel;
use Inhere\Console\Component\Formatter\Section;
use Inhere\Console\Component\Formatter\SingleList;
use Inhere\Console\Component\Formatter\Table;
use Inhere\Console\Component\Formatter\Title;
use Toolkit\Cli\Style;
use Inhere\Console\Console;
use Inhere\Console\Util\Interact;
use Inhere\Console\Util\Show;
use LogicException;
use Toolkit\Stdlib\Php;
use function array_merge;
use function json_encode;
use function method_exists;
use function sprintf;
use function strpos;
use function substr;

/**
 * Class FormatOutputAwareTrait
 *
 * @package Inhere\Console\Traits
 *
 * @method int info($messages, $quit = false)
 * @method int note($messages, $quit = false)
 * @method int notice($messages, $quit = false)
 * @method int success($messages, $quit = false)
 * @method int primary($messages, $quit = false)
 * @method int warning($messages, $quit = false)
 * @method int danger($messages, $quit = false)
 * @method int error($messages, $quit = false)
 *
 * @method int liteInfo($messages, $quit = false)
 * @method int liteNote($messages, $quit = false)
 * @method int liteNotice($messages, $quit = false)
 * @method int liteSuccess($messages, $quit = false)
 * @method int litePrimary($messages, $quit = false)
 * @method int liteWarning($messages, $quit = false)
 * @method int liteDanger($messages, $quit = false)
 * @method int liteError($messages, $quit = false)
 *
 * @method padding(array $data, string $title = null, array $opts = [])
 *
 * @method splitLine(string $title, string $char = '-', int $width = 0)
 * @method spinner($msg = '', $ended = false)
 * @method loading($msg = 'Loading ', $ended = false)
 * @method pending($msg = 'Pending ', $ended = false)
 * @method pointing($msg = 'handling ', $ended = false)
 *
 * @method Generator counterTxt($msg = 'Pending ', $ended = false)
 *
 * @method confirm(string $question, bool $default = true): bool
 * @method unConfirm(string $question, bool $default = true): bool
 * @method select(string $description, $options, $default = null, bool $allowExit = true): string
 * @method checkbox(string $description, $options, $default = null, bool $allowExit = true): array
 * @method ask(string $question, string $default = '', Closure $validator = null): string
 * @method askPassword(string $prompt = 'Enter Password:'): string
 */
trait FormatOutputAwareTrait
{
    /**
     * @inheritdoc
     * @see Console::write()
     */
    public function write($messages, $nl = true, $quit = false, array $opts = []): int
    {
        return Console::write($messages, $nl, $quit, array_merge([
            'flush'  => true,
            'stream' => $this->outputStream,
        ], $opts));
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public function writef(string $format, ...$args): void
    {
        Console::printf($format, ...$args);
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public function printf(string $format, ...$args): void
    {
        Console::printf($format, ...$args);
    }

    /**
     * @param string|mixed $text
     * @param bool         $quit
     * @param array        $opts
     *
     * @return int
     */
    public function writeln($text, $quit = false, array $opts = []): int
    {
        return Console::writeln($text, $quit, $opts);
    }

    /**
     * @param string|mixed $text
     * @param bool         $quit
     * @param array        $opts
     *
     * @return int
     */
    public function println($text, $quit = false, array $opts = []): int
    {
        return Console::writeln($text, $quit, $opts);
    }

    /**
     * @param string|mixed $text
     * @param bool         $nl
     * @param bool         $quit
     * @param array        $opts
     *
     * @return int
     */
    public function writeRaw($text, bool $nl = true, $quit = false, array $opts = []): int
    {
        return Console::writeRaw($text, $nl, $quit, $opts);
    }

    /**
     * @param string $text
     * @param string $tag
     *
     * @return int
     */
    public function colored(string $text, string $tag = 'info'): int
    {
        return $this->writeln(sprintf('<%s>%s</%s>', $tag, $text, $tag));
    }

    /**
     * @param array|mixed $messages
     * @param string      $type
     * @param string      $style
     * @param bool        $quit
     *
     * @return int
     */
    public function block($messages, string $type = 'MESSAGE', string $style = Style::NORMAL, $quit = false): int
    {
        return Show::block($messages, $type, $style, $quit);
    }

    /**
     * @param array|mixed $messages
     * @param string      $type
     * @param string      $style
     * @param bool        $quit
     *
     * @return int
     */
    public function liteBlock($messages, string $type = 'MESSAGE', string $style = Style::NORMAL, $quit = false): int
    {
        return Show::liteBlock($messages, $type, $style, $quit);
    }

    /**
     * @param string $title
     * @param array  $opts
     */
    public function title(string $title, array $opts = []): void
    {
        Title::show($title, $opts);
    }

    /**
     * @param string       $title
     * @param string|array $body The section body message
     * @param array        $opts
     */
    public function section(string $title, $body, array $opts = []): void
    {
        Section::show($title, $body, $opts);
    }

    /**
     * @param array|mixed $data
     * @param string      $title
     * @param array       $opts
     */
    public function aList($data, string $title = 'Information', array $opts = []): void
    {
        SingleList::show($data, $title, $opts);
    }

    /**
     * @param array $data
     * @param array $opts
     */
    public function multiList(array $data, array $opts = []): void
    {
        MultiList::show($data, $opts);
    }

    /**
     * @param array $data
     * @param array $opts
     */
    public function mList(array $data, array $opts = []): void
    {
        MultiList::show($data, $opts);
    }

    /**
     * @param array $config
     */
    public function helpPanel(array $config): void
    {
        HelpPanel::show($config);
    }

    /**
     * @param array  $data
     * @param string $title
     * @param array  $opts
     */
    public function panel(array $data, string $title = 'Information panel', array $opts = []): void
    {
        Panel::show($data, $title, $opts);
    }

    /**
     * @param array  $data
     * @param string $title
     * @param array  $opts
     */
    public function table(array $data, string $title = 'Data Table', array $opts = []): void
    {
        Table::show($data, $title, $opts);
    }

    /**
     * @param int    $total
     * @param string $msg
     * @param string $doneMsg
     *
     * @return Generator
     */
    public function progressTxt(int $total, string $msg, string $doneMsg = ''): Generator
    {
        return Show::progressTxt($total, $msg, $doneMsg);
    }

    /**
     * @inheritdoc
     * @see Show::progressBar()
     */
    public function progressBar($total, array $opts = []): Generator
    {
        return Show::progressBar($total, $opts);
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return int
     * @throws LogicException
     */
    public function __call($method, array $args = [])
    {
        $map = Show::getBlockMethods(false);

        if (isset($map[$method])) {
            $msg   = $args[0];
            $quit  = $args[1] ?? false;
            $style = $map[$method];

            if (0 === strpos($method, 'lite')) {
                $type = substr($method, 4);
                return Show::liteBlock($msg, $type === 'Primary' ? 'IMPORTANT' : $type, $style, $quit);
            }

            return Show::block($msg, $style === 'primary' ? 'IMPORTANT' : $style, $style, $quit);
        }

        if (method_exists(Show::class, $method)) {
            return Show::$method(...$args);
        }

        // interact methods
        if (method_exists(Interact::class, $method)) {
            return Interact::$method(...$args);
        }

        throw new LogicException("Call a not exists method: $method of the " . static::class);
    }

    /**
     * @param mixed $data
     * @param bool  $echo
     * @param int   $flags
     *
     * @return int|string
     */
    public function json(
        $data,
        bool $echo = true,
        int $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
        $string = json_encode($data, $flags);

        if ($echo) {
            return Console::write($string);
        }

        return $string;
    }

    /**
     * @param mixed ...$vars
     */
    public function dump(...$vars): void
    {
        Console::write(Php::dumpVars(...$vars));
    }

    /**
     * @param mixed ...$vars
     */
    public function prints(...$vars): void
    {
        Console::write(Php::printVars(...$vars));
    }
}
