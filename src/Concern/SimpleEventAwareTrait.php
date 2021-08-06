<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-27
 * Time: 16:17
 */

namespace Inhere\Console\Concern;

use function count;
use function in_array;

/**
 * Class SimpleEventStaticTrait
 *
 * @package Inhere\Console\Concern
 */
trait SimpleEventAwareTrait
{
    /**
     * set the supported events, if you need.
     *  if it is empty, will allow register any event.
     *
     * @var array
     */
    protected static $supportedEvents = [];

    /**
     * registered Events
     *
     * @var array
     * [
     *  'event' => bool, // is once event
     * ]
     */
    private static $events = [];

    /**
     * events and handlers
     *
     * @var array
     * [
     *  'event' => callable, // event handler
     * ]
     */
    private static $eventHandlers = [];

    /**
     * register a event handler
     *
     * @param string   $event
     * @param callable $handler
     * @param bool     $once
     */
    public function on(string $event, callable $handler, bool $once = false): void
    {
        if (self::isSupportedEvent($event)) {
            self::$eventHandlers[$event][] = $handler;

            if (!isset(self::$events[$event])) {
                self::$events[$event] = $once;
            }
        }
    }

    /**
     * register a once event handler
     *
     * @param string   $event
     * @param callable $handler
     */
    public function once(string $event, callable $handler): void
    {
        $this->on($event, $handler, true);
    }

    /**
     * trigger event
     *
     * @param string $event
     * @param mixed  ...$args
     *
     * @return bool
     */
    public function fire(string $event, ...$args): bool
    {
        if (!isset(self::$events[$event])) {
            return false;
        }

        // call event handlers of the event.
        /** @var mixed $return */
        $return = true;
        foreach ((array)self::$eventHandlers[$event] as $cb) {
            $return = $cb(...$args);
            // return FALSE to stop go on handle.
            if (false === $return) {
                break;
            }
        }

        // is a once event, remove it
        if (self::$events[$event]) {
            return $this->off($event);
        }

        return (bool)$return;
    }

    /**
     * remove event and it's handlers
     *
     * @param string $event
     *
     * @return bool
     */
    public function off(string $event): bool
    {
        if ($this->hasEvent($event)) {
            unset(self::$events[$event], self::$eventHandlers[$event]);

            return true;
        }

        return false;
    }

    /**
     * @param string $event
     *
     * @return bool
     */
    public function hasEvent(string $event): bool
    {
        return isset(self::$events[$event]);
    }

    /**
     * @param string $event
     *
     * @return bool
     */
    public function isOnce(string $event): bool
    {
        if ($this->hasEvent($event)) {
            return self::$events[$event];
        }

        return false;
    }

    /**
     * check $name is a supported event name
     *
     * @param string $event
     *
     * @return bool
     */
    public static function isSupportedEvent(string $event): bool
    {
        if (!$event || !preg_match('/[a-zA-z][\w-]+/', $event)) {
            return false;
        }

        if ($ets = self::$supportedEvents) {
            return in_array($event, $ets, true);
        }

        return true;
    }

    /**
     * @return array
     */
    public static function getSupportEvents(): array
    {
        return self::$supportedEvents;
    }

    /**
     * @param array $supportedEvents
     */
    public static function setSupportEvents(array $supportedEvents): void
    {
        self::$supportedEvents = $supportedEvents;
    }

    /**
     * @return array
     */
    public static function getEvents(): array
    {
        return self::$events;
    }

    /**
     * @return int
     */
    public static function countEvents(): int
    {
        return count(self::$events);
    }
}
