<?php declare(strict_types=1);

namespace Inhere\Console\Util;

use Closure;
use Exception;
use RuntimeException;
use Toolkit\Cli\Cli;
use Toolkit\Stdlib\Json;
use Toolkit\Sys\Sys;
use function explode;
use function file_exists;
use function file_get_contents;
use function is_dir;
use function is_file;
use function putenv;
use function random_int;
use function strpos;

/**
 * Class PhpDevServe
 *
 * @package Inhere\Kite\Common
 */
class PhpDevServe
{
    public const PHP_BIN  = 'php';
    public const IDX_FILE = 'index.php';
    public const SVR_ADDR = '127.0.0.1:8080';

    /**
     * The php binary file
     *
     * @var string
     */
    protected $phpBin = self::PHP_BIN;

    /**
     * The document root dir for server
     *
     * @var string
     */
    protected $docRoot = '';

    /**
     * The entry file for server. e.g web/index.php
     * NOTICE: if set the entry file, will need handle static file access.
     *
     * @var string
     */
    protected $entryFile = '';

    /**
     * The http server address. e.g 127.0.0.1:8552
     *
     * @var string
     */
    protected $serveAddr = '';

    /**
     * Can custom message for print before start server.
     * It must return an message string.
     *
     * @var callable
     */
    protected $beforeStart;

    /**
     * The IDEA http-client env json file data.
     *
     * @var array
     */
    private $hceData = [];

    /**
     * @var string
     */
    private $hceEnv = '';

    /**
     * @var array
     */
    private $envVars = [];

    /**
     * @param string $serveAddr
     * @param string $docRoot
     * @param string $entryFile
     *
     * @return self
     */
    public static function new(string $serveAddr, string $docRoot = '', string $entryFile = ''): self
    {
        return new self($serveAddr, $docRoot, $entryFile);
    }

    /**
     * @return string
     */
    public static function findPhpBin(): string
    {
        // `which php` output: "/usr/local/bin/php"
        // `type php` output: "php is /usr/local/bin/php"
        [$ok, $ret,] = Sys::run('type php');

        $phpBin = '';
        if ($ok === 0) {
            $nodes  = explode('/', $ret, 2);
            $phpBin = $nodes[1] ?? '';
        }

        return $phpBin;
    }

    /**
     * Class constructor.
     *
     * @param string $serveAddr
     * @param string $docRoot
     * @param string $entryFile
     */
    public function __construct(string $serveAddr, string $docRoot, string $entryFile = '')
    {
        $this->serveAddr = $serveAddr ?: self::SVR_ADDR;
        $this->setDocRoot($docRoot);
        $this->setEntryFile($entryFile);
    }

    /**
     * @param Closure $fn
     *
     * @return $this
     */
    public function config(Closure $fn): self
    {
        $fn($this);
        return $this;
    }

    /**
     * @param array $envVars
     *
     * @return $this
     */
    public function setEnvVars(array $envVars): self
    {
        $this->envVars = $envVars;
        return $this;
    }

    /**
     * start and listen serve
     *
     * @throws Exception
     */
    public function listen(): void
    {
        if ($fn = $this->beforeStart) {
            $fn($this);
        } else {
            $this->printDefaultMessage();
        }

        $this->putEnvVars();

        $command = $this->getCommand();

        Cli::write("<cyan>></cyan> <darkGray>$command</darkGray>");
        Sys::execute($command);
    }

    /**
     * @throws Exception
     */
    protected function printDefaultMessage(): void
    {
        // $version = PHP_VERSION;
        $workDir = (string)getcwd();
        $svrAddr = $this->getServerAddr();
        $docRoot = $workDir;
        if ($this->docRoot) {
            $docRoot = $this->docRoot;
            $docRoot = Helper::isAbsPath($docRoot) ? $docRoot : $workDir . '/' . $docRoot;
        }

        Cli::writeln([
            "PHP Development Server start listening on <info>http://$svrAddr</info>",
            "Document root is <comment>$docRoot</comment>",
            'You can use <comment>CTRL + C</comment> to stop run.',
        ]);
    }

    protected function putEnvVars(): void
    {
        foreach ($this->envVars as $name => $val) {
            $_SERVER[$name] = (string)$val;
            putenv("$name=$val");
        }
    }

