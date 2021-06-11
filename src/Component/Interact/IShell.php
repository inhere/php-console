<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Closure;
use Inhere\Console\Component\Formatter\Title;
use Inhere\Console\Component\InteractiveHandle;
use Inhere\Console\Console;
use Inhere\Console\Util\Interact;
use Inhere\Console\Util\Show;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
use Toolkit\Sys\Proc\ProcessUtil;
use Toolkit\Sys\Proc\Signal;
use function explode;
use function strlen;
use function strpos;

/**
 * Class IShell
 *
 * @package Inhere\Console\Component\Interact
 */
class IShell extends InteractiveHandle
{
    public const HELP = 'help';

    private const STOP = 1;
    private const GOON = 2;

    /**
     * @var bool
     */
    private $debug = false;

    /**
     * @var string
     */
    private $prefix = 'CMD';

    /**
     * @var string
     */
    private $title = 'Welcome interactive shell environment';

    /**
     * the main logic handler
     *
     * @var callable
     */
    private $handler;

    /**
     * @var callable
     */
    private $helpHandler;

    /**
     * @var callable
     */
    private $errorHandler;

    /**
     * @var array
     */
    private $exitKeys = [
        'q'    => 1,
        'quit' => 1,
        'exit' => 1,
    ];

    /**
     * @param array $options
     *
     * @return static
     */
    public static function new(array $options = []): self
    {
        return new self($options);
    }

    /**
     * Quick create and start run an shell
     *
     * @param callable $handler
     * @param array    $options
     *                         - title
     *                         - prefix
     */
    public static function run(callable $handler, array $options = []): void
    {
        $options['handler'] = $handler;

        (new self($options))->start();
    }

    /**
     * @return bool
     */
    protected function registerSignal(): bool
    {
        if (!($hasPcntl = ProcessUtil::hasPcntl())) {
            $this->debugf('php is not enable "pcntl" extension, cannot listen CTRL+C signal');
        }

        // register signal.
        if ($hasPcntl) {
            ProcessUtil::installSignal(Signal::INT, static function () {
                Show::colored("\nQuit by CTRL+C");
                exit(0);
            });
        }

        return $hasPcntl;
    }

    protected function beforeStart(): void
    {
        if ($this->title) {
            Title::show($this->title, [
                'titlePos' => Title::POS_MIDDLE,
            ]);
        }

        if (!$this->errorHandler) {
            $this->errorHandler = $this->defaultErrorHandler();
        }
    }

    /**
     * Start shell to run
     */
    public function start(): void
    {
        if (!$handler = $this->handler) {
            throw new RuntimeException('must be set the logic handler for start');
        }

        $prefix = $this->prefix;
        $this->beforeStart();

        $hasPcntl = $this->registerSignal();
        while (true) {
            $line = Interact::readln("<comment>$prefix></comment> ");

            // listen signal.
            if ($hasPcntl) {
                ProcessUtil::dispatchSignal();
            }

            $state = $this->dispatch($line, $handler);
            if ($state === self::STOP) {
                break;
            }

            Console::println('');
        }

        Show::colored("\nQuit. ByeBye!");
    }

    /**
     * @param string   $line
     * @param callable $handler
     *
     * @return int
     * @throws RuntimeException
     */
    protected function dispatch(string $line, callable $handler): int
    {
        if (strlen($line) < 5) {
            // exit
            if (isset($this->exitKeys[$line])) {
                return self::STOP;
            }

            // "?" as show help
            if ($line === '?') {
                $line = self::HELP;
            }
        }

        // display help
        $hasMoreKey = false;
        if ($line === self::HELP || ($hasMoreKey = strpos($line, 'help ') === 0)) {
            // help CMD
            $moreKeys = $hasMoreKey ? explode(' ', $line) : [];
            $this->handleHelp($moreKeys);
            return self::GOON;
        }

        $this->debugf('input line: %s', $line);

        try {
            // call validator
            if ($vfn = $this->validator) {
                $line = $vfn($line);
            }

            $handler($line);
        } catch (Throwable $e) {
            if ($fn = $this->errorHandler) {
                $fn($e);
            } else {
                throw new RuntimeException('dispatch error, line: ' . $line, 500, $e);
            }
        }

        return self::GOON;
    }

    /**
     * @param array $moreKeys
     */
    protected function handleHelp(array $moreKeys): void
    {
        if ($fn = $this->helpHandler) {
            $fn($moreKeys);
            return;
        }

        Console::println('no help message');
    }

    /**
     * @return Closure
     */
    public function emptyValidator(): Closure
    {
        return static function (string $line) {
            if ($line === '') {
                throw new InvalidArgumentException('input is empty!');
            }
            return $line;
        };
    }

    /**
     * @return Closure
     */
    public function defaultErrorHandler(): Closure
    {
        return static function (Throwable $e) {
            Console::write('<error>ERROR:</error> ' . $e->getMessage(), false);
        };
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public function debugf(string $format, ...$args): void
    {
        if ($this->debug) {
            Console::logf(Console::VERB_DEBUG, $format, ...$args);
        }
    }

    /**
     * @param callable $handler
     *
     * @return IShell
     */
    public function setHandler(callable $handler): IShell
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @param bool $debug
     *
     * @return IShell
     */
    public function setDebug(bool $debug): IShell
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @param array $exitKeys
     *
     * @return IShell
     */
    public function setExitKeys(array $exitKeys): IShell
    {
        $this->exitKeys = $exitKeys;
        return $this;
    }

    /**
     * @param string $title
     *
     * @return IShell
     */
    public function setTitle(string $title): IShell
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return IShell
     */
    public function setPrefix(string $prefix): IShell
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @param callable $helpHandler
     *
     * @return IShell
     */
    public function setHelpHandler(callable $helpHandler): IShell
    {
        $this->helpHandler = $helpHandler;
        return $this;
    }

    /**
     * @param callable $errorHandler
     *
     * @return IShell
     */
    public function setErrorHandler(callable $errorHandler): IShell
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }
}
