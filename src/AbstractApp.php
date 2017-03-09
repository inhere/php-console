<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-09
 * Time: 18:37
 */

namespace inhere\console;

/**
 * Class AbstractApp
 * @package inhere\console
 */
abstract class AbstractApp
{

    // event name list
    const EVT_APP_INIT = 'appInit';
    const EVT_BEFORE_RUN = 'beforeRun';
    const EVT_AFTER_RUN  = 'afterRun';
    const EVT_APP_STOP   = 'appStop';
    const EVT_NOT_FOUND  = 'notFound';

    /**
     * @var array
     */
    protected static $eventHandlers = [
        'appInit' => '',
        'beforeRun' => '',
        'afterRun' => '',
        'appStop' => '',
        'notFound' => '',
    ];

    protected function init()
    {
        // ...
    }

    /**********************************************************
     * app run
     **********************************************************/

    protected function prepareRun()
    {
        date_default_timezone_set($this->config('timeZone', 'UTC'));

        // ...
    }

    /**
     * run app
     * @param bool $exit
     */
    public function run($exit=true)
    {
        $this->prepareRun();

        // call 'onBeforeRun' service, if it is registered.
        if ( $cb = self::$eventHandlers[self::EVT_BEFORE_RUN] ) {
            $cb($this);
        }

        // do run ...
        $returnCode = $this->doRun();

        // call 'onAfterRun' service, if it is registered.
        if ( $cb = self::$eventHandlers[self::EVT_AFTER_RUN] ) {
            $cb($this);
        }

        if ($exit) {
            $this->stop((int)$returnCode);
        }
    }

    /**
     * do run
     */
    abstract public function doRun();

    /**
     * @param int $code
     */
    public function stop($code = 0)
    {
        // call 'onAppStop' service, if it is registered.
        if ( $cb = self::$eventHandlers[self::EVT_APP_STOP] ) {
            $cb($this);
        }

        exit((int)$code);
    }


    /**
     * @return array
     */
    public static function events()
    {
        return [self::EVT_APP_INIT, self::EVT_BEFORE_RUN, self::EVT_AFTER_RUN, self::EVT_APP_STOP, self::EVT_NOT_FOUND];
    }

    /**
     * @param $event
     * @param callable $handler
     */
    public function on($event, callable $handler)
    {
        if (isset(self::events()[$event])) {
            self::$eventHandlers[$event] = $handler;
        }
    }

}