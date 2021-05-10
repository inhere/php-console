<?php declare(strict_types=1);

namespace Inhere\Console\Concern;

use Inhere\Console\AbstractHandler;
use function strpos;
use function strtr;

/**
 * Trait CommandHelpTrait
 *
 * @package Inhere\Console\Concern
 */
trait CommandHelpTrait
{
    /**
     * @var array [name => value]
     * @see AbstractHandler::annotationVars()
     */
    private $commentsVars;

    /**
     * @return array
     */
    public function getCommentsVars(): array
    {
        return $this->commentsVars;
    }

    /**
     * @param array $commentsVars
     */
    public function setCommentsVars(array $commentsVars): void
    {
        $this->commentsVars = $commentsVars;
    }

    /**
     * @param string       $name
     * @param string|array $value
     */
    protected function addCommentsVar(string $name, $value): void
    {
        if (!isset($this->commentsVars[$name])) {
            $this->setCommentsVar($name, $value);
        }
    }

    /**
     * @param array $map
     */
    protected function addCommentsVars(array $map): void
    {
        foreach ($map as $name => $value) {
            $this->setCommentsVar($name, $value);
        }
    }

    /**
     * @param string       $name
     * @param string|array $value
     */
    protected function setCommentsVar(string $name, $value): void
    {
        $this->commentsVars[$name] = is_array($value) ? implode(',', $value) : (string)$value;
    }

    /**
     * 替换注解中的变量为对应的值
     *
     * @param string $str
     *
     * @return string
     */
    protected function parseCommentsVars(string $str): string
    {
        // not use vars
        if (false === strpos($str, self::HELP_VAR_LEFT)) {
            return $str;
        }

        static $map;

        if ($map === null) {
            foreach ($this->commentsVars as $key => $value) {
                $key = self::HELP_VAR_LEFT . $key . self::HELP_VAR_RIGHT;
                // save
                $map[$key] = $value;
            }
        }

        return $map ? strtr($str, $map) : $str;
    }
}
