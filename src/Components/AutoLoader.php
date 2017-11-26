<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/11/25 0025
 * Time: 11:31
 */

namespace Inhere\Console\Components;

/**
 * Class AutoLoader - for auto load commands and controllers
 * @package Inhere\Console\Components
 */
class AutoLoader
{
    /** @var string */
    protected $commandsNamespace;

    /** @var string */
    protected $controllersNamespace;

    public function scanCommands()
    {
        return [];
    }

    public function scanControllers()
    {
        return [];
    }

    /**
     * @return string
     */
    public function getCommandsNamespace()
    {
        return $this->commandsNamespace;
    }

    /**
     * @param string $commandsNamespace
     */
    public function setCommandsNamespace(string $commandsNamespace)
    {
        $this->commandsNamespace = $commandsNamespace;
    }

    /**
     * @return string
     */
    public function getControllersNamespace()
    {
        return $this->controllersNamespace;
    }

    /**
     * @param string $controllersNamespace
     */
    public function setControllersNamespace(string $controllersNamespace)
    {
        $this->controllersNamespace = $controllersNamespace;
    }
}
