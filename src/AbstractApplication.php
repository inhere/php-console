<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 18:37
 */

namespace Inhere\Console;

use ErrorException;
use Inhere\Console\Component\ErrorHandler;
use Inhere\Console\Component\Formatter\Title;
use Inhere\Console\Concern\StyledOutputAwareTrait;
use Inhere\Console\Contract\ApplicationInterface;
use Inhere\Console\Contract\ErrorHandlerInterface;
use Inhere\Console\Contract\InputInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Contract\OutputInterface;
use Inhere\Console\Concern\ApplicationHelpTrait;
use Inhere\Console\Concern\InputOutputAwareTrait;
use Inhere\Console\Concern\SimpleEventAwareTrait;
use Inhere\Console\Util\Interact;
use InvalidArgumentException;
use Throwable;
use Toolkit\Cli\Style;
use Toolkit\Cli\Util\LineParser;
use Toolkit\Stdlib\Helper\PhpHelper;
use Toolkit\Sys\Proc\ProcessUtil;
use Toolkit\Sys\Proc\Signal;
use function array_keys;
use function array_merge;
use function error_get_last;
use function header;
use function in_array;
use function is_int;
use function json_encode;
use function memory_get_usage;
use function microtime;
use function register_shutdown_function;
use function set_error_handler;
use function set_exception_handler;
use function trim;
use const PHP_SAPI;

/**
 * Class AbstractApplication
 *
 * @package Inhere\Console
 */
abstract class AbstractApplication implements ApplicationInterface
{
    use ApplicationHelpTrait;
    use InputOutputAwareTrait;
    use StyledOutputAwareTrait;
    use SimpleEventAwareTrait;

    /** @var array */
    protected static $internalCommands = [
        'version' => 'Show application version information',
        'help'    => 'Show application help information',
        'list'    => 'List all group and alone commands',
    ];

    /** @var array */
    protected static $globalOptions = [
        '--debug'          => 'Setting the runtime log debug level(quiet 0 - 5 crazy)',
        '--ishell'         => 'Run application an interactive shell environment',
        '--profile'        => 'Display timing and memory usage information',
        '--no-color'       => 'Disable color/ANSI for message output',
        '-h, --help'       => 'Display this help message',
        '-V, --version'    => 'Show application version information',
        '--no-interactive' => 'Run commands in a non-interactive environment',
    ];

    /** @var array Application runtime stats */
    protected $stats = [
        'startTime'   => 0,
        'endTime'     => 0,
        'startMemory' => 0,
        'endMemory'   => 0,
    ];

    /** @var array Application config data */
    protected $config = [
        'name'           => 'My Console Application',
        'description'    => 'This is my console application',
        'debug'          => Console::VERB_ERROR,
        'profile'        => false,
        'version'        => '0.5.1',
        'publishAt'      => '2017.03.24',
        'updateAt'       => '2019.01.01',
        'rootPath'       => '',
        'ishellName'     => '', // name prefix on i-shell env.
        'strictMode'     => false,
        'hideRootPath'   => true,
        // global options
        'no-interactive' => true,

        // 'timeZone' => 'Asia/Shanghai',
        // 'env' => 'prod', // dev test prod
        // 'charset' => 'UTF-8',

        'logoText'  => '',
        'logoStyle' => 'info',
    ];

    /**
     * @var string Command delimiter char. e.g dev:serve
     */
    public $delimiter = ':'; // '/' ':'

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ErrorHandlerInterface Can custom error handler
     */
    protected $errorHandler;

    /**
     * @var Controller[]
     */
    protected $groupObjects = [];

