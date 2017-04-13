<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace inhere\console;

/**
 * Class Command
 * @package inhere\console
 */
abstract class Controller extends AbstractCommand
{
    /**
     * @var string
     */
    protected $defaultAction = 'help';

    /**
     * @var string
     */
    protected $actionSuffix = 'Command';

    /**
     * @var string
     */
    protected $notFoundCallback = 'notFound';

    /**
     * 运行控制器的 action
     * @param $action
     * @return mixed
     * @throws \HttpException
     */
    public function run($action = '')
    {
        $showCmdHelp = $action && ($this->input->getBool('h') || $this->input->getBool('help'));

        if ($showCmdHelp) {
            return $this->helpCommand($action);
        }

        $result = '';
        $action = $action?: $this->defaultAction;

        if ( $params = func_get_args() ) {
            array_shift($params);// the first argument is `$action`
        }

        $action = trim($action, '/');

        // convert 'first-second' to 'firstSecond'
        if ( strpos($action, '-') ) {
            $action = ucwords(str_replace('-', ' ', $action));
            $action = str_replace(' ','',lcfirst($action));
        }

        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        // the action method exists and only allow access public method.
        if (
            method_exists($this, $method) &&
            (($refMethod = new \ReflectionMethod($this, $method)) && $refMethod->isPublic())
        ) {
            // run action
            try {
                $this->beforeRun($action);

                $result = $params ? call_user_func_array([$this, $method], $params) : $this->$method();

                $this->afterRun($action);

            } catch (\Exception $e) {
                $this->handleRuntimeException($e);
            }

            // if you defined the method '$this->notFoundCallback' , will call it
        } elseif ( ( $notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
            $result = $this->{$notFoundCallback}($action);
        } else {
            // throw new \RuntimeException('Sorry, the page you want to visit already does not exist!');
            $this->output->error("Sorry, the controller command [$action] not exist!");
            $this->showCommandList();
        }

        return $result;
    }

    /**
     * handle action runtime exception
     *
     * @param  \Exception $e
     * @throws \Exception
     */
    protected function handleRuntimeException(\Exception $e)
    {
        throw $e;
    }

    protected function beforeRun($action)
    {}

    protected function afterRun($action)
    {}

    /**
     * Show help of the controller command group or specified command action
     * @usage <info>{name}/[action] -h</info> OR <info>{name}/help [action]</info> OR <info>{name} [action]</info>
     * @example home/help
     *    home/help index
     *    home/index -h
     *    home index
     *
     * @param string $action
     * @return int
     */
    final public function helpCommand($action = '')
    {
        if (!$action && !($action = $this->input->getFirstArg()) ) {
            $this->showCommandList();
            return 0;
        }

        // convert 'first-second' to 'firstSecond'
        if ( strpos($action, '-') ) {
            $action = ucwords(str_replace('-', ' ', $action));
            $action = str_replace(' ','',lcfirst($action));
        }

        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        $ref = new \ReflectionClass($this);
        $sName = lcfirst($this->getName() ?: $ref->getShortName());

        if ( !$ref->hasMethod($method) || !$ref->getMethod($method)->isPublic() ) {
            $this->write("Command [<info>$sName/$action</info>] don't exist or don't allow access in the class.");
            return 0;
        }

        $m = $ref->getMethod($method);
        $tags = $this->parseDocCommentTags($m->getDocComment());

        foreach ($tags as $tag => $msg) {
            if (!self::$allowTags || in_array($tag, self::$allowTags, true)) {
                $tag = ucfirst($tag);
                $this->write("<comment>$tag:</comment>\n   $msg\n");
            }
        }

        return 0;
    }

    /**
     * show command list of the controller class
     */
    final protected function showCommandList()
    {
        $ref = new \ReflectionClass($this);

        $class = $ref->getName();
        $sName = lcfirst($this->getName() ?: $ref->getShortName());
        $this->write("This is in the console controller [<bold>$class</bold>]\n");

        if ( !($desc = static::DESCRIPTION) ) {
            $desc = $this->parseDocCommentDetail($ref->getDocComment()) ?: 'No Description';
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
                $desc = $this->parseDocCommentSummary($m->getDocComment());
                $length = strlen($this->actionSuffix);
                $cmd = '';

                if ( $length ) {
                    if ( substr($mName, - $length) === $this->actionSuffix ) {
                        $cmd = substr($mName, 0, - $length);
                    }

                } else {
                    $cmd = $mName;
                }

                if ( $cmd ) {
                    //$this->write("  <info>$cmd</info>  $desc");
                    $commands[$cmd] = $desc;
                }
            }
        }

        $commands[] = "\nFor more information please use: <info>$sName/help [command]</info>";
        $this->output->aList($commands, '<comment>Sub-Commands:</comment>');
    }

    /**
     * @return string
     */
    public function getDefaultAction()
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
    public function getActionSuffix()
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

    /*
     * 以下三个方法来自 yii2 console/Controller.php
     */

    /**
     * Parses the comment block into tags.
     * @param string $comment the comment block
     * @return array the parsed tags
     */
    protected function parseDocCommentTags($comment)
    {
//        $comment = $reflection->getDocComment();
        $comment = "@description \n" . strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($comment, '/'))), "\r", '');

        $parts = preg_split('/^\s*@/m', $comment, -1, PREG_SPLIT_NO_EMPTY);
        $tags = [];

        foreach ($parts as $part) {
            if (preg_match('/^(\w+)(.*)/ms', trim($part), $matches)) {
                $name = $matches[1];
                if (!isset($tags[$name])) {
                    $tags[$name] = trim($matches[2]);
                } elseif (is_array($tags[$name])) {
                    $tags[$name][] = trim($matches[2]);
                } else {
                    $tags[$name] = [$tags[$name], trim($matches[2])];
                }
            }
        }
        return $tags;
    }

    /**
     * Returns the first line of docBlock.
     *
     * @param  $comment
     * @return string
     */
    protected function parseDocCommentSummary($comment)
    {
        $docLines = preg_split('~\R~u', $comment);

        if (isset($docLines[1])) {
            return trim($docLines[1], "\t *");
        }

        return '';
    }

    /**
     * Returns full description from the doc block.
     *
     * @param  $comment
     * @return string
     */
    protected function parseDocCommentDetail($comment)
    {
        $comment = strtr(trim(preg_replace('/^\s*\**( |\t)?/m', '', trim($comment, '/'))), "\r", '');

        if (preg_match('/^\s*@\w+/m', $comment, $matches, PREG_OFFSET_CAPTURE)) {
            $comment = trim(substr($comment, 0, $matches[0][1]));
        }
//        if ($comment !== '') {
//            return $this->write($comment);
//        }

        return $comment;
    }
}
