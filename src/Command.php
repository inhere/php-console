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
    // command usage message
    protected $usage = '';

    // command arguments message
    protected $arguments = [];

    // command arguments message
    protected $options = [];

    // command example message
    protected $example = '';

    /**
     * run command
     * @param  string $name
     * @return int
     */
    public function run($name = '')
    {
        $this->setName($name);

        if ($this->input->boolOpt('h') || $this->input->boolOpt('help')) {
            return $this->showHelp();
        }

        $status = 0;

        try {
            $this->beforeRun();
            $status = $this->execute($this->input, $this->output);
            $this->afterRun();

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
     * handle command runtime exception
     *
     * @param  \Exception $e
     * @throws \Exception
     */
    protected function handleRuntimeException(\Exception $e)
    {
        throw $e;
    }

    protected function beforeRun()
    {
    }

    protected function afterRun()
    {
    }

    protected function configure()
    {
        return [
            // 'usage' => '',

            // 'arguments' => [],
            // 'options' => [],
            // 'examples' => [],
        ];
    }

    public function showHelp()
    {
        $configure = $this->configure();

        if (!$configure) {
            return 91;
        }

        $configure['description'] = static::DESCRIPTION;

        $this->output->helpPanel($configure, false);

        return 0;
    }
}
