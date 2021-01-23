<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Inhere\Console\AbstractApplication;
use Inhere\Console\Application;
use Inhere\Console\Console;
use Inhere\Console\GlobalOption;

/**
 * Trait AttachApplicationTrait
 *
 * @package Inhere\Console\Concern
 */
trait AttachApplicationTrait
{
    use SimpleEventAwareTrait {
        fire as parentFire;
    }

    /**
     * @var Application
     */
    protected $app;

    /**
     * Mark the command/controller is attached in console application.
     *
     * @var bool
     */
    private $attached = false;

    /**
     * @return AbstractApplication
     */
    public function getApp(): AbstractApplication
    {
        return $this->app;
    }

    /**
     * @param AbstractApplication $app
     */
    public function setApp(AbstractApplication $app): void
    {
        $this->app = $app;

        // auto setting $attached
        $this->attached = true;
    }

    /**
     * @return bool
     */
    public function isAttached(): bool
    {
        return $this->attached;
    }

    /**
     * @return bool
     */
    public function isDetached(): bool
    {
        return $this->attached === false;
    }

    /**
     * Detached running
     */
    public function setDetached(): void
    {
        $this->attached = false;
    }

    /**
     * @return bool
     */
    public function isInteractive(): bool
    {
        if ($this->app) {
            return $this->app->isInteractive();
        }

        $value = $this->input->getBoolOpt(GlobalOption::NO_INTERACTIVE);

        return $value === false;
    }

    /**
     * Get current debug level value
     *
     * @return int
     */
    public function getVerbLevel(): int
    {
        if ($this->app) {
            return $this->app->getVerbLevel();
        }

        return (int)$this->input->getLongOpt('debug', Console::VERB_ERROR);
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
     * @param int    $level
     * @param string $message
     * @param array  $extra
     */
    public function log(int $level, string $message, array $extra = []): void
    {
        if ($this->getVerbLevel() < $level) {
            return;
        }

        Console::log($level, $message, $extra);
    }

    /**************************************************************************
     * wrap trigger events
     **************************************************************************/

    /**
     * @param string $event
     * @param mixed  ...$args
     *
     * @return bool
     */
    public function fire(string $event, ...$args): bool
    {
        // if has application instance
        if ($this->attached) {
            $stop = $this->app->fire($event, ...$args);
            if ($stop === false) {
                return false;
            }
        }

        return $this->parentFire($event, ...$args);
    }
}
