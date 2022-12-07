<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact\ParamDefinition;

use Toolkit\Stdlib\Obj\AbstractObj;

/**
 * class AbstractParam
 *
 * @author inhere
 * @date 2022/11/21
 */
abstract class AbstractParam extends AbstractObj
{
    /**
     * @var string
     */
    public string $type = '';

    /**
     * @var string
     */
    public string $name = '';

    /**
     * @var string
     */
    public string $desc = '';

    /**
     * @var mixed
     */
    public mixed $default = null;

    /**
     * @var null|callable
     */
    protected $validator;

    /**
     * @param callable $validator
     *
     * @return static
     */
    public function setValidator(callable $validator): static
    {
        $this->validator = $validator;
        return $this;
    }
}
