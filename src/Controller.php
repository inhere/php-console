<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace Inhere\Console;

use Inhere\Console\Base\AbstractCommand;
use Inhere\Console\Base\ControllerInterface;
use Inhere\Console\IO\Input;
use Inhere\Console\IO\Output;
use Inhere\Console\Utils\FormatUtil;
use Inhere\Console\Utils\Helper;
use Inhere\Console\Utils\Annotation;

/**
 * Class Controller
 * @package Inhere\Console
 */
abstract class Controller extends AbstractCommand implements ControllerInterface
{
    /**
     * @var array
     */
    private static $commandAliases;

    /** @var string */
    private $action;

    /** @var bool */
    private $standAlone = false;

    /** @var string */
    private $defaultAction = 'help';

    /** @var string */
    private $actionSuffix = 'Command';

    /** @var string */
    protected $notFoundCallback = 'notFound';

    /** @var string */
    protected $delimiter = ':'; // '/' ':'

    /**
     * define command alias map
     * @return array
     */
    protected static function commandAliases()
    {
        return [
            // alias => command
            // 'i'  => 'install',
        ];
    }

    /**
     * @param string $command
     * @return int
     */
    public function run($command = '')
    {
        $this->action = $this->getRealCommandName(trim($command, $this->delimiter));

        if (!$this->action) {
            return $this->showHelp();
        }

        return parent::run($command);
    }

    /**
     * load command configure
     */
    final protected function configure()
    {
        if ($action = $this->action) {
            $method = $action . 'Configure';

            if (method_exists($this, $method)) {
                $this->$method();
            }
        }
    }

