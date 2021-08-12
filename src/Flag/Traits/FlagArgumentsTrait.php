<?php declare(strict_types=1);

namespace Inhere\Console\Flag\Traits;

use Inhere\Console\Flag\Argument;
use function count;

/**
 * Class CmdArgumentsTrait
 * - input arguments builder trait
 *
 * @package Inhere\Console\Flag\Traits
 */
trait FlagArgumentsTrait
{
    /**
     * @var array [name => index]
     */
    private $name2index = [];

    /**
     * @var Argument[]
     */
    private $arguments = [];

    /**
     * @param Argument $argument
     */
    public function addArgument(Argument $argument): void
    {
        // record index
        $this->name2index[$argument->getName()] = count($this->arguments);
        // append
        $this->arguments[] = $argument;
    }

    /**
     * @param string      $name
     * @param string      $desc
     * @param int|null    $mode
     * @param string|null $type The argument data type. (eg: 'string', 'array', 'mixed')
     * @param null|mixed        $default
     * @param string      $alias
     */
    public function add(
        string $name,
        string $desc = '',
        int $mode = 0,
        string $type = '',
        $default = null,
        string $alias = ''
    ): void {
        $argObj = Argument::new($name, $desc, $mode, $default);
        $argObj->setType($type);
        $argObj->setAlias($alias);

        $this->addArgument($argObj);
    }

    /**
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @param array $arguments
     */
    public function setArguments(array $arguments): void
    {
        $this->arguments = $arguments;
    }

    protected function resetArguments(): void
    {
        $this->name2index = [];
        $this->arguments = [];
    }
}
