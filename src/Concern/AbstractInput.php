<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Concern;

use Inhere\Console\Contract\InputInterface;
use Toolkit\Cli\Helper\FlagHelper;
use Toolkit\PFlag\FlagsParser;
use function array_map;
use function array_shift;
use function basename;
use function chdir;
use function getcwd;
use function implode;
use function is_string;
use function preg_match;

/**
 * Class AbstractInput
 *
 * @package Inhere\Console\Concern
 */
abstract class AbstractInput implements InputInterface
{
    /**
     * Global flags parser
     *
     * @var FlagsParser|null
     */
    protected ?FlagsParser $gfs = null;

    /**
     * Command flags parser
     *
     * @var FlagsParser|null
     */
    protected ?FlagsParser $fs = null;

    /**
     * @var string
     */
    protected string $pwd = '';

    /**
     * The bin script file
     * e.g `./bin/app` OR `bin/cli.php`
     *
     * @var string
     */
    protected string $scriptFile = '';

    /**
     * The bin script name
     * e.g `app` OR `cli.php`
     *
     * @var string
     */
    protected string $scriptName = '';

    /**
     * the command name(Is first argument)
     * e.g `git` OR `start`
     *
     * @var string
     */
    protected string $command = '';

    /**
     * eg `./examples/app home:useArg status=2 name=john arg0 -s=test --page=23`
     *
     * @var string
     */
    protected string $fullScript;

    /**
     * Raw input argv data.
     * - first element is script file
     *
     * @var array
     */
    protected array $tokens;

    /**
     * Same the $tokens but no $script
     *
     * @var array
     */
    protected array $flags = [];

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->getTokenString();
    }

    /**
     * @param bool $escape
     *
     * @return string
     */
    public function getTokenString(bool $escape = true): string
    {
        if (!$this->tokens) {
            return '';
        }

        if ($escape) {
            $tokens = array_map([$this, 'tokenEscape'], $this->tokens);
            return implode(' ', $tokens);
        }

        return implode(' ', $this->tokens);
    }

    /**
     * @param bool $escape
     *
     * @return string
     */
    public function getInputUri(bool $escape = true): string
    {
        return $this->getTokenString($escape);
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
     * @param array $rawFlags
     */
    protected function collectInfo(array $rawFlags): void
    {
        $this->getPwd();
        if (!$rawFlags) {
            return;
        }

        $this->tokens = $rawFlags;

        // first is bin file
        if (isset($rawFlags[0]) && is_string($rawFlags[0])) {
            $this->scriptFile = array_shift($rawFlags);

            // bin name
            $this->scriptName = basename($this->scriptFile);
        }

        $this->flags = $rawFlags; // no script

        // full script
        $this->fullScript = implode(' ', $rawFlags);
    }

    /**
     * @return bool
     */
    public function isInteractive(): bool
    {
        return false;
    }

    /***********************************************************************************
     * getter/setter
     ***********************************************************************************/

    /**
     * @param bool $refresh
     *
     * @return string
     */
    public function getPwd(bool $refresh = false): string
    {
        if (!$this->pwd || $refresh) {
            $this->pwd = (string)getcwd();
        }

        return $this->pwd;
    }

    /**
     * @param bool $refresh
     *
     * @return string
     */
    public function getWorkDir(bool $refresh = false): string
    {
        return $this->getPwd($refresh);
    }

    /**
     * @param string $workdir
     *
     * @return void
     */
    public function chWorkDir(string $workdir): void
    {
        if ($workdir) {
            chdir($workdir);
            $this->getPwd(true);
        }
    }

    /**
     * @param string $pwd
     */
    public function setPwd(string $pwd): void
    {
        $this->pwd = $pwd;
    }

    /**
     * @return string
     */
    public function getScriptFile(): string
    {
        return $this->scriptFile;
    }

    /**
     * @return string
     */
    public function getScriptPath(): string
    {
        return $this->scriptFile;
    }

    /**
     * @param string $scriptFile
     */
    public function setScriptFile(string $scriptFile): void
    {
        if ($scriptFile) {
            $this->scriptFile = $scriptFile;
            // update scriptName
            $this->scriptName = basename($scriptFile);
        }
    }

    /**
     * @return string
     */
    public function getScriptName(): string
    {
        return $this->scriptName;
    }

    /**
     * @return string
     */
    public function getBinName(): string
    {
        return $this->scriptName;
    }

    /**
     * @return string
     */
    public function getCommand(): string
    {
        return $this->command;
    }

    /**
     * @param string $command
     */
    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    /**
     * @param bool $withBinName
     *
     * @return string
     */
    public function getFullScript(bool $withBinName = false): string
    {
        if ($withBinName) {
            return $this->getBinName() . ' ' . $this->fullScript;
        }

        return $this->fullScript;
    }

    /**
     * @param string $fullScript
     */
    public function setFullScript(string $fullScript): void
    {
        $this->fullScript = $fullScript;
    }

    /**
     * @return array
     */
    public function getFlags(): array
    {
        return $this->flags;
    }

    /**
     * @param array $flags
     */
    public function setFlags(array $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * @return array
     */
    public function getRawFlags(): array
    {
        return $this->tokens;
    }

    /**
     * @return array
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * @param array $tokens
     */
    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
        $this->collectInfo($tokens);
    }

    /**
     * @return FlagsParser
     */
    public function getGfs(): FlagsParser
    {
        return $this->gfs;
    }

    /**
     * @param FlagsParser $gfs
     */
    public function setGfs(FlagsParser $gfs): void
    {
        $this->gfs = $gfs;
    }

    /**
     * @return FlagsParser
     */
    public function getFs(): FlagsParser
    {
        return $this->fs;
    }

    /**
     * @param FlagsParser $fs
     */
    public function setFs(FlagsParser $fs): void
    {
        $this->fs = $fs;
    }
}
