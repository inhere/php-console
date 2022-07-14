<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console;

use ErrorException;
use Inhere\Console\Component\ErrorHandler;
use Inhere\Console\Component\Formatter\Title;
use Inhere\Console\Component\Router;
use Inhere\Console\Contract\ApplicationInterface;
use Inhere\Console\Contract\ErrorHandlerInterface;
use Inhere\Console\Decorate\ApplicationHelpTrait;
use Inhere\Console\Decorate\InputOutputAwareTrait;
use Inhere\Console\Decorate\SimpleEventAwareTrait;
use Inhere\Console\Decorate\StyledOutputAwareTrait;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Util\Helper;
use Inhere\Console\Util\Interact;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use Throwable;
use Toolkit\Cli\Style;
use Toolkit\Cli\Util\LineParser;
use Toolkit\PFlag\FlagUtil;
use Toolkit\PFlag\SFlags;
use Toolkit\Stdlib\Helper\DataHelper;
use Toolkit\Stdlib\Helper\PhpHelper;
use Toolkit\Stdlib\OS;
use Toolkit\Sys\Proc\ProcessUtil;
use Toolkit\Sys\Proc\Signal;
use function array_keys;
use function array_merge;
use function array_shift;
use function error_get_last;
use function header;
use function in_array;
use function is_int;
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
    protected static array $internalCommands = [
        'version' => 'Show application version information',
        'help'    => 'Show application help information',
        'list'    => 'List all group and alone commands',
    ];

    /** @var array Application runtime stats */
    protected array $stats = [
        'startTime'   => 0,
        'endTime'     => 0,
        'startMemory' => 0,
        'endMemory'   => 0,
    ];

    /**
     * @var int
     */
    private int $exitCode = 0;

    /**
     * @var string
     */
    public string $delimiter = ':'; // '/' ':'

    /**
     * @var string
     */
    protected string $commandName = '';

    /*
     * @var string Command delimiter char. e.g dev:serve
     */

    /** @var array Application config data */
    protected array $config = [
        'name'           => 'My Console Application',
        'desc'           => 'This is my console application',
        'version'        => '0.5.1',
        'homepage'       => '', // can provide you app homepage url
        'publishAt'      => '2017.03.24',
        'updateAt'       => '2019.01.01',
        'rootPath'       => '',
        'ishellName'     => '', // name prefix on i-shell env.
        'strictMode'     => false,
        // hide root path on dump error stack
        'hideRootPath'   => true,
        // ---- global options
        'no-interactive' => false,
        'debug'          => Console::VERB_ERROR,
        'profile'        => false,

        // 'timeZone' => 'Asia/Shanghai',
        // 'env' => 'prod', // dev test prod
        // 'charset' => 'UTF-8',

        // other settings.
        'logoText'       => '',
        'logoStyle'      => 'info',
    ];

    /**
     * @var Router
     */
    protected Router $router;

    /**
     * @var ErrorHandlerInterface Can custom error handler
     */
    protected ErrorHandlerInterface $errorHandler;

    /**
     * @var Controller[]
     */
    protected array $groupObjects = [];

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

        $this->flags  = new SFlags();
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

        $this->errorHandler = new ErrorHandler([
            'rootPath'     => $this->config['rootPath'],
            'hideRootPath' => (bool)$this->config['hideRootPath'],
        ]);

        $this->registerErrorHandle();

        $this->logf(Console::VERB_DEBUG, 'console application init completed');
    }

    /**********************************************************
     * app run
     **********************************************************/

    protected function initForRun(Input $input): void
    {
        $input->setGfs($this->flags);

        // binding global options
        $this->logf(Console::VERB_DEBUG, 'init - add and binding global options');
        $this->flags->addOptsByRules(GlobalOption::getOptions());
        $this->flags->setScriptFile($input->getScriptFile());
        $this->flags->setAutoBindArgs(false);

        // set helper render
        $this->flags->setHelpRenderer(function (): void {
            $this->showHelpInfo();
            $this->stop();
        });

        // parse options
        $this->logf(Console::VERB_DEBUG, 'init - begin parse global options');
        $this->flags->parse($input->getFlags());

        // set debug to error handler
        $this->errorHandler->setDebug($this->isDebug());
    }

    protected function prepareRun(): void
    {
        // if ($this->input->getSameOpt(['no-color'])) {
        // if disable color
        if ($this->flags->getOpt(GlobalOption::NO_COLOR)) {
            Style::setNoColor();
        }

        // date_default_timezone_set($this->config('timeZone', 'UTC'));
        // new AutoCompletion(array_merge($this->getCommandNames(), $this->getControllerNames()));
    }

    /**
     * @return bool Return true for continue handle.
     */
    protected function handleGlobalOption(): bool
    {
        // TIP help option has been handled by `initGlobalFlags.setHelpRenderer`

        // if ($this->input->getSameBoolOpt(GlobalOption::VERSION_OPTS)) {
        if ($this->flags->getOpt(GlobalOption::VERSION)) {
            $this->showVersionInfo();
            return false;
        }

        // if ($this->input->getBoolOpt(GlobalOption::ISHELL)) {
        if ($this->flags->getOpt(GlobalOption::ISHELL)) {
            $this->startInteractiveShell();
            return false;
        }

        return true;
    }

    protected function beforeRun(): bool
    {
        // eg: --version, --help
        if (!$this->handleGlobalOption()) {
            return false;
        }

        $remainArgs = $this->flags->getRawArgs();

        // not input command
        if (!$remainArgs) {
            $this->showCommandList();
            return false;
        }

        // remain first arg as command name.
        $firstArg = array_shift($remainArgs);

        if (!Helper::isValidCmdPath($firstArg)) {
            $evtName = ConsoleEvent::ON_NOT_FOUND;
            if (true === $this->fire($evtName, $firstArg, $this)) {
                $this->debugf('user custom handle the invalid command: %s, event: %s', $firstArg, $evtName);
            } else {
                $this->output->liteError("input an invalid command name: $firstArg");
                $this->showCommandList();
            }

            return false;
        }

        $command = trim($firstArg, $this->delimiter);
        // save name.
        $this->commandName = $command;
        $this->flags->popFirstRawArg();
        $this->input->setCommand($command);
        $this->logf(Console::VERB_DEBUG, 'app - match and found the command: %s', $command);

        // like: help, version, list
        if (!$this->handleGlobalCommand($command)) {
            return false;
        }

        // continue
        return true;
    }

    /**
     * run application
     *
     * @param bool $exit
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function run(bool $exit = true): mixed
    {
        try {
            // init
            $this->initForRun($this->input);

            $this->prepareRun();

            // fire event ON_BEFORE_RUN, if it is registered.
            $this->fire(ConsoleEvent::ON_BEFORE_RUN, $this);
            if (!$this->beforeRun()) {
                return 0;
            }

            // do run ...
            $command = $this->commandName;
            $result  = $this->dispatch($command, $this->flags->getRawArgs());
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
    #[NoReturn]
    public function stop(int $code = 0): void
    {
        if ($code === 0) {
            $code = $this->exitCode;
        }

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
     * @param array $args
     *
     * @return mixed
     */
    public function runWithArgs(array $args): mixed
    {
        $this->input->setFlags($args);
        return $this->run(false);
    }

    /**
     * @param Input  $input
     * @param Output $output
     */
    public function runWithIO(Input $input, Output $output): void
    {
        $app = $this->copy();
        $app->setInput($input);
        $app->setOutput($output);

        $this->debugf('copy application and run with new input, output');
        $app->run(false);
    }

    /**
     * @param string $command
     * @param Input  $input
     * @param Output $output
     *
     * @return mixed
     */
    public function subRun(string $command, Input $input, Output $output): mixed
    {
        $app = $this->copy();
        $app->setInput($input);
        $app->setOutput($output);

        $this->debugf('copy application and run command(%s) with new input, output', $command);

        return $app->dispatch($command);
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
        if (!in_array(PHP_SAPI, ['cli', 'phpdbg', 'cli-server', 'micro'], true)) {
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
        register_shutdown_function(function (): void {
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
    #[NoReturn]
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
    public function handleException(Throwable $e): void
    {
        // you can log error on sub class ...
        $this->errorHandler->handle($e);
    }

    /**
     * @param string $command
     *
     * @return bool False will stop run
     */
    protected function handleGlobalCommand(string $command): bool
    {
        if (!$this->isInternalCommand($command)) {
            return true;
        }

        $this->debugf('run the global command: %s', $command);
        switch ($command) {
            case 'help':
                $cmd  = '';
                $args = $this->flags->getRawArgs();
                if ($args && FlagUtil::isValidName($args[0])) {
                    $cmd = $args[0];
                }

                $this->showHelpInfo($cmd);
                break;
            case 'list':
                $this->showCommandList();
                break;
            case 'version':
                $this->showVersionInfo();
                break;
        }
        return false;
    }

    /**********************************************************
     * start interactive shell
     **********************************************************/

    /**
     * start an interactive shell run
     */
    protected function startInteractiveShell(): void
    {
        // $in  = $this->input;
        $out = $this->output;
        $out->title('Welcome interactive shell for run application', [
            'titlePos' => Title::POS_MIDDLE,
        ]);

        if (!($hasPcntl = ProcessUtil::hasPcntl())) {
            $this->debugf('php is not enable "pcntl" extension, cannot listen CTRL+C signal');
        }

        // register signal.
        if ($hasPcntl) {
            ProcessUtil::installSignal(Signal::INT, static function () use ($out): void {
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

        // set helper render
        $this->flags->setHelpRenderer(function (): void {
            $this->showHelpInfo();
            // $this->stop(); not exit
        });

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
            $this->debugf('ishell - input line: %s, split args: %s', $line, DataHelper::toString($args));

            // reload and parse args
            $this->flags->resetResults();
            // $this->flags->setTrustedOpt('debug');
            $this->flags->parse($args);
            // $in->parse($args);
            // $in->setFullScript($line);

            // fire event ON_BEFORE_RUN, if it is registered.
            $this->fire(ConsoleEvent::ON_BEFORE_RUN, $this);
            if (!$this->beforeRun()) {
                continue;
            }

            // do run ...
            $command = $this->commandName;
            $this->dispatch($command, $this->flags->getRawArgs());

            $this->debugf('ishell - the command "%s" run completed', $command);
            $out->println('');
        }

        $out->colored("\nQuit. ByeBye!");
    }

    /**
     * @param string       $name
     * @param array|string $aliases
     *
     * @return $this
     */
    public function addAliases(string $name, array|string $aliases): self
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
     * @param string $name
     * @param array  $default
     *
     * @return array
     */
    public function getArrayParam(string $name, array $default = []): array
    {
        if (isset($this->config[$name])) {
            return (array)$this->config[$name];
        }

        return $default;
    }

    /**
     * Get config param value
     *
     * @param string $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getParam(string $name, mixed $default = null): mixed
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
        $optKey = GlobalOption::DEBUG;

        // feat: support set debug level by ENV var: CONSOLE_DEBUG
        $envVal = OS::getEnvStrVal(Console::DEBUG_ENV_KEY);
        if ($envVal !== '') {
            $setVal = (int)$envVal;
        } else {
            $setVal = (int)$this->config[$optKey];
        }

        if (!$this->flags->hasOpt($optKey)) {
            return $setVal;
        }

        // return (int)$this->input->getLongOpt($key, $setVal);
        return $this->flags->getOpt($optKey, $setVal);
    }

    /**
     * is open profile
     *
     * @return boolean
     */
    public function isProfile(): bool
    {
        $optKey  = GlobalOption::PROFILE;
        $default = (bool)$this->getParam($optKey, false);

        // return $this->input->getBoolOpt($key, $def);
        return $this->flags->getOpt($optKey, $default);
    }

    /**
     * is open interactive env
     *
     * @return bool
     */
    public function isInteractive(): bool
    {
        $optKey  = GlobalOption::NO_INTERACTIVE;
        $default = (bool)$this->getParam($optKey, false);

        // $value = $this->input->getBoolOpt($optKey, $default);
        return $this->flags->getOpt($optKey, $default) === false;
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

    /**
     * @return string
     */
    public function getCommandName(): string
    {
        return $this->commandName;
    }

    /**
     * @return int
     */
    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @param int $exitCode
     */
    public function setExitCode(int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }
}
