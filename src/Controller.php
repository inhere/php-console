<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace inhere\console;

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
     * 运行控制器的 action
     * @return mixed
     * @throws \HttpException
     */
    public function run()
    {
        $action = $this->action;

        if ($action && $this->input->sameOpt(['h','help'])) {
            return $this->helpCommand();
        }

        $result = '';
        $action = $action ?: $this->defaultAction;
        $action = trim($action, '/');

        // convert 'first-second' to 'firstSecond'
        if (strpos($action, '-')) {
            $action = ucwords(str_replace('-', ' ', $action));
            $action = str_replace(' ', '', lcfirst($action));
        }

        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        // the action method exists and only allow access public method.
        if (
            method_exists($this, $method) &&
            (($refMethod = new \ReflectionMethod($this, $method)) && $refMethod->isPublic())
        ) {
            // run action
            try {
                App::fire(App::ON_BEFORE_EXEC, [$this]);

                $this->beforeRun();
                $result = $this->$method($this->input, $this->output);
                $this->afterRun();

                App::fire(App::ON_AFTER_EXEC, [$this]);

            } catch (\Throwable $e) {
                App::fire(App::ON_EXEC_ERROR, [$e, $this]);
                $this->handleRuntimeException($e);
            }

            // if you defined the method '$this->notFoundCallback' , will call it
        } elseif (($notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
            $result = $this->{$notFoundCallback}($action);
        } else {
            // throw new \RuntimeException('Sorry, the page you want to visit already does not exist!');
            $this->output->error("Sorry, the controller command [$action] not exist!");
            $this->showCommandList();
        }

        return $result;
    }

    /**
     * Show help of the controller command group or specified command action
     * @usage <info>{name}/[action] -h</info> OR <info>{name}/help [action]</info> OR <info>{name} [action]</info>
     * @example home/help
     *    home/help index
     *    home/index -h
     *    home index
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

        // convert 'first-second' to 'firstSecond'
        if (strpos($action, '-')) {
            $action = ucwords(str_replace('-', ' ', $action));
            $action = str_replace(' ', '', lcfirst($action));
        }

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

        if (!($desc = static::DESCRIPTION)) {
            $desc = Annotation::description($ref->getDocComment()) ?: 'No Description';
        }

        $suffix = $this->actionSuffix;
        $suffixLen = Helper::strLen($suffix);
        $text = "<comment>Description:</comment>
  $desc
<comment>Usage</comment>:
  $sName/[command] [options] [arguments]
<comment>Group Name:</comment>
  <info>$sName</info>";

        $this->write($text);

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

        $commands[] = "\nFor more information please use: <info>$sName/help [command]</info>";
        $this->output->aList($commands, '<comment>Commands:</comment>');
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
            $this->action = $action;
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
