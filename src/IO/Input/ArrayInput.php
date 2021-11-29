<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;
use function is_int;

/**
 * Class ArrayInput
 *
 * @package Inhere\Console\IO\Input
 */
class ArrayInput extends Input
{
    /**
     * Input constructor.
     *
     * @param array $arr
     */
    public function __construct(array $arr = [])
    {
        $args = [];
        foreach ($arr as $key => $val) {
            if (!is_int($key)) {
                $args[] = $key;
            }

            $args[] = $val;
        }

        parent::__construct($args);
    }
}
