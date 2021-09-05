<?php declare(strict_types=1);

namespace Inhere\Console\Component;

use Toolkit\Stdlib\Obj;

/**
 * Class InteractMessage
 *
 * @package Inhere\Console\Component
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
}
