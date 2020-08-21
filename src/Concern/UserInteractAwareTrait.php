<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:38
 */

namespace Inhere\Console\Concern;

use Closure;
use Inhere\Console\Util\Interact;
use LogicException;
use function method_exists;

/**
 * Class UserInteractAwareTrait
 *
 * @package Inhere\Console\Concern
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
     * @param string          $description
     * @param string|array    $options Option data
     * @param string|int|null $default Default option
     * @param bool            $allowExit
     *
     * @return string
     * @see Interact::choice()
     */
    public function select(string $description, $options, $default = null, $allowExit = true): string
    {
        return $this->choice($description, $options, $default, $allowExit);
    }

    /**
     * @param string          $description
     * @param string|array    $options Option data
     * @param string|int|null $default Default option
     * @param bool            $allowExit
     *
     * @return string
     */
    public function choice(string $description, $options, $default = null, $allowExit = true): string
    {
        return Interact::choice($description, $options, $default, $allowExit);
    }

    /**
     * @param string $question
     * @param bool   $default
     *
     * @return bool
     */
    public function confirm(string $question, bool $default = true): bool
    {
        return Interact::confirm($question, $default);
    }

    /**
     * @param string $question
     * @param bool   $default
     *
     * @return bool
     */
    public function unConfirm(string $question, bool $default = true): bool
    {
        return Interact::unConfirm($question, $default);
    }

    /**
     * @param string       $question
     * @param string       $default
     * @param Closure|null $validator
     *
     * @return string|null
     */
    public function ask(string $question, string $default = '', Closure $validator = null): ?string
    {
        return $this->question($question, $default, $validator);
    }

    /**
     * @param string       $question
     * @param string       $default
     * @param Closure|null $validator
     *
     * @return string|null
     */
    public function question(string $question, string $default = '', Closure $validator = null): ?string
    {
        return Interact::question($question, $default, $validator);
    }

    /**
     * @param string       $question
     * @param string       $default
     * @param Closure|null $validator
     * @param int          $times
     *
     * @return string|null
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
