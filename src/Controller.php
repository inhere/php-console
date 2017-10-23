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
 * Class Command
 * @package Inhere\Console
 */
abstract class Controller extends AbstractCommand implements ControllerInterface
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
     * @var string
     */
    public $delimiter = '/'; // '/' ':'

    /**
     * @var bool
     */
    protected $showMore = true;

    /**
     * @var bool
     */
    private $standAlone = false;

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
        $action = Helper::transName(trim($action, $this->delimiter));

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
        $sName = lcfirst(self::getName() ?: $ref->getShortName());

        if (!($classDes = self::getDescription())) {
            $classDes = Annotation::description($ref->getDocComment()) ?: 'No Description for the console controller';
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
//                $this->showMore ? "\nMore information please use: <cyan>$script {$name}{command} -h</cyan>" : ''
            ],
        ]);

        $this->showMore && $this->write("More information please use: <cyan>$script {$name}{command} -h</cyan>");
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

}
