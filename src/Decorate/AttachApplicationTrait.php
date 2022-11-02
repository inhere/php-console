<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Decorate;

use Inhere\Console\Application;
use Inhere\Console\Console;
use Toolkit\Stdlib\OS;

/**
 * Trait AttachApplicationTrait
 *
 * @package Inhere\Console\Decorate
 */
trait AttachApplicationTrait
{
    use SimpleEventAwareTrait {
        fire as parentFire;
    }

    /**
     * @var Application|null
     */
    protected ?Application $app = null;

    /**
     * Mark the command/controller is attached in console application.
     *
     * @var bool
     */
    private bool $attached = false;

    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }

    /**
     * @param Application|null $app
     */
    public function setApp(Application|null $app): void
    {
        if ($app !== null) {
            $this->app = $app;

            // auto setting $attached
            $this->attached = true;
        }
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

        // $value = $this->input->getOpt(GlobalOption::NO_INTERACTIVE);
        return $this->input->isInteractive();
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

        // return (int)$this->input->getLongOpt('debug', Console::VERB_ERROR);
        $envVal = OS::getEnvStrVal(Console::DEBUG_ENV_KEY);
        return $envVal !== '' ? (int)$envVal : Console::VERB_ERROR;
    }

    /**
     * @param int $level
     *
     * @return bool
     */
    public function isDebug(int $level = Console::VERB_DEBUG): bool
    {
        if ($this->app) {
            return $this->app->isDebug();
        }

        $setVal = Console::VERB_ERROR;
        $envVal = OS::getEnvStrVal(Console::DEBUG_ENV_KEY);
        if ($envVal !== '') {
            $setVal = (int)$envVal;
        }

        return $level <= $setVal;
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
}
