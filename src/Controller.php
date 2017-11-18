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
use Inhere\Console\Utils\Helper;
use Inhere\Console\Utils\Annotation;

/**
 * Class Controller
 * @package Inhere\Console
 */
abstract class Controller extends AbstractCommand implements ControllerInterface
{
    /** @var string */
    private $action;

    /** @var string  */
    private $defaultAction = 'help';

    /** @var string  */
    private $actionSuffix = 'Command';

    /** @var string  */
    protected $notFoundCallback = 'notFound';

    /** @var string  */
    protected $delimiter = ':'; // '/' ':'

    /** @var bool  */
    private $standAlone = false;

    /**
     * @param string $command
     * @return int
     */
    public function run($command = '')
    {
        if (!$this->action = trim($command)) {
            return $this->showHelp();
        }

        return parent::run($command);
    }

    /**
     * load command configure
     */
    protected function configure()
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
     * @throws \ReflectionException
     */
    protected function execute($input, $output)
    {
        $action = Helper::camelCase(trim($this->action ?: $this->defaultAction, $this->delimiter));
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

            // find similar command names by similar_text()
            $similar = [];

            foreach ($this->getAllCommandMethods() as $cmd => $refM) {
                similar_text($action, $cmd, $percent);

                if (45 <= (int)$percent) {
                    $similar[] = $cmd;
                }
            }

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
     * @throws \ReflectionException
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
     * @throws \ReflectionException
     */
    final public function helpCommand()
    {
        $action = $this->action;

        // show all commands of the controller
        if (!$action && !($action = $this->input->getFirstArg())) {
            $this->showCommandList();
            return 0;
        }

        $action = Helper::camelCase($action);
        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        // show help info for a command.
        return $this->showHelpByMethodAnnotation($method, $action);
    }

    /**
     * show command list of the controller class
     * @throws \ReflectionException
     */
    final public function showCommandList()
    {
        $ref = new \ReflectionClass($this);
        $sName = lcfirst(self::getName() ?: $ref->getShortName());

        if (!($classDes = self::getDescription())) {
            $classDes = Annotation::description($ref->getDocComment()) ?: 'No Description for the console controller';
        }

        $commands = [];
        foreach ($this->getAllCommandMethods($ref) as $cmd => $m) {
            $desc = Annotation::firstLine($m->getDocComment());

            if ($cmd) {
                $commands[$cmd] = $desc;
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
            $usage = "$script <info>{command}</info> [arguments] [options]";
        } else {
            $name = $sName . $this->delimiter;
            $usage = "$script {$name}<info>{command}</info> [arguments] [options]";
        }

        $this->output->mList([
            'Description:' => $classDes,
            'Usage:' => $usage,
            //'Group Name:' => "<info>$sName</info>",
            'Commands:' => $commands,
            'Options:' => [
                '-h,--help' => 'Show help of the command group or specified command action',
            ],
        ]);

        $this->write(sprintf(
            "To see more information about a command, please use: <cyan>$script {command} -h</cyan>",
            $this->standAlone ? ' ' . $name : ''
        ));
    }

    /**
     * @param \ReflectionClass|null $ref
     * @return \Generator
     */
    protected function getAllCommandMethods(\ReflectionClass $ref = null)
    {
        $ref = $ref ?: new \ReflectionObject($this);

        $suffix = $this->actionSuffix;
        $suffixLen = Helper::strLen($suffix);

        foreach ($ref->getMethods() as $m) {
            $mName = $m->getName();

            if ($m->isPublic() && substr($mName, - $suffixLen) === $suffix) {
                // suffix is empty ?
                $cmd = $suffix ? substr($mName, 0, -$suffixLen) : $mName;

                yield $cmd => $m;
            }
        }
    }

    /**************************************************************************
     * getter/setter methods
     **************************************************************************/

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
            $this->action = Helper::camelCase($action);
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
