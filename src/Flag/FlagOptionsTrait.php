<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-17
 * Time: 10:10
 */

namespace Inhere\Console\Flag;

/**
 * Class InputOptions
 * - input options builder
 *
 * @package Inhere\Console\IO\Input
 */
trait FlagOptionsTrait
{
    /**
     * The defined options on init.
     *
     * @var Option[]
     */
    private $defined = [];

    /**
     * The matched options on runtime
     *
     * @var Option[]
     */
    private $matched = [];

    /**
     * @param string $name
     * @param string $shorts
     * @param string $desc
     * @param int    $mode
     * @param mixed  $default
     */
    public function addOpt(string $name, string $shorts, string $desc, int $mode = 0, $default = null): void
    {
        $opt = Option::new($name, $desc, $mode, $default);
        $opt->setShortcut($shorts);

        $this->addOption($opt);
    }

    /**
     * @param Option $option
     */
    public function addOption(Option $option): void
    {
        $name = $option->getName();

        $this->defined[$name] = $option;
    }

    /**
     * @param Option[] $options
     */
    public function addOptions(array $options): void
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
    }

    /**
     * @return Option[]
     */
    public function getDefinedOptions(): array
    {
        return $this->defined;
    }

    /**
     * @return Option[]
     */
    public function getMatchedOptions(): array
    {
        return $this->matched;
    }
}
