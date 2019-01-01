<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/22 0022
 * Time: 21:44
 */

namespace Inhere\Console\Component\Formatter;

/**
 * Class Formatter - message formatter
 * @package Inhere\Console\Component\Formatter
 */
abstract class Formatter
{
    // content align
    public const ALIGN_LEFT   = 'left';
    public const ALIGN_CENTER = 'center';
    public const ALIGN_RIGHT  = 'right';

    /**
     * @var array
     */
    protected $config = [];

    public static function create(array $config = [])
    {
        return new static($config);
    }

    /**
     * Formatter constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $name => $value) {
            $setter = 'set' . \ucfirst($name);

            if (\method_exists($this, $setter)) {
                $this->$setter($value);
            } elseif (\property_exists($this, $name)) {
                $this->$name = $value;
            }
        }

        $this->config = $config;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    public function render()
    {

    }

    /**
     * @return string
     */
    abstract public function toString(): string;

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
