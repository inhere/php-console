<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

namespace Inhere\Console\Concern;

use Toolkit\Stdlib\Obj;

/**
 * Class InteractMessage
 *
 * @package Inhere\Console\Concern
 */
abstract class InteractiveHandle
{
    /**
     * You can validate and filter input
     *
     * ```php
     * $run->setValidator(function (string $line) {
     *      // check input
     *      if (!$line) {
     *          throw new InvalidArgumentException('argument is required');
     *      }
     *      return $line;
     * });
     * ```
     *
     * @var callable
     */
    protected $validator;

    /**
     * @var callable
     */
    protected $ansFilter;

    /**
     * Class constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        Obj::init($this, $options);
    }

    /**
     * @param callable $validator
     *
     * @return self
     */
    public function setValidator(callable $validator): self
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * @return callable
     */
    public function getAnsFilter(): callable
    {
        return $this->ansFilter;
    }

    /**
     * @param callable $ansFilter
     */
    public function setAnsFilter(callable $ansFilter): void
    {
        $this->ansFilter = $ansFilter;
    }
}
