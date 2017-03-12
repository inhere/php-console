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

/**
 * Class Command
 * @package inhere\console
 */
abstract class Controller
{
    // please use the const setting current controller description
    const DESCRIPTION = '';

    /**
     * @var Input
     */
    protected $input;

    /**
     * @var Output
     */
    protected $output;

    /**
     * @var string
     */
    protected $defaultAction = 'help';

    /**
     * @var string
     */
    protected $actionSuffix = 'Command';

    /**
     * allow display message tags in the command
     * @var array
     */
    protected static $allowTags = ['description', 'usage', 'example'];

    /**
     * @var string
     */
    protected $notFoundCallback = 'notFound';

    /**
     * @var string
     */
    private $name = '';

    /**
     * Command constructor.
     * @param Input $input
     * @param Output $output
     */
    public function __construct(Input $input, Output $output)
    {
        $this->input  = $input;
        $this->output = $output;
    }

    /**
     * 运行控制器的 action
     * @param $action
     * @return mixed
     * @throws \HttpException
     */
    public function run($action = '')
    {
        // $this->input->getBool('h') || $this->input->getBool('help');

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
                $this->handleActionException($e);
            }

            // if you defined the method '$this->notFoundCallback' , will call it
        } elseif ( ( $notFoundCallback = $this->notFoundCallback) && method_exists($this, $notFoundCallback)) {
            $result = $this->{$notFoundCallback}($action);
        } else {
            // throw new \RuntimeException('Sorry, the page you want to visit already does not exist!');
            $this->output->error('Sorry, the page you want to visit already does not exist!');
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
    protected function handleActionException(\Exception $e)
    {
        throw $e;
    }

    protected function beforeRun($action)
    {}

    protected function afterRun($action)
    {}

    /**
     * @param string $msg
     * @return string
     */
    protected function read($msg = '')
    {
        return $this->input->read($msg);
    }

    /**
     * @param $message
     * @param bool $nl
     * @param bool $quit
     */
    protected function write($message, $nl = true, $quit = false)
    {
        $this->output->write($message, $nl, $quit);
    }

    /**
     * show help of the class or specified action
     * @usage ./bin/app {class}/help [action]
     * @example ./bin/app home/help OR ./bin/app home/help index
     */
    final public function helpCommand()
    {
        if (!$args = $this->input->get()) {
            $this->showCommandList();
            return 0;
        }

        $keys = array_keys($args);
        $action = trim(array_shift($keys), '- ');

        // convert 'first-second' to 'firstSecond'
        if ( strpos($action, '-') ) {
            $action = ucwords(str_replace('-', ' ', $action));
            $action = str_replace(' ','',lcfirst($action));
        }

        $method = $this->actionSuffix ? $action . ucfirst($this->actionSuffix) : $action;

        $ref = new \ReflectionClass($this);
        $sName = lcfirst($this->name?: $ref->getShortName());

        if ( !$ref->hasMethod($method) || !$ref->getMethod($method)->isPublic() ) {
            $this->write("Command [<info>$sName/$action</info>] don't exist or don't allow access in the class.");
            return 0;
        }

        $m = $ref->getMethod($method);
        $tags = $this->parseDocCommentTags($m->getDocComment());

        foreach ($tags as $tag => $msg) {
            if (!self::$allowTags || in_array($tag, self::$allowTags, true)) {
                $tag = ucfirst($tag);
                $this->write("<comment>$tag:</comment>\n   <info>$msg</info>\n");
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
        $sName = lcfirst($this->name?: $ref->getShortName());
        $this->write("This is in the console controller [<bold>$class</bold>]\n");

        if ( !($desc = static::DESCRIPTION) ) {
            $desc = $this->parseDocCommentDetail($ref->getDocComment()) ?: 'No Description';
        }

        $suffix = $this->actionSuffix;
        $suffixLen = Helper::strLen($suffix);
        $text = "<comment>Description:</comment>
  $desc
<comment>Usage</comment>:
  <info>$sName/[command] [options] [arguments]</info>
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
        $this->output->aList('<comment>Sub-Commands:</comment>', $commands);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
