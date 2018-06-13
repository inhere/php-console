<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/12/24 0024
 * Time: 18:39
 */

namespace Inhere\Console\IO;

/**
 * Class FixedInput
 * - 初始化时不全部解析，只取出 '-h' '--help' 还有命令名
 * - 到运行命令时根据命令的参数选项配置(InputDefinition)来进行解析
 * - @todo un-completed
 * @package Inhere\Console\IO
 */
class FixedInput extends Input
{
    /**
     * the prepare parsed options.
     * @see AbstractApplication::$internalOptions
     * @var array
     */
    private $preParsed =[
        // opt name => has value
        'h' => false,
        'V' => false,
        'help' => false,
        'debug' => true,
        'profile' => false,
        'version' => false,
    ];

    /** @var array */
    private $cleanedTokens;

    /**
     * FixedInput constructor.
     * @param null|array $argv
     */
    public function __construct($argv = null)
    {
        if (null === $argv) {
            $argv = $_SERVER['argv'];
        }

        parent::__construct($argv, false);

        $copy = $argv;

        // command name
        if (!empty($copy[1]) && $copy[1][0] !== '-' && false === \strpos($copy[1], '=')) {
            $this->setCommand($copy[1]);

            // unset command
            unset($copy[1]);
        }

        // pop script name
        \array_shift($copy);

        $this->cleanedTokens = $copy;
        $this->collectPreParsed($copy);
    }

    private function collectPreParsed(array $tokens)
    {
        // foreach ($this->preParsed as $name => $hasVal) {
        //
        // }
    }

    /**
     * @param array $allowArray
     * @param array $noValues
     */
    public function parseTokens(array $allowArray = [], array $noValues = [])
    {
        $params = $this->getTokens();
        \array_shift($params); // pop script name
    }

    /**
     * @return array
     */
    public function getPreParsed(): array
    {
        return $this->preParsed;
    }

    /**
     * @param array $preParsed
     */
    public function setPreParsed(array $preParsed)
    {
        $this->preParsed = $preParsed;
    }

    /**
     * @return array|null
     */
    public function getCleanedTokens(): array
    {
        return $this->cleanedTokens;
    }

}
