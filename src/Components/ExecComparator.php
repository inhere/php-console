<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-21
 * Time: 9:51
 */

namespace Inhere\Console\Components;

/**
 * Class ExecComparator - PHP code exec speed comparator
 * @package Inhere\Console\Components
 */
class ExecComparator
{
    /**
     * @var array
     */
    private $vars = [];

    public function compare($code1, $code2, $loops = 10000)
    {

    }

    /**
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @param array $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }
}
