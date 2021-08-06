<?php declare(strict_types=1);

namespace Inhere\Console\Component;

use BadMethodCallException;
use Closure;
use DateTime;
use DateTimeZone;
use Exception;
use FilesystemIterator;
use Inhere\Console\Util\Helper;
use InvalidArgumentException;
use Iterator;
use Phar;
use RuntimeException;
use Seld\PharUtils\Timestamps;
use SplFileInfo;
use SplQueue;
use Toolkit\FsUtil\File;
use Toolkit\Sys\Sys;
use UnexpectedValueException;
use function array_merge;
use function basename;
use function class_exists;
use function date;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function ini_get;
use function is_dir;
use function is_file;
use function is_string;
use function ltrim;
use function preg_replace;
use function realpath;
use function str_replace;
use function stripos;
use function strlen;
use function strpos;
use function substr;
use function substr_replace;
use function trim;
use function unlink;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * Class PharCompiler
 *
 * @since 1.0
 */
class PharCompiler
{
    public const ON_ADD = 'add';

    public const ON_SKIP = 'skip';

    public const ON_ERROR = 'error';

    public const ON_MESSAGE = 'message';

    public const ON_COLLECTED = 'collected';

    public const FILE_EXT = '.phar';

    public const ADD_CLI_INDEX = 'add.index.cli';
    public const ADD_WEB_INDEX = 'add.index.web';

    /** @var array */
    private static $supportedSignatureTypes = [
        Phar::SHA512 => 1,
        Phar::SHA256 => 1,
        Phar::SHA1   => 1
    ];

    /** @var resource */
    private $key;

    /** @var int */
    private $signatureType;

    /**
     * @var int compress Mode @see \Phar::NONE, \Phar::GZ, \Phar::BZ2
     */
    private $compressMode = 0;

    /**
     * @var string The want to packaged project path
     */
    private $basePath;

    /**
     * @var string
     */
    private $cliIndex = '';

    /**
     * @var string
     */
    private $webIndex = '';

    /**
     * @var string|bool Set the shebang. eg '#!/usr/bin/env php'
     */
    private $shebang;

    /**
     * @var array Want to added files. (It is relative the $basePath)
     */
    private $files = [];

    /**
     * @var array Want to include files suffix name list
     */
    private $suffixes = ['.php'];

    /**
     * @var array Want to exclude directory/file name list
     * [
     *  '/test/', // exclude all contains '/test/' path
     * ]
     */
    private $excludes = [];

    /**
     * @var array The directory paths, will collect files in there.
     */
    private $directories = [];

    /**
     * @var Closure[] Some events. if you want to get some info on packing.
     */
    private $events = [];

    /**
     * @var Closure Maybe you not want strip all files.
     */
    private $stripFilter;

    /**
     * @var bool Whether strip comments
     */
    private $stripComments = true;

    /**
     * @var bool Whether auto collect version info by git log.
     */
    private $collectVersionInfo = true;

    // -------------------- project version(by git) --------------------

    /**
     * @var string The latest commit id
     */
    private $lastCommit = '';

    /**
     * @var string The latest tag name
     */
    private $lastVersion = '';

    /**
     * @var DateTime
     */
    private $versionDate;

    /**
     * 记录上面三个信息的文件, 相对于basePath
     * 当里面存在下面的占位符时会自动替换为获取到的信息
     * [
     *  'lastCommit'  => '{@package_last_commit}',
     *  'lastVersion' => '{@package_last_version}',
     *  'releaseDate' => '{@release_date}',
     * ]
     *
     * @var string
     */
    private $versionFile = '';

    private $versionFileContent = '';

    // -------------------- internal properties --------------------

    /** @var int */
    private $counter = 0;

    /**
     * @var string Phar file path. e.g '/some/path/app.phar'
     */
    private $pharFile;

    /**
     * @var string Phar file name. eg 'app.phar'
     */
    private $pharName;

    /**
     * @var Closure File filter
     */
    private $fileFilter;

    /**
     * @var array|Iterator The modifies files list. if not empty, will skip find dirs.
     */
    private $modifies;

    /**
     * @var SplQueue
     */
    private $fileQueue;

    /**
     * @param string            $pharFile
     * @param string            $extractTo
     * @param string|array|null $files Only fetch the listed files
     * @param bool              $overwrite
     *
     * @return bool
     * @throws BadMethodCallException
     * @throws RuntimeException
     */
    public static function unpack(string $pharFile, string $extractTo, $files = null, $overwrite = false): bool
    {
        self::checkEnv();

        $phar = new Phar($pharFile);

        return $phar->extractTo($extractTo, $files, $overwrite);
    }