    /**
     * Class constructor.
     *
     * @param array       $config
     * @param Input|null  $input
     * @param Output|null $output
     */
    public function __construct(array $config = [], Input $input = null, Output $output = null)
    {
        $this->runtimeCheck();
        $this->setConfig($config);

        $this->input  = $input ?: new Input();
        $this->output = $output ?: new Output();
        $this->router = new Router();

        $this->init();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function init(): void
    {
        $this->stats = [
            'startTime'   => microtime(true),
            'endTime'     => 0,
            'startMemory' => memory_get_usage(),
            'endMemory'   => 0,
        ];

        if (!$this->errorHandler) {
            $this->errorHandler = new ErrorHandler();
        }

        $this->registerErrorHandle();

        $this->logf(Console::VERB_DEBUG, 'console application init completed');
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isGlobalOption(string $name): bool
    {
        return isset(GlobalOption::KEY_MAP[$name]);
    }

    /**
     * @return array
     */
    public static function getGlobalOptions(): array
    {
        return self::$globalOptions;
    }

    /**
     * @param array $options
     */
    public function addGlobalOptions(array $options): void
    {
        if ($options) {
            self::$globalOptions = array_merge(self::$globalOptions, $options);
        }
    }

    /**********************************************************
     * app run
     **********************************************************/

    protected function prepareRun(): void
    {
        if ($this->input->getSameOpt(['no-color'])) {
            Style::setNoColor();
        }

        // date_default_timezone_set($this->config('timeZone', 'UTC'));
        // new AutoCompletion(array_merge($this->getCommandNames(), $this->getControllerNames()));
    }

    protected function beforeRun(): void
    {
    }

    /**
     * run application
     *
     * @param bool $exit
     *
     * @return int|mixed
     * @throws InvalidArgumentException
     */
    public function run(bool $exit = true)
    {
        $command = trim($this->input->getCommand(), $this->delimiter);

        $this->logf(Console::VERB_DEBUG, 'begin run the application, command is: %s', $command);

        try {
            $this->prepareRun();

            // like: help, version, list
            if ($this->filterSpecialCommand($command)) {
                return 0;
            }

            // call 'onBeforeRun' service, if it is registered.
            $this->fire(ConsoleEvent::ON_BEFORE_RUN, $this);
            $this->beforeRun();

            // do run ...
            $result = $this->dispatch($command);
        } catch (Throwable $e) {
            $this->fire(ConsoleEvent::ON_RUN_ERROR, $e, $this);
            $result = $e->getCode() === 0 ? $e->getLine() : $e->getCode();
            $this->handleException($e);
        }

        $this->stats['endTime'] = microtime(true);

        // call 'onAfterRun' service, if it is registered.
        $this->fire(ConsoleEvent::ON_AFTER_RUN, $this);
        $this->afterRun();

        if ($exit) {
            $this->stop(is_int($result) ? $result : 0);
        }

        return $result;
    }

    protected function afterRun(): void
    {
    }

    /**
     * @param int $code
     */
    public function stop(int $code = 0)
    {
        // call 'onAppStop' event, if it is registered.
        $this->fire(self::ON_STOP_RUN, $this);

        // display runtime info
        if ($this->isProfile()) {
            $title = '------ Runtime Stats(use --profile) ------';
            $stats = $this->stats;
            // output
            $this->stats = PhpHelper::runtime($stats['startTime'], $stats['startMemory'], $stats);
            $this->output->write('');
            $this->output->aList($this->stats, $title);
        }

        exit($code);
    }

    /**
     * @param string          $command
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|mixed
     */
    public function subRun(string $command, InputInterface $input, OutputInterface $output)
    {
        $app = $this->copy();
        $app->setInput($input);
        $app->setOutput($output);

        $this->debugf('copy application and run command(%s) with new input, output', $command);

        return $app->dispatch($command);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function runWithIO(InputInterface $input, OutputInterface $output): void
    {
        $app = $this->copy();
        $app->setInput($input);
        $app->setOutput($output);

        $this->debugf('copy application and run with new input, output');
        $app->run(false);
    }

    /**
     * @return $this
     */
    public function copy(): self
    {
        $app = clone $this;
        // reset something
        $app->groupObjects = [];

        return $app;
    }

    /**********************************************************
     * helper method for the application
     **********************************************************/

    /**
     * runtime env check
     */
    protected function runtimeCheck(): void
    {
        // check env
        if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'cli-server'], true)) {
            header('HTTP/1.1 403 Forbidden');
            exit("  403 Forbidden \n\n" . " current environment is CLI. \n" . " :( Sorry! Run this script is only allowed in the terminal environment!\n,You are not allowed to access this file.\n");
        }
    }

