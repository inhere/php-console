<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Concern;

use Toolkit\Stdlib\Str\StrValue;

/**
 * Class AbstractQuestion
 *
 * @package Inhere\Console\Component\Interact
 */
abstract class AbstractQuestion extends InteractiveHandle
{
    /**
     * @var StrValue|null
     */
    protected ?StrValue $answer = null;

    /**
     * @param string $str
     */
    protected function createAnswer(string $str): void
    {
        $this->answer = StrValue::new($str)->trim();
    }

    /**
     * @return StrValue
     */
    public function getAnswer(): StrValue
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
