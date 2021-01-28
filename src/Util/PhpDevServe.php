<?php declare(strict_types=1);

namespace Inhere\Console\Util;

use Toolkit\Cli\Color;
use Toolkit\Sys\Sys;
use function implode;
use function sprintf;
use const PHP_EOL;

/**
 * Class PhpDevServe
 *
 * @package Inhere\Kite\Common
 */
class PhpDevServe
{
    /**
     * @var string
     */
    private $phpBin;

    /**
     * @var string
     */
    private $serveAddr;

    /**
     * @var string
     */
    private $entryFile;

    /**
     * @var string
     */
    private $documentRoot;

    /**
     * @param string $serveAddr
     * @param string $documentRoot
     * @param string $entryFile
     *
     * @return static
     */
    public static function new(string $serveAddr, string $documentRoot, string $entryFile = ''): self
    {
        return new self($serveAddr, $documentRoot, $entryFile);
    }

    /**
     * Class constructor.
     *
     * @param string $serveAddr
     * @param string $documentRoot
     * @param string $entryFile
     */
    public function __construct(string $serveAddr, string $documentRoot, string $entryFile = '')
    {
        $this->documentRoot = $documentRoot;

        $this->entryFile = $entryFile;
        $this->serveAddr = $serveAddr ?: '127.0.0.1:8080';
    }

    /**
     * @param bool $withColorTag
     */
    public function showTipsMessage(bool $withColorTag = true): void
    {
        $msg = $this->getTipsMessage($withColorTag);

        if ($withColorTag) {
            echo Color::parseTag($msg), PHP_EOL;
        } else {
            echo $msg, PHP_EOL;
        }
    }

    /**
     * @param bool $withColorTag
     *
     * @return string
     */
    public function getTipsMessage(bool $withColorTag = true): string
    {
        $version = PHP_VERSION;

        $addr = $this->serveAddr;
        $root = $this->documentRoot;
        $stop = 'CTRL + C';

        if ($withColorTag) {
            $addr = "<info>{$this->serveAddr}</info>";
            $root = "<comment>{$this->documentRoot}</comment>";
            $stop = "<comment>CTRL + C</comment>";
        }

        $nodes = [
            sprintf("PHP $version Development Server started\nServer listening on http://%s", $addr),
            sprintf("Document root is %s\nYou can use %s to stop run.", $root, $stop),
        ];

        return implode("\n", $nodes);
    }

    public function start(): void
    {
        // php -S {$serveAddr} -t public public/index.php
        $phpBin  = $this->phpBin ?: 'php';
        $command = "$phpBin -S {$this->serveAddr}";

        if ($this->documentRoot) {
            $command .= " -t {$this->documentRoot}";
        }

        if ($entryFile = $this->entryFile) {
            $command .= " $entryFile";
        }

        echo Color::parseTag("<comment>></comment> <info>$command</info>"), PHP_EOL;
        Sys::execute($command);
    }

    /**
     * @return string
     */
    public function findPhpBinFile(): string
    {
        $phpBin = 'php';

        // TODO use `type php` check and find. return: 'php is /usr/local/bin/php'
        [$ok, $ret,] = Sys::run('which php');
        if ($ok === 0) {
            $phpBin = trim($ret);
        }

        return $phpBin;
    }

    /**
     * @return string
     */
    public function getPhpBin(): string
    {
        return $this->phpBin;
    }

    /**
     * @param string $phpBin
     *
     * @return PhpDevServe
     */
    public function setPhpBin(string $phpBin): self
    {
        $this->phpBin = $phpBin;
        return $this;
    }

    /**
     * @return string
     */
    public function getServeAddr(): string
    {
        return $this->serveAddr;
    }

    /**
     * @param string $serveAddr
     *
     * @return PhpDevServe
     */
    public function setServeAddr(string $serveAddr): self
    {
        $this->serveAddr = $serveAddr;
        return $this;
    }

    /**
     * @return string
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    /**
     * @param string $documentRoot
     *
     * @return PhpDevServe
     */
    public function setDocumentRoot(string $documentRoot): self
    {
        $this->documentRoot = $documentRoot;
        return $this;
    }

    /**
     * @return string
     */
    public function getEntryFile(): string
    {
        return $this->entryFile;
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
}
