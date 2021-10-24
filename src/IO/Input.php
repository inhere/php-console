<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO;

use Toolkit\Cli\Cli;
use Toolkit\Cli\Flags;
use Toolkit\Cli\Helper\FlagHelper;
use Toolkit\FsUtil\File;
use function array_map;
use function fwrite;
use function implode;
use function preg_match;

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
     */
    public function __construct(array $args = null)
    {
        if (null === $args) {
            $args = $_SERVER['argv'];
        }

        $this->inputStream = Cli::getInputStream();
        $this->collectInfo($args);
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
            return $match[1] . FlagHelper::escapeToken($match[2]);
        }

        if ($token && $token[0] !== '-') {
            return FlagHelper::escapeToken($token);
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
    public function readln(string $question = '', bool $nl = false): string
    {
        if ($question) {
            fwrite($this->inputStream, $question . ($nl ? "\n" : ''));
        }

        return File::streamFgets($this->inputStream);
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
        return $this->scriptFile . ' ' . $this->getCommandPath();
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