    /**
     * build full command line string
     *
     * @param bool $checkEnv
     *
     * @return string
     * @throws Exception
     */
    public function getCommand(bool $checkEnv = true): string
    {
        $phpBin  = $this->getPhpBin();
        $svrAddr = $this->getServerAddr();
        // command eg: "php -S 127.0.0.1:8080 -t web web/index.php";
        $command = "$phpBin -S $svrAddr";

        if ($docRoot = $this->docRoot) {
            if ($checkEnv && !is_dir($docRoot)) {
                throw new RuntimeException("the document root is not exists. path: $docRoot");
            }

            $command .= " -t $docRoot";
        }

        if ($entryFile = $this->getEntryFile()) {
            if ($checkEnv && !is_file($entryFile)) {
                throw new RuntimeException("the entry file is not exists. path: $entryFile");
            }

            $command .= " $entryFile";
        }

        return $command;
    }

    /**
     * @param string $hceFile
     * @param bool   $mustLoad
     *
     * @return bool
     */
    public function loadHceFile(string $hceFile, bool $mustLoad = false): bool
    {
        if (!file_exists($hceFile)) {
            if ($mustLoad) {
                throw new RuntimeException('the http-client env json file not exists. file: ' . $hceFile);
            }

            return false;
        }

        $jsonString = file_get_contents($hceFile);

        // load data
        $this->hceData = Json::decode($jsonString, true);
        return true;
    }

    /**
     * @param string $envName
     *
     * @return $this
     */
    public function useHceEnv(string $envName): self
    {
        if (!isset($this->hceData[$envName])) {
            throw new RuntimeException('the env name is not exist in hceData');
        }

        $info = $this->hceData[$envName];

        $this->hceEnv = $envName;
        if ($host = $info['host']) {
            $this->serveAddr = $host;
        }

        // TODO load more from $info
        // phpBin

        return $this;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getInfo(): array
    {
        return [
            'phpBinFile'   => $this->getPhpBin(),
            'serverAddr'   => $this->getServerAddr(),
            'documentRoot' => $this->docRoot,
            'entryFile'    => $this->getEntryFile(),
            'commandLine'  => $this->getCommand(false),
        ];
    }

    /**
     * @param callable $beforeStart
     *
     * @return PhpDevServe
     */
    public function setBeforeStart(callable $beforeStart): self
    {
        $this->beforeStart = $beforeStart;
        return $this;
    }

    /**
     * @return array
     */
    public function getCurrentHceInfo(): array
    {
        if ($this->hceEnv) {
            return $this->hceData[$this->hceEnv] ?? [];
        }

        return [];
    }

    /**
     * @return string
     */
    public function getPhpBin(): string
    {
        if (!$this->phpBin) {
            $this->phpBin = self::PHP_BIN;
        }

        return $this->phpBin;
    }

    /**
     * @return string
     */
    public function getEntryFile(): string
    {
        // if (!$this->entryFile) {
        //     $this->entryFile = self::IDX_FILE;
        // }
        return $this->entryFile;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getRandomPort(): int
    {
        return random_int(10001, 59999);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getServerAddr(): string
    {
        if (!$this->serveAddr) {
            $this->serveAddr = self::SVR_ADDR;
        } else {
            $svrAddr = $this->serveAddr;
            $charPos = strpos($svrAddr, ':');
            if ($charPos === false) {
                $this->serveAddr .= ':' . $this->getRandomPort();
                // } elseif ($charPos === 0) {
                //     $this->svrAddr = '' . $svrAddr;
            }
        }

        return $this->serveAddr;
    }

    /**
     * @return array
     */
    public function getHceData(): array
    {
        return $this->hceData;
    }

    /**
     * @param string $entryFile
     *
     * @return PhpDevServe
     */
    public function setEntryFile(string $entryFile): self
    {
        $this->entryFile = $entryFile;
        return $this;
    }

    /**
     * @param string $phpBin
     *
     * @return PhpDevServe
     */
    public function setPhpBin(string $phpBin): self
    {
        if ($phpBin) {
            $this->phpBin = $phpBin;
        }

        return $this;
    }

    /**
     * @param string $docRoot
     *
     * @return PhpDevServe
     */
    public function setDocRoot(string $docRoot): self
    {
        if ($docRoot) {
            $this->docRoot = $docRoot;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getEnvVars(): array
    {
        return $this->envVars;
    }
}
