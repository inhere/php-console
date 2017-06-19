<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2016/12/7
 * Time: 13:23
 */

namespace inhere\console;

use inhere\console\io\Input;
use inhere\console\io\Output;

/**
 * Class Command
 * @package inhere\console
 */
abstract class Command extends AbstractCommand
{
    /**
     * command usage message
     * @var string
     */
    protected $usage = '';

    /**
     * command arguments message
     * @var array
     */
    protected $arguments = [];

    /**
     * command arguments message
     * @var array
     */
    protected $options = [];

    /**
     * command example message
     * @var string
     */
    protected $example = '';

    /**
     * run command
     * @param  string $name
     * @return int
     */
    public function run($name = '')
    {
        $this->setName($name);

        if ($this->input->sameOpt(['h','help'])) {
            return $this->showHelp();
        }

        $status = 0;

        try {
            $this->beforeRun($name);
            $status = $this->execute($this->input, $this->output);
            $this->afterRun($name);

        } catch (\Exception $e) {
            $this->handleRuntimeException($e);
        }

        return $status;
    }

    /**
     * do execute
     * @param  Input $input
     * @param  Output $output
     * @return int
     */
    abstract protected function execute($input, $output);

    /**
     * @return array
     */
    protected function configure()
    {
        return [
            // 'usage' => '',

            // 'arguments' => [],
            // 'options' => [],
            // 'examples' => [],
        ];
    }

    /**
     * @return int
     */
    public function showHelp()
    {
        $configure = $this->configure();

        if (!$configure) {
            return __LINE__;
        }

        $configure['description'] = static::DESCRIPTION;

        $this->output->helpPanel($configure, false);

        return 0;
    }
}
