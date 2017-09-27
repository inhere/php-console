<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:38
 */

namespace Inhere\Console\Traits;

use Inhere\Console\Utils\Interact;

/**
 * Class UserInteractTrait
 * @package Inhere\Console\Traits
 */
trait UserInteractTrait
{
    /**
     * @inheritdoc
     * @see Interact::choice()
     */
    public function select($description, $options, $default = null, $allowExit = true)
    {
        return $this->choice($description, $options, $default, $allowExit);
    }

    /**
     * @inheritdoc
     * @see Interact::choice()
     */
    public function choice($description, $options, $default = null, $allowExit = true)
    {
        return Interact::choice($description, $options, $default, $allowExit);
    }

    /**
     * @inheritdoc
     * @see Interact::confirm()
     */
    public function confirm($question, $default = true)
    {
        return Interact::confirm($question, $default);
    }

    /**
     * @inheritdoc
     * @see Interact::question()
     */
    public function ask($question, $default = null, \Closure $validator = null)
    {
        return $this->question($question, $default, $validator);
    }

    public function question($question, $default = null, \Closure $validator = null)
    {
        return Interact::question($question, $default, $validator);
    }

    /**
     * @inheritdoc
     * @see Interact::limitedAsk()
     */
    public function loopAsk($question, $default = null, \Closure $validator = null, $times = 3)
    {
        return Interact::limitedAsk($question, $default, $validator, $times);
    }

    /**
     * @inheritdoc
     * @see Interact::limitedAsk()
     */
    public function limitedAsk($question, $default = null, \Closure $validator = null, $times = 3)
    {
        return Interact::limitedAsk($question, $default, $validator, $times);
    }

}
