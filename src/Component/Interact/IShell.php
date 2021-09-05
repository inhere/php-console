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
use Toolkit\Cli\Util\Readline;
use Toolkit\Sys\Proc\ProcessUtil;
use Toolkit\Sys\Proc\Signal;
use function explode;
use function stripos;
use function strlen;
use function strpos;
use function substr;
use function trim;

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
     * @var bool
     */
    private $hasPcntl = false;

    /**
     * @var bool
     */
    private $hasReadline = false;

    /**
     * @var string
     */
    private $historyFile = '';

    /**
     * @var int
     */
    private $historySize = 1024;

    /**
     * Auto complete handler
     *
     * ```php
     * function (string $input, int $index) {
     *  // do something...
     * }
     * ```
     *
     * @var callable
     */
    private $autoCompleter;

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
     *
     * @throws Throwable
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
        $this->hasPcntl = ProcessUtil::hasPcntl();

        if (!$this->hasPcntl) {
            $this->debugf('php is not enable "pcntl" extension, cannot listen CTRL+C signal');
        }

        // register signal.
        if ($this->hasPcntl) {
            ProcessUtil::installSignal(Signal::INT, static function () {
                Show::colored("\nQuit by CTRL+C");
                exit(0);
            });
        }

        return $this->hasPcntl;
    }

    protected function registerReadline(): void
    {
        $this->hasReadline = Readline::isSupported();
        if (!$this->hasReadline) {
            return;
        }

        Readline::loadHistory($this->historyFile);
        Readline::registerCompleter([$this, 'autoCompleteHandle']);
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
     *
     * @throws Throwable
     */
    public function start(): void
    {
        if (!$handler = $this->handler) {
            throw new RuntimeException('must be set the logic handler for start');
        }

        $this->beforeStart();
        $this->registerSignal();
        $this->registerReadline();

        try {
            $this->doRun($handler);
        } catch (Throwable $e) {
            throw new $e;
        } finally {
            if ($this->hasReadline) {
                Readline::dumpHistory($this->historyFile);
            }
        }

        Show::colored("\nQuit. ByeBye!");
    }

    /**
     * @param callable $handler
     */
    public function doRun(callable $handler): void
    {
        $prefix = $this->prefix;

        while (true) {
            $line = Interact::readln("<bold>$prefix></bold> ");

            // listen signal.
            if ($this->hasPcntl) {
                ProcessUtil::dispatchSignal();
            }

            // has readline
            if ($this->hasReadline) {
                Readline::addHistory($line);
            }

            $state = $this->dispatch($line, $handler);
            if ($state === self::STOP) {
                break;
            }

            Console::println('');
        }
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
     * @param string $input
     * @param int    $index
     *
     * @return array|bool
     */
    public function autoCompleteHandle(string $input, int $index)
    {
        // custom auto-completer
        if ($fn = $this->autoCompleter) {
            return $fn($input, $index);
        }

        $commands = [
            '?',
            'help',
            'quit',
        ];

        $info = Readline::getInfo();
        $line = trim(substr($info['line_buffer'], 0, $info['end']));
        if ($info['point'] !== $info['end']) {
            return true;
        }

        $founded = [];

        // $line=$input completion for top command name prefix.
        if (strpos($line, ' ') === false) {
            foreach ($commands as $name) {
                if (stripos($name, $input)) {
                    $founded[] = $name;
                }
            }
        } // else // completion for subcommand

        return $founded ?: $commands;
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
    public function setHandler(callable $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @param bool $debug
     *
     * @return IShell
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }

    /**
     * @param array $exitKeys
     *
     * @return IShell
     */
    public function setExitKeys(array $exitKeys): self
    {
        $this->exitKeys = $exitKeys;
        return $this;
    }

    /**
     * @param string $title
     *
     * @return IShell
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $prefix
     *
     * @return IShell
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @param callable $helpHandler
     *
     * @return IShell
     */
    public function setHelpHandler(callable $helpHandler): self
    {
        $this->helpHandler = $helpHandler;
        return $this;
    }

    /**
     * @param callable $errorHandler
     *
     * @return IShell
     */
    public function setErrorHandler(callable $errorHandler): self
    {
        $this->errorHandler = $errorHandler;
        return $this;
    }

    /**
     * @return callable
     */
    public function getAutoCompleter(): callable
    {
        return $this->autoCompleter;
    }

    /**
     * @param callable $autoCompleter
     *
     * @return IShell
     */
    public function setAutoCompleter(callable $autoCompleter): self
    {
        $this->autoCompleter = $autoCompleter;
        return $this;
    }

    /**
     * @return string
     */
    public function getHistoryFile(): string
    {
        return $this->historyFile;
    }

    /**
     * @param string $historyFile
     */
    public function setHistoryFile(string $historyFile): void
    {
        $this->historyFile = $historyFile;
    }

    /**
     * @return int
     */
    public function getHistorySize(): int
    {
        return $this->historySize;
    }

    /**
     * @param int $historySize
     */
    public function setHistorySize(int $historySize): void
    {
        $this->historySize = $historySize;
    }
}
