<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component;

use Inhere\Console\Contract\ErrorHandlerInterface;
use InvalidArgumentException;
use Throwable;
use Toolkit\Cli\Cli;
use Toolkit\Cli\Util\Highlighter;
use Toolkit\Stdlib\Obj\ObjectHelper;
use function file_get_contents;
use function get_class;
use function sprintf;
use function str_replace;

/**
 * Class ErrorHandler
 *
 * @package Inhere\Console\Component
 */
class ErrorHandler implements ErrorHandlerInterface
{
    /**
     * @var bool
     */
    protected bool $debug = false;

    /**
     * @var string
     */
    protected string $rootPath = '';

    /**
     * @var bool
     */
    protected bool $hideRootPath = false;

    /**
     * @param array $config
     *
     * @return static
     */
    public static function new(array $config = []): self
    {
        return new self($config);
    }

    /**
     * Class constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        ObjectHelper::init($this, $config);
    }

    /**
     * @inheritdoc
     */
    public function handle(Throwable $e): void
    {
        if ($e instanceof InvalidArgumentException) {
            Cli::error($e->getMessage());
            return;
        }

        $class = get_class($e);

        // open debug, throw exception
        if ($this->isDebug()) {
            $tpl  = <<<ERR
\n<error> Error </error> <mga>%s</mga>

At File <cyan>%s</cyan> line <bold>%d</bold>
Exception class is <magenta>$class</magenta>
<comment>Code View:</comment>\n\n%s
<comment>Code Trace:</comment>\n\n%s\n
ERR;
            $line = $e->getLine();
            $file = $e->getFile();
            $prev = $e->getPrevious();

            $snippet = Highlighter::create()->snippet(file_get_contents($file), $line, 3, 3);
            $message = sprintf(
                $tpl, // $e->getCode(),
                $e->getMessage(),
                $file,
                $line, // __METHOD__,
                $snippet,
                $e->getTraceAsString() . ($prev ? "\n<comment>Previous:</comment> {$prev->getMessage()}\n" . $prev->getTraceAsString() : '')
            );

            if ($this->hideRootPath && ($rootPath = $this->rootPath)) {
                $message = str_replace($rootPath, '{ROOT}', $message);
            }

            Cli::write($message, false);
            return;
        }

        // simple output
        Cli::error($e->getMessage() ?: 'unknown error');
        Cli::write("\nYou can use '--debug 4' to see error details.");
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * @param string $rootPath
     */
    public function setRootPath(string $rootPath): void
    {
        $this->rootPath = $rootPath;
    }

    /**
     * @return bool
     */
    public function isHideRootPath(): bool
    {
        return $this->hideRootPath;
    }

    /**
     * @param bool $hideRootPath
     */
    public function setHideRootPath(bool $hideRootPath): void
    {
        $this->hideRootPath = $hideRootPath;
    }
}
