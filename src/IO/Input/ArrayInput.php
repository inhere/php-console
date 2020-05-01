<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2018/1/30 0030
 * Time: 23:41
 */

namespace Inhere\Console\IO\Input;

use Inhere\Console\IO\Input;
use Toolkit\Cli\Flags;
use function array_shift;
use function implode;

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
     * @param null|array $args
     * @param bool       $parsing
     */
    public function __construct(array $args = null, bool $parsing = true)
    {
        parent::__construct([], false);

        $this->tokens     = $args;
        $this->script     = array_shift($args);
        $this->fullScript = implode(' ', $args);

        if ($parsing && $args) {
            [$this->args, $this->sOpts, $this->lOpts] = Flags::parseArray($args);

            // find command name
            $this->findCommand();
        }
    }
}