    /**
     * register error handle
     *
     * @throws InvalidArgumentException
     */
    protected function registerErrorHandle(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function(function () {
            if ($e = error_get_last()) {
                $this->handleError($e['type'], $e['message'], $e['file'], $e['line']);
            }
        });
    }

    /**
     * Running error handling
     *
     * @param int    $num
     * @param string $str
     * @param string $file
     * @param int    $line
     *
     * @throws InvalidArgumentException
     */
    public function handleError(int $num, string $str, string $file, int $line): void
    {
        $this->handleException(new ErrorException($str, 0, $num, $file, $line));
        $this->stop(-1);
    }

    /**
     * Running exception handling
     *
     * @param Throwable $e
     *
     * @throws InvalidArgumentException
     */
    public function handleException($e): void
    {
        // you can log error on sub class ...
        $this->errorHandler->handle($e, $this);
    }

    /**
     * @param string $command
     *
     * @return bool True will stop run, False will goon run give command.
     */
    protected function filterSpecialCommand(string $command): bool
    {
        if (!$command) {
            if ($this->input->getSameBoolOpt(GlobalOption::VERSION_OPTS)) {
                $this->showVersionInfo();
                return true;
            }

            if ($this->input->getSameBoolOpt(GlobalOption::HELP_OPTS)) {
                $this->showHelpInfo();
                return true;
            }

            if ($this->input->getBoolOpt(GlobalOption::ISHELL)) {
                $this->startInteractiveShell();
                return true;
            }

            // default run list command
            // $command = $this->defaultCommand ? 'list';
            $command = 'list';
            // is user command
        } elseif (!$this->isInternalCommand($command)) {
            return false;
        }

        switch ($command) {
            case 'help':
                $this->showHelpInfo($this->input->getFirstArg());
                break;
            case 'list':
                $this->showCommandList();
                break;
            case 'version':
                $this->showVersionInfo();
                break;
            default:
                return false;
        }
        return true;
    }

    /**********************************************************
     * start interactive shell
     **********************************************************/

    /**
     * start an interactive shell run
     */
    protected function startInteractiveShell(): void
    {
        $in = $this->input;
        $out = $this->output;

        $out->title("Welcome interactive shell for run application", [
            'titlePos' => Title::POS_MIDDLE,
        ]);

        if (!($hasPcntl = ProcessUtil::hasPcntl())) {
            $this->debugf('php is not enable "pcntl" extension, cannot listen CTRL+C signal');
        }

        // register signal.
        if ($hasPcntl) {
            ProcessUtil::installSignal(Signal::INT, static function () use ($out) {
                $out->colored("\nQuit by CTRL+C");
                exit(0);
            });
        }

        $prefix = $this->getParam('ishellName') ?: $this->getName();
        if (!$prefix) {
            $prefix = 'CMD';
        }

        $exitKeys = [
            'q'    => 1,
            'quit' => 1,
            'exit' => 1,
        ];

        while (true) {
            $line = Interact::readln("<comment>$prefix ></comment> ");
            if (strlen($line) < 5) {
                if (isset($exitKeys[$line])) {
                    break;
                }

                // "?" as show help
                if ($line === '?') {
                    $line = 'help';
                }
            }

            // listen signal.
            if ($hasPcntl) {
                ProcessUtil::dispatchSignal();
            }

            $args = LineParser::parseIt($line);
            $this->debugf('input line: %s, parsed args: %s', $line, json_encode($args));

            // reload and parse args
            $in->parse($args);
            $in->setFullScript($line);

            // \vdump($in);
            $this->run(false);
            $out->println('');
        }

        $out->colored("\nQuit. ByeBye!");
    }

