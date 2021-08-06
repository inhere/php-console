<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 19:23
 */

namespace Inhere\Console\IO;

use Toolkit\Cli\Cli;
use Toolkit\Cli\Flags;
use function array_map;
use function array_shift;
use function basename;
use function fgets;
use function fwrite;
use function implode;
use function is_string;
use function preg_match;
use function trim;

/**
 * Class Input - The input information. by parse global var $argv.
 *
 * @package Inhere\Console\IO
 */
class Input extends AbstractInput
{
    /**
     * The real command ID(group:command)
     * e.g `http:start`
     *
     * @var string
     */
    protected $commandId = '';

    /**
     * Default is STDIN
     *
     * @var resource
     */
    protected $inputStream;

    /**
     * Input constructor.
     *
     * @param null|array $args
     * @param bool       $parsing
     */
    public function __construct(array $args = null, bool $parsing = true)
    {
        if (null === $args) {
            $args = $_SERVER['argv'];
        }

        $this->inputStream = Cli::getInputStream();
        $this->collectInfo($args);

        if ($parsing) {
            $this->doParse($this->flags);
        }
    }

    /**
     * @param array $args
     */
    protected function collectInfo(array $args): void
    {
        $this->getPwd();
        if (!$args) {
            return;
        }

        $this->tokens = $args;

        // first is bin file
        if (isset($args[0]) && is_string($args[0])) {
            $this->script = array_shift($args);

            // bin name
            $this->scriptName = basename($this->script);
        }

        $this->flags = $args; // no script

        // full script
        $this->fullScript = implode(' ', $args);
    }

    /**
     * re-parse args/opts from given args
     *
     * @param array $args
     */
    public function parse(array $args): void
    {
        $this->doParse($args);
    }

    /**
     * @param array $args
     */
    protected function doParse(array $args): void
    {
        [
            $this->args,
            $this->sOpts,
            $this->lOpts
        ] = Flags::parseArgv($args);

        // find command name
        $this->command = $this->findCommandName();
    }

    public function resetInputStream(): void
    {
        $this->inputStream = Cli::getInputStream();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $tokens = array_map([$this, 'tokenEscape'], $this->tokens);

        return implode(' ', $tokens);
    }

    /**
     * @param string $token
     *
     * @return string
     */
    protected function tokenEscape(string $token): string
    {
        if (preg_match('{^(-[^=]+=)(.+)}', $token, $match)) {
            return $match[1] . Flags::escapeToken($match[2]);
        }

        if ($token && $token[0] !== '-') {
            return Flags::escapeToken($token);
        }

        return $token;
    }

    /**
     * Read input information
     *
     * @param string $question 若不为空，则先输出文本消息
     * @param bool   $nl       true 会添加换行符 false 原样输出，不添加换行符
     *
     * @return string
     */
    public function read(string $question = '', bool $nl = false): string
    {
        if ($question) {
            fwrite(Cli::getOutputStream(), $question . ($nl ? "\n" : ''));
        }

        return trim((string)fgets($this->inputStream));
    }

    /***********************************************************************************
     * getter/setter
     ***********************************************************************************/

    /**
     * @return string
     */
    public function getBinWithCommand(): string
    {
        return $this->scriptName . ' ' . $this->getCommandPath();
    }

    /**
     * @return string
     */
    public function getFullCommand(): string
    {
        return $this->script . ' ' . $this->getCommandPath();
    }

    /**
     * @return resource
     */
    public function getInputStream()
    {
        return $this->inputStream;
    }

    /**
     * Get command ID e.g `http:start`
     *
     * @return  string
     */
    public function getCommandId(): string
    {
        return $this->commandId;
    }

    /**
     * Set command ID e.g `http:start`
     *
     * @param string $commandId e.g `http:start`
     *
     * @return void
     */
    public function setCommandId(string $commandId): void
    {
        $this->commandId = $commandId;
    }
}