    /**
     * 运行控制器的 action
     * @param  Input $input
     * @param  Output $output
     * @return mixed
     */
    protected function execute($input, $output)
    {
        $action = FormatUtil::camelCase(trim($this->action ?: $this->defaultAction, $this->delimiter));
        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        // the action method exists and only allow access public method.
        if (method_exists($this, $method) && (($rfm = new \ReflectionMethod($this, $method)) && $rfm->isPublic())) {
            // run action
            $status = $this->$method($input, $output);

            // if you defined the method '$this->notFoundCallback' , will call it
        } elseif (($notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
            $status = $this->{$notFoundCallback}($action);
        } else {
            $group = static::getName();
            $status = -1;
            $output->liteError("Sorry, The command '$action' not exist of the group '{$group}'!");

            // find similar command names
            $similar = Helper::findSimilar($action, $this->getAllCommandMethods(null, true));

            if ($similar) {
                $output->write(sprintf("\nMaybe what you mean is:\n    <info>%s</info>", implode(', ', $similar)));
            } else {
                $this->showCommandList();
            }
        }

        return $status;
    }

    /**
     * @return int
     */
    protected function showHelp()
    {
        if (true === parent::showHelp()) {
            return 0;
        }

        return $this->helpCommand();
    }

    /**
     * Show help of the controller command group or specified command action
     * @usage <info>{name}/[command] -h</info> OR <info>{command} [command]</info> OR <info>{name} [command] -h</info>
     * @example
     *  {script} {name} -h
     *  {script} {name}/help
     *  {script} {name}/help index
     *  {script} {name}/index -h
     *  {script} {name} index
     *
     * @return int
     */
    final public function helpCommand()
    {
        $action = $this->action;

        // show all commands of the controller
        if (!$action && !($action = $this->input->getFirstArg())) {
            $this->showCommandList();
            return 0;
        }

        $action = FormatUtil::camelCase($action);
        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;
        $aliases = self::getCommandAliases($action);

        // show help info for a command.
        return $this->showHelpByMethodAnnotation($method, $action, $aliases);
    }

    /**
     * show command list of the controller class
     */
    final public function showCommandList()
    {
        $ref = new \ReflectionClass($this);
        $sName = lcfirst(self::getName() ?: $ref->getShortName());

        if (!($classDes = self::getDescription())) {
            $classDes = Annotation::description($ref->getDocComment()) ?: 'No description for the console controller';
        }

        $commands = [];
        $defCommandDes = 'No description message';

        foreach ($this->getAllCommandMethods($ref) as $cmd => $m) {
            $desc = Annotation::firstLine($m->getDocComment()) ?: $defCommandDes;

            // is a annotation tag
            if ($desc[0] === '@') {
                $desc = $defCommandDes;
            }

            if ($cmd) {
                $aliases = self::getCommandAliases($cmd);
                $extra = $aliases ? Helper::wrapTag(' [alias: ' . implode(',', $aliases) . ']', 'info') : '';
                $commands[$cmd] = $desc . $extra;
            }
        }

        // sort commands
        ksort($commands);

        // move 'help' to last.
        if ($helpCmd = $commands['help'] ?? null) {
            unset($commands['help']);
            $commands['help'] = $helpCmd;
        }

        $script = $this->getScriptName();

        if ($this->standAlone) {
            $name = $sName . ' ';
            $usage = "$script <info>{command}</info> [arguments ...] [options ...]";
        } else {
            $name = $sName . $this->delimiter;
            $usage = "$script {$name}<info>{command}</info> [arguments ...] [options ...]";
        }

        $this->output->startBuffer();
        $this->output->write(ucfirst($classDes) . PHP_EOL);
        $this->output->mList([
            'Usage:' => $usage,
            //'Group Name:' => "<info>$sName</info>",
            'Options:' => [
                '-h, --help' => 'Show help of the command group or specified command action',
            ],
            'Commands:' => $commands,
        ], [
            'sepChar' => '  ',
        ]);

        $this->write(sprintf(
            "More information about a command, please use: <cyan>$script $name{command} -h</cyan>",
            $this->standAlone ? ' ' . $name : ''
        ));
        $this->output->flush();
    }

    /**
     * @param \ReflectionClass|null $ref
     * @param bool $onlyName
     * @return \Generator
     */
    protected function getAllCommandMethods(\ReflectionClass $ref = null, $onlyName = false)
    {
        $ref = $ref ?: new \ReflectionObject($this);

        $suffix = $this->actionSuffix;
        $suffixLen = Helper::strLen($suffix);

        foreach ($ref->getMethods() as $m) {
            $mName = $m->getName();

            if ($m->isPublic() && substr($mName, -$suffixLen) === $suffix) {
                // suffix is empty ?
                $cmd = $suffix ? substr($mName, 0, -$suffixLen) : $mName;

                if ($onlyName) {
                    yield $cmd;
                } else {
                    yield $cmd => $m;
                }
            }
        }
    }

    /**
     * @param string $name
     * @return mixed|string
     */
    protected function getRealCommandName(string $name)
    {
        if (!$name) {
            return $name;
        }

        $map = self::getCommandAliases();

        return $map[$name] ?? $name;
    }

    /**************************************************************************
     * getter/setter methods
     **************************************************************************/

    /**
     * @param string|null $name
     * @return array
     */
    public static function getCommandAliases(string $name = null)
    {
        if (null === self::$commandAliases) {
            self::$commandAliases = static::commandAliases();
        }

        if ($name) {
            return self::$commandAliases ? array_keys(self::$commandAliases, $name, true) : [];
        }

        return self::$commandAliases;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return $this
     */
    public function setAction(string $action)
    {
        if ($action) {
            $this->action = FormatUtil::camelCase($action);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultAction(): string
    {
        return $this->defaultAction;
    }

    /**
     * @param string $defaultAction
     */
    public function setDefaultAction($defaultAction)
    {
        $this->defaultAction = $defaultAction;
    }

    /**
     * @return string
     */
    public function getActionSuffix(): string
    {
        return $this->actionSuffix;
    }

    /**
     * @param string $actionSuffix
     */
    public function setActionSuffix($actionSuffix)
    {
        $this->actionSuffix = $actionSuffix;
    }

    /**
     * @return string
     */
    public function getNotFoundCallback()
    {
        return $this->notFoundCallback;
    }

    /**
     * @param string $notFoundCallback
     */
    public function setNotFoundCallback($notFoundCallback)
    {
        $this->notFoundCallback = $notFoundCallback;
    }

    /**
     * @return bool
     */
    public function isStandAlone(): bool
    {
        return $this->standAlone;
    }

    /**
     * @param bool $standAlone
     */
    public function setStandAlone($standAlone = true)
    {
        $this->standAlone = (bool)$standAlone;
    }

    /**
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }
}