    /**
     * @param string       $name
     * @param string|array $aliases
     *
     * @return $this
     */
    public function addAliases(string $name, $aliases): self
    {
        if ($name && $aliases) {
            $this->router->setAlias($name, $aliases, true);
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return array
     */
    public function getAliases(string $name = ''): array
    {
        return $this->router->getAliases($name);
    }

    /**
     * @param int    $level
     * @param string $format
     * @param mixed  ...$args
     */
    public function logf(int $level, string $format, ...$args): void
    {
        if ($this->getVerbLevel() < $level) {
            return;
        }

        Console::logf($level, $format, ...$args);
    }

    /**
     * @param string $format
     * @param mixed  ...$args
     */
    public function debugf(string $format, ...$args): void
    {
        if ($this->getVerbLevel() < Console::VERB_DEBUG) {
            return;
        }

        Console::logf(Console::VERB_DEBUG, $format, ...$args);
    }

    /**********************************************************
     * getter/setter methods
     **********************************************************/

    /**
     * @return string|null
     */
    public function getLogoText(): ?string
    {
        return $this->config['logoText'] ?? null;
    }

    /**
     * @param string      $logoTxt
     * @param string|null $style
     */
    public function setLogo(string $logoTxt, string $style = null): void
    {
        $this->config['logoText'] = $logoTxt;

        if ($style) {
            $this->config['logoStyle'] = $style;
        }
    }

    /**
     * @return string|null
     */
    public function getLogoStyle(): ?string
    {
        return $this->config['logoStyle'] ?? 'info';
    }

    /**
     * @param string $style
     */
    public function setLogoStyle(string $style): void
    {
        $this->config['logoStyle'] = $style;
    }

    /**
     * @return string
     */
    public function getRootPath(): string
    {
        return $this->getParam('rootPath', '');
    }

    /**
     * @return array
     */
    public function getInternalCommands(): array
    {
        return array_keys(static::$internalCommands);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isInternalCommand(string $name): bool
    {
        return isset(static::$internalCommands[$name]);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->config['name'];
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->config['version'];
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        if ($config) {
            $this->config = array_merge($this->config, $config);
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get config param value
     *
     * @param string $name
     * @param null|string|mixed $default
     *
     * @return array|string
     */
    public function getParam(string $name, $default = null)
    {
        return $this->config[$name] ?? $default;
    }

    /**
     * @return bool
     */
    public function isStrictMode(): bool
    {
        return (bool)$this->config['strictMode'];
    }

    /**
     * check is given verbose level
     *
     * @param int $level
     *
     * @return bool
     */
    public function isDebug(int $level = Console::VERB_DEBUG): bool
    {
        return $level <= $this->getVerbLevel();
    }

    /**
     * get current debug level value
     *
     * @return int
     */
    public function getVerbLevel(): int
    {
        $key = GlobalOption::DEBUG;

        return (int)$this->input->getLongOpt($key, (int)$this->config[$key]);
    }

    /**
     * is open profile
     *
     * @return boolean
     */
    public function isProfile(): bool
    {
        $key = GlobalOption::PROFILE;
        $def = (bool)$this->getParam($key, false);

        return $this->input->getBoolOpt($key, $def);
    }

    /**
     * is open interactive env
     *
     * @return bool
     */
    public function isInteractive(): bool
    {
        $key = GlobalOption::NO_INTERACTIVE;
        $def = (bool)$this->getParam($key, true);
        $val = $this->input->getBoolOpt($key, $def);

        return $val === false;
    }

    /**
     * @return ErrorHandlerInterface
     */
    public function getErrorHandler(): ErrorHandlerInterface
    {
        return $this->errorHandler;
    }

    /**
     * @param ErrorHandlerInterface $errorHandler
     */
    public function setErrorHandler(ErrorHandlerInterface $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }
}