    /**
     * @throws RuntimeException
     */
    private static function checkEnv(): void
    {
        if (!class_exists(Phar::class, false)) {
            throw new RuntimeException("The 'phar' extension is required for build phar package");
        }

        if (ini_get('phar.readonly')) {
            throw new RuntimeException(
                "The 'phar.readonly' is 'On', build phar must setting it 'Off' or exec with 'php -d phar.readonly=0'"
            );
        }
    }

    /**
     * PharCompiler constructor.
     *
     * @param string $basePath
     *
     * @throws RuntimeException
     */
    public function __construct(string $basePath)
    {
        self::checkEnv();

        $this->basePath  = realpath($basePath);
        $this->fileQueue = new SplQueue();

        if (!is_dir($this->basePath)) {
            throw new RuntimeException("The inputted path is not exists. PATH: {$this->basePath}");
        }
    }

    /**
     * @param string|array $files
     *
     * @return $this
     */
    public function addFile($files): self
    {
        $this->files = array_merge($this->files, (array)$files);
        return $this;
    }

    /**
     * @param array $files
     *
     * @return PharCompiler
     */
    public function setFiles(array $files): self
    {
        $this->files = $files;
        return $this;
    }

    /**
     * @param string|array $suffixes
     *
     * @return $this
     */
    public function addSuffix($suffixes): self
    {
        $this->suffixes = array_merge($this->suffixes, (array)$suffixes);
        return $this;
    }

    /**
     * @param string|array $patterns
     *
     * @return $this
     */
    public function addExclude($patterns): self
    {
        $this->excludes = array_merge($this->excludes, (array)$patterns);
        return $this;
    }

    /**
     * @param string|array $patterns
     *
     * @return $this
     */
    public function addExcludeDir($patterns): self
    {
        $list = [];
        foreach ((array)$patterns as $pattern) {
            $list[] = '/' . trim($pattern, '/') . '/';
        }

        $this->excludes = array_merge($this->excludes, $list);
        return $this;
    }

    /**
     * @param string|array $patterns
     *
     * @return $this
     */
    public function addExcludeFile($patterns): self
    {
        $list = [];
        foreach ((array)$patterns as $pattern) {
            $list[] = '/' . ltrim($pattern, '/') . '/';
        }

        $this->excludes = array_merge($this->excludes, $list);
        return $this;
    }

    /**
     * @param array $excludes
     *
     * @return PharCompiler
     */
    public function setExcludes(array $excludes): self
    {
        $this->excludes = $excludes;
        return $this;
    }

    /**
     * @param bool $value
     *
     * @return PharCompiler
     */
    public function stripComments($value): self
    {
        $this->stripComments = (bool)$value;
        return $this;
    }

    /**
     * @param bool $value
     *
     * @return PharCompiler
     */
    public function collectVersion($value): self
    {
        $this->collectVersionInfo = (bool)$value;
        return $this;
    }

    /**
     * @param Closure $stripFilter
     *
     * @return PharCompiler
     */
    public function setStripFilter(Closure $stripFilter): self
    {
        $this->stripFilter = $stripFilter;
        return $this;
    }

    /**
     * @param bool|string $shebang
     *
     * @return PharCompiler
     */
    public function setShebang($shebang): self
    {
        $this->shebang = $shebang;
        return $this;
    }

    /**
     * @param string|array $dirs
     *
     * @return PharCompiler
     */
    public function in($dirs): self
    {
        $this->directories = array_merge($this->directories, (array)$dirs);
        return $this;
    }

    /**
     * @param array|Iterator $modifies
     *
     * @return PharCompiler
     */
    public function setModifies($modifies): self
    {
        $this->modifies = $modifies;
        return $this;
    }

