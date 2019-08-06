<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:38
 */

namespace Inhere\Console\Traits;

use Closure;
use Inhere\Console\Util\Interact;
use LogicException;
use function method_exists;

/**
 * Class UserInteractAwareTrait
 *
 * @package Inhere\Console\Traits
 * @see     Interact
 *
 * @method string readRow($message = null, $nl = false)
 * @method string readln($message = null, $nl = false, array $opts = [])
 *
 * @method array checkbox(string $description, $options, $default = null, $allowExit = true)
 * @method array multiSelect(string $description, $options, $default = null, $allowExit = true)
 *
 * @method string askHiddenInput(string $prompt = 'Enter Password:')
 * @method string promptSilent(string $prompt = 'Enter Password:')
 * @method string askPassword(string $prompt = 'Enter Password:')
 */
trait UserInteractAwareTrait
{
    /**
     * @inheritdoc
     * @see Interact::choice()
     */
    public function select(string $description, $options, $default = null, $allowExit = true): string
    {
        return $this->choice($description, $options, $default, $allowExit);
    }

    /**
     * @inheritdoc
     * @see Interact::choice()
     */
    public function choice(string $description, $options, $default = null, $allowExit = true): string
    {
        return Interact::choice($description, $options, $default, $allowExit);
    }

    /**
     * @inheritdoc
     * @see Interact::confirm()
     */
    public function confirm(string $question, bool $default = true): bool
    {
        return Interact::confirm($question, $default);
    }

    /**
     * @inheritdoc
     * @see Interact::question()
     */
    public function ask(string $question, string $default = '', Closure $validator = null): ?string
    {
        return $this->question($question, $default, $validator);
    }

    public function question(string $question, string $default = '', Closure $validator = null): ?string
    {
        return Interact::question($question, $default, $validator);
    }

    /**
     * @inheritdoc
     * @see Interact::limitedAsk()
     */
    public function limitedAsk(string $question, string $default = '', Closure $validator = null, $times = 3): ?string
    {
        return Interact::limitedAsk($question, $default, $validator, $times);
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @return int
     * @throws LogicException
     */
    public function __call($method, array $args = [])
    {
        if (method_exists(Interact::class, $method)) {
            return Interact::$method(...$args);
        }

        throw new LogicException("Call a not exists method: $method of the " . static::class);
    }
}
