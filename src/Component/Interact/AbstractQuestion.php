<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Component\Interact;

use Inhere\Console\Component\InteractiveHandle;
use Toolkit\Stdlib\Str\StrObject;

/**
 * Class AbstractQuestion
 *
 * @package Inhere\Console\Component\Interact
 */
abstract class AbstractQuestion extends InteractiveHandle
{
    /**
     * @var StrObject
     */
    protected $answer;

    /**
     * @param string $str
     */
    protected function createAnswer(string $str): void
    {
        $this->answer = StrObject::new($str)->trim();
    }

    /**
     * @return StrObject
     */
    public function getAnswer(): StrObject
    {
        return $this->answer;
    }

    /**
     * @return int
     */
    public function getInt(): int
    {
        return $this->answer->toInt();
    }
}