    /**
     * Compiles composer into a single phar file
     *
     * @param string $pharFile The full path to the file to create
     * @param bool   $refresh
     *
     * @return string
     * @throws UnexpectedValueException
     * @throws BadMethodCallException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function pack(string $pharFile, bool $refresh = true): string
    {
        if (!$this->directories) {
            throw new RuntimeException("Please setting the 'directories' want building directories by 'in()'");
        }

        $exists = file_exists($pharFile);
        if ($refresh && $exists) {
            unlink($pharFile);
        }

        $this->pharFile = $pharFile;
        $this->pharName = $pharName = basename($this->pharFile);
        // $this->excludes = \array_flip($this->excludes);

        $this->collectInformation();

        $phar = new Phar($pharFile, 0, $pharName);

        if ($this->key) {
            $privateKey = '';
            /** @noinspection PhpComposerExtensionStubsInspection */
            openssl_pkey_export($this->key, $privateKey);
            $phar->setSignatureAlgorithm(Phar::OPENSSL, $privateKey);
            /** @noinspection PhpComposerExtensionStubsInspection */
            $keyDetails = openssl_pkey_get_details($this->key);
            file_put_contents($pharFile . '.pubkey', $keyDetails['key']);
        } else {
            $phar->setSignatureAlgorithm($this->selectSignatureType());
        }

        // Collect files
        $this->collectFiles($exists, $refresh);

        // Begin packing
        $this->fire(self::ON_COLLECTED, $this->counter);
        $phar->startBuffering();

        $this->fire(self::ON_MESSAGE, 'Files collect complete, begin add file to Phar');
        $phar->buildFromIterator($this->fileQueue, $this->basePath);

        // Add index files
        $this->packIndexFile($phar);

        // Add version file
        if ($content = $this->versionFileContent) {
            $phar->addFromString($this->versionFile, $content);
        }

        // Add Stubs
        // $phar->setDefaultStub($this->cliIndex, $this->webIndex));
        $phar->setStub($this->createStub());

        if ($this->compressMode) {
            $phar->compressFiles($this->compressMode);
        }

        // Default meta information
        // $metaData = array(
        //     'Author'      => 'Inhere <in.@lange.demon.co.uk>',
        //     'Description' => 'PHP Class for working with Matrix numbers',
        //     'Copyright'   => 'Mark Baker (c) 2013-' . date('Y'),
        //     'Timestamp'   => time(),
        //     'Version'     => '0.1.0',
        //     'Date'        => date('Y-m-d')
        // );
        // $phar->setMetadata($metaData);
        $this->fire(self::ON_MESSAGE, 'Write requests to the Phar archive, save changes to disk');
        $phar->stopBuffering();
        unset($phar);

        // re-sign the phar with reproducible timestamp / signature
        if (class_exists(Timestamps::class)) {
            $util = new Timestamps($pharFile);
            $util->updateTimestamps($this->versionDate);
            $util->save($pharFile, Phar::SHA1);
        }

        return $pharFile;
    }

    /**
     * @param bool $exists
     * @param bool $refresh
     */
    protected function collectFiles(bool $exists, bool $refresh): void
    {
        $basePath = $this->basePath;

        // Only build modifies
        if (!$refresh && $exists && $this->modifies) {
            foreach ($this->modifies as $file) {
                if ('/' === $file[0] || is_file($file = $basePath . '/' . $file)) {
                    $this->collectFileInfo(new SplFileInfo($file));
                }
            }
        } else {
            // Collect files in there are dirs.
            foreach ($this->directories as $directory) {
                foreach ($this->findFiles($directory) as $fileInfo) {
                    $this->collectFileInfo($fileInfo);
                }
            }
        }

        // Add special files
        foreach ($this->files as $filename) {
            if ('/' === $filename[0] || is_file($filename = $basePath . '/' . $filename)) {
                // $this->packFile($phar, new SplFileInfo($filename));
                $this->collectFileInfo(new SplFileInfo($filename));
            }
        }
    }

    /**
     * @param SplFileInfo $file
     */
    protected function collectFileInfo(SplFileInfo $file): void
    {
        $filePath = $file->getPathname();

        // Skip not exist file
        if (!file_exists($filePath)) {
            $this->fire(self::ON_ERROR, "File $filePath is not exists!");
            return;
        }

        $this->counter++;
        $relativePath = $this->getRelativeFilePath($file);

        $this->fire(self::ON_ADD, $relativePath, $this->counter);

        // have version file
        if ($relativePath === $this->versionFile) {
            $content = file_get_contents($filePath);
            // save content
            $this->versionFileContent = $this->addVersionInfo($content);
            return;
        }

        $this->fileQueue->push($file);
    }

    /**
     * find changed or new created files by git status.
     *
     * @throws RuntimeException
     */
    public function findChangedByGit()
    {
        // -u expand dir's files
        [, $output,] = Sys::run('git status -s -u', $this->basePath);

        // 'D some.file'    deleted
        // ' M some.file'   modified
        // '?? some.file'   new file
        foreach (explode("\n", trim($output)) as $file) {
            $file = trim($file);

            // only php file.
            if (!strpos($file, '.php')) {
                continue;
            }

            // modified files
            if (strpos($file, 'M ') === 0) {
                yield substr($file, 2);

                // new files
            } elseif (strpos($file, '?? ') === 0) {
                yield substr($file, 3);
            }
        }
    }

    /**
     * @param string $directory
     *
     * @return Iterator|SplFileInfo[]
     * @throws InvalidArgumentException
     */
    protected function findFiles(string $directory)
    {
        return Helper::directoryIterator(
            $directory,
            $this->getIteratorFilter(),
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
        );
    }

    /**
     * @param string $content
     *
     * @return string
     */
    private function addVersionInfo(string $content): string
    {
        if (!$this->collectVersionInfo) {
            return $content;
        }

        return str_replace([
            '{@package_last_commit}',
            '{@package_last_version}',
            '{@release_date}',
        ], [
            $this->lastCommit,
            $this->lastVersion,
            $this->versionDate->format('Y-m-d H:i:s')
        ], $content);
    }

    /**
     * @param Phar $phar
     */
    private function packIndexFile(Phar $phar): void
    {
        if ($this->cliIndex) {
            $this->counter++;
            $path = $this->basePath . '/' . $this->cliIndex;

            $this->fire(self::ON_ADD, $this->cliIndex, $this->counter);

            $rawContent = preg_replace('{^#!/usr/bin/env php\s*}', '', file_get_contents($path));
            $fmtContent = $this->fire(self::ADD_CLI_INDEX, $this->cliIndex, $rawContent);
            if ($fmtContent) {
                $rawContent = $fmtContent;
            }

            $phar->addFromString($this->cliIndex, trim($rawContent) . PHP_EOL);
        }

        if ($this->webIndex) {
            $this->counter++;
            $path = $this->basePath . '/' . $this->webIndex;

            $this->fire(self::ON_ADD, $this->webIndex, $this->counter);

            $rawContent = file_get_contents($path);
            $fmtContent = $this->fire(self::ADD_WEB_INDEX, $this->cliIndex, $rawContent);
            if ($fmtContent) {
                $rawContent = $fmtContent;
            }

            $phar->addFromString($this->webIndex, trim($rawContent) . PHP_EOL);
        }
    }

    /**
     * @return int
     */
    public function getCounter(): int
    {
        return $this->counter;
    }

    /**
     * @return string
     * @throws RuntimeException
     */
    private function createStub(): string
    {
        $date     = date('Y-m-d H:i');
        $pharName = $this->pharName;
        $stub     = <<<EOF
<?php declare(strict_types=1);
/**
 * @date $date
 * @author inhere <in.798@qq.com>
 */

define('IN_PHAR', true);
Phar::mapPhar('$pharName');

EOF;
        // add shebang
        if ($shebang = $this->shebang) {
            $shebang = is_string($shebang) ? $shebang : '#!/usr/bin/env php';
            $stub    = "$shebang\n$stub";
        }

        if ($this->cliIndex && $this->webIndex) {
            $stub .= <<<EOF
// for command line            
if (PHP_SAPI === 'cli') {
    require 'phar://$pharName/{$this->cliIndex}';
} else {
    require 'phar://$pharName/{$this->webIndex}';
}
EOF;
        } elseif ($this->cliIndex) {
            $stub .= "\nrequire 'phar://$pharName/{$this->cliIndex}';\n";
        } elseif ($this->webIndex) {
            $stub .= "\nrequire 'phar://$pharName/{$this->webIndex}';\n";
        } else {
            throw new RuntimeException("'cliIndex' and 'webIndex', please set at least one");
        }

        return $stub . "\n__HALT_COMPILER();\n";
    }

    /**
     * @return Closure
     */
    private function getIteratorFilter(): Closure
    {
        if (!$this->fileFilter) {
            $this->fileFilter = function (SplFileInfo $file) {
                $name = $file->getFilename();
                $path = $file->getPathname();

                // Skip hidden files and directories.
                if (strpos($name, '.') === 0) {
                    return false;
                }

                // Skip exclude directories.
                if ($file->isDir()) {
                    foreach ($this->excludes as $exclude) {
                        if (strpos($path . '/', $exclude) > 0) {
                            $this->fire(self::ON_SKIP, $path, false);
                            return false;
                        }
                    }
                    return true;
                }

                // File ext check
                if ($this->suffixes) {
                    foreach ($this->suffixes as $suffix) {
                        if (stripos($name, $suffix) !== false) {
                            return true;
                        }
                    }

                    $this->fire(self::ON_SKIP, $path, true);
                    return false;
                }

                return true;
            };
        }

        return $this->fileFilter;
    }

    /**
     * Removes whitespace from a PHP source string while preserving line numbers.
     *
     * @param string $source A PHP string
     *
     * @return string The PHP string with the whitespace removed
     */
    private function stripWhitespace(string $source): string
    {
        return File::stripPhpCode($source);
    }

    /**
     * Auto collect project information by git log
     *
     * @throws RuntimeException
     * @throws Exception
     */
    private function collectInformation(): void
    {
        if (!$this->collectVersionInfo) {
            return;
        }

        $basePath = $this->basePath;
        [$code, $ret,] = Sys::run('git log --pretty="%H" -n1 HEAD', $basePath);

        if ($code !== 0) {
            throw new RuntimeException(
                'Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.'
            );
        }

        $this->lastCommit = trim($ret);

        [$code, $ret,] = Sys::run('git log -n1 --pretty=%ci HEAD', $basePath);

        if ($code !== 0) {
            throw new RuntimeException(
                'Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.'
            );
        }

        $this->versionDate = new DateTime(trim($ret));
        $this->versionDate->setTimezone(new DateTimeZone('UTC'));

        // Get the latest tag
        [$code, $ret,] = Sys::run('git describe --tags --exact-match HEAD', $basePath);
        if ($code === 0) {
            $this->lastCommit = trim($ret);
        } else {
            [$code, $ret,] = Sys::run('git branch', $basePath);
            $this->lastVersion = $code === 0 ? trim($ret, '* ') : 'UNKNOWN';
        }
    }

    /**
     * @param SplFileInfo $file
     *
     * @return string
     */
    private function getRelativeFilePath(SplFileInfo $file): string
    {
        $realPath   = $file->getRealPath();
        $pathPrefix = $this->basePath . DIRECTORY_SEPARATOR;

        $pos  = strpos($realPath, $pathPrefix);
        $path = $pos !== false ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

        return str_replace('\\', '/', $path);
    }

    /**
     * @param string $event
     * @param array  $args
     *
     * @return mixed|null
     */
    private function fire(string $event, ...$args)
    {
        if (isset($this->events[$event])) {
            $cb = $this->events[$event];
            return $cb(...$args);
        }

        return null;
    }

    /**
     * add event handler
     *
     * @param string  $event
     * @param Closure $closure
     */
    public function on(string $event, Closure $closure): void
    {
        $this->events[$event] = $closure;
    }

    /**
     * @param Closure $onAdd
     */
    public function onAdd(Closure $onAdd): void
    {
        $this->on(self::ON_ADD, $onAdd);
    }

    /**
     * @param Closure $onError
     */
    public function onError(Closure $onError): void
    {
        $this->on(self::ON_ERROR, $onError);
    }

    /**
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @param string $basePath
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = realpath($basePath);
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @return array
     */
    public function getExcludes(): array
    {
        return $this->excludes;
    }

    /**
     * @return string
     */
    public function getCliIndex(): string
    {
        return $this->cliIndex;
    }

    /**
     * @param string $cliIndex
     *
     * @return $this
     */
    public function setCliIndex(string $cliIndex): self
    {
        $this->cliIndex = $cliIndex;
        return $this;
    }

    /**
     * @return string
     */
    public function getWebIndex(): string
    {
        return $this->webIndex;
    }

    /**
     * @param null|string $webIndex
     *
     * @return PharCompiler
     */
    public function setWebIndex(string $webIndex): self
    {
        $this->webIndex = $webIndex;
        return $this;
    }

    /**
     * @return Closure[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @return string
     */
    public function getVersionFile(): string
    {
        return $this->versionFile;
    }

    /**
     * @param string $versionFile
     *
     * @return PharCompiler
     */
    public function setVersionFile(string $versionFile): PharCompiler
    {
        $this->versionFile = $versionFile;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastCommit(): string
    {
        return $this->lastCommit;
    }

    /**
     * @param null|string $lastCommit
     *
     * @return PharCompiler
     */
    public function setLastCommit(string $lastCommit): PharCompiler
    {
        $this->lastCommit = $lastCommit;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastVersion(): string
    {
        return $this->lastVersion;
    }

    /**
     * @param null|string $lastVersion
     *
     * @return PharCompiler
     */
    public function setLastVersion(string $lastVersion): PharCompiler
    {
        $this->lastVersion = $lastVersion;
        return $this;
    }

    /**
     * @return int
     */
    private function selectSignatureType(): int
    {
        if (isset(self::$supportedSignatureTypes[$this->signatureType])) {
            return $this->signatureType;
        }

        return Phar::SHA1;
    }

    /**
     * @return string
     */
    public function getPharName(): string
    {
        return $this->pharName;
    }

    /**
     * @return string
     */
    public function getPharFile(): string
    {
        return $this->pharFile;
    }

    /**
     * @return int
     */
    public function getFileCount(): int
    {
        return $this->fileQueue->count();
    }
}
