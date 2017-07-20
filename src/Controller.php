<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace inhere\console;

use inhere\console\io\Input;
use inhere\console\io\Output;
use inhere\console\utils\Helper;
use inhere\console\utils\Annotation;

/**
 * Class Command
 * @package inhere\console
 */
abstract class Controller extends AbstractCommand
{
    /**
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $defaultAction = 'help';

    /**
     * @var string
     */
    private $actionSuffix = 'Command';

    /**
     * @var string
     */
    protected $notFoundCallback = 'notFound';

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
     */
    protected function execute($input, $output)
    {
        $action = $this->action ?: $this->defaultAction;
        $action = Helper::transName(trim($action, '/'));

        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        // the action method exists and only allow access public method.
        if (method_exists($this, $method) && (($rfm = new \ReflectionMethod($this, $method)) && $rfm->isPublic())) {
            // run action
            $status = $this->$method($input, $output);

            // if you defined the method '$this->notFoundCallback' , will call it
        } elseif (($notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
            $status = $this->{$notFoundCallback}($action);
        } else {
            $status = -1;
            $this->output->liteError("Sorry, the console controller command [$action] not exist!");
            $this->showCommandList();
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
     * @usage <info>{name}/[command] -h</info> OR <info>{name}/help [command]</info> OR <info>{name} [command]</info>
     * @example
     * {script} {name} -h
     * {script} {name}/help
     * {script} {name}/help index
     * {script} {name}/index -h
     * {script} {name} index
     *
     * @return int
     */
    final public function helpCommand()
    {
        $action = $this->action;

        if (!$action && !($action = $this->input->getFirstArg())) {
            $this->showCommandList();
            return 0;
        }

        $action = Helper::transName($action);
        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        return $this->showHelpByMethodAnnotation($method, $action);
    }

    /**
     * show command list of the controller class
     */
    final protected function showCommandList()
    {
        $ref = new \ReflectionClass($this);

        $class = $ref->getName();
        $sName = lcfirst(self::getName() ?: $ref->getShortName());
        $this->write("This is in the console controller [<bold>$class</bold>]\n");

        if (!($classDes = self::getDescription())) {
            $classDes = Annotation::description($ref->getDocComment()) ?: 'No Description';
        }

        $suffix = $this->actionSuffix;
        $suffixLen = Helper::strLen($suffix);

        $commands = [];
        foreach ($ref->getMethods() as $m) {
            $mName = $m->getName();

            if ($m->isPublic() && substr($mName, -$suffixLen) === $suffix) {
                $desc = Annotation::firstLine($m->getDocComment());
                $length = strlen($this->actionSuffix);
                $cmd = '';

                if ($length) {
                    if (substr($mName, -$length) === $this->actionSuffix) {
                        $cmd = substr($mName, 0, -$length);
                    }

                } else {
                    $cmd = $mName;
                }

                if ($cmd) {
                    //$this->write("  <info>$cmd</info>  $desc");
                    $commands[$cmd] = $desc;
                }
            }
        }

        $this->output->mList([
            'Description:' => $classDes,
            'Usage:' => "$sName/[command] [arguments] [options]",
            'Group Name:' => "<info>$sName</info>",
            'Commands:' => $commands,
            'Options:' => [
                '--help,-h' => 'Show help of the command group or specified command action',
                "\nMore information please use: <cyan>$sName/[command] -h</cyan> OR <cyan>$sName/help [command]</cyan>"
            ],
        ]);
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
            $this->action = Helper::transName($action);
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
}
