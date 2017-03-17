<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-03-17
 * Time: 11:40
 */

namespace inhere\console;

use inhere\console\utils\TraitInputOutput;

/**
 * Class AbstractCommand
 * @package inhere\console
 */
abstract class AbstractCommand
{
    use TraitInputOutput;

    // command description message
    // please use the const setting current controller/command description
    const DESCRIPTION = '';

    /**
     * command name e.g 'test' 'test:one'
     * @var string
     */
    private $name = '';

    /**
     * allow display message tags in the command
     * @var array
     */
    protected static $allowTags = ['description', 'usage', 'example'];

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public static function getAllowTags()
    {
        return self::$allowTags;
    }

    /**
     * @param array $allowTags
     */
    public static function setAllowTags($allowTags)
    {
        self::$allowTags = $allowTags;
    }
}