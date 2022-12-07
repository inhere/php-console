<?php declare(strict_types=1);

namespace Inhere\Console\Component\Interact;

use Toolkit\PFlag\FlagsParser;
use Toolkit\Stdlib\Helper\Assert;

/**
 * class ValuesCollector
 * - collect value by start i-shell env.
 */
class ValuesCollector
{
    /**
     * @var array = [
     *     [
     *      'name' => 'title', // param name
     *      'type' => 'StringParam',  // String Parameter Definition
     *      'desc' => 'description',
     *      'default' => null, // default value
     *     ],
     *     [
     *      'name' => 'projects',
     *      'type' => 'ChoiceParam', // Choice Parameter Definition
     *      'desc' => 'description',
     *      'default' => null, // default value
     *      ],
     * ]
     */
    public array $propDefinitions;

    /**
     * @param array $propDefinitions
     *
     * @return $this
     */
    public function new(array $propDefinitions = []): self
    {
        return new self($propDefinitions);
    }

    /**
     * Class constructor.
     *
     * @param array $propDefinitions
     */
    public function __construct(array $propDefinitions = [])
    {
        $this->propDefinitions = $propDefinitions;
    }

    /**
     * @param FlagsParser $fs
     */
    public function collect(FlagsParser $fs): void
    {
        // for ($fs->getOptDefine($name))
        Assert::notEmpty($this->propDefinitions);
        foreach ($this->propDefinitions as $definition) {

        }
    }

    /**
     * @param array $propDefinitions
     *
     * @return ValuesCollector
     */
    public function setPropDefinitions(array $propDefinitions): self
    {
        $this->propDefinitions = $propDefinitions;
        return $this;
    }
}
