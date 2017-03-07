<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/7 0007
 * Time: 22:36
 */

namespace inhere\console\utils;

use inhere\console\ConsoleHelper;
use inhere\console\io\Output;

/**
 * Class ProgressBar
 * @package inhere\console\utils
 *
 *
 * ```
 *     1 [->--------------------------]
 *     3 [■■■>------------------------]
 * 25/50 [==============>-------------]  50%
 * ```
 *
 */
class ProgressBar
{
    // options
    private $barWidth = 30;
    private $completeChar;    // 完成的
    private $progressChar = '>';  // 当前进度
    private $remainingChar = '-'; // 剩下的
    private $startTime;
    private $percent;
    private $step = 0;
    private $max;
    private $stepWidth;

    private $format;

    /**
     * @param Output $output
     * @param array $config
     * @return ProgressBar
     */
    public static function create(Output $output, array $config = [])
    {
        return new self($output, $config);
    }

    public function __construct(Output $output, array $config = [])
    {
        $this->config = $config;
    }

    /**
     * 开始
     * @param null $max
     */
    public function start($max=null)
    {
        $this->startTime = time();
        $this->step = 0;
        $this->percent = 0.0;

        if (null !== $max) {
            $this->setMaxSteps($max);
        }

        $this->display();
    }

    /**
     * 前进
     * @param int $step
     */
    public function advance($step = 1)
    {

    }

    /**
     * 完成
     */
    public function finish()
    {

    }

    protected function display()
    {

    }

    /**
     * Sets the progress bar maximal steps.
     * @param int $max The progress bar max steps
     */
    private function setMaxSteps($max)
    {
        $this->max = max(0, (int) $max);
        $this->stepWidth = $this->max ? ConsoleHelper::strlen($this->max) : 4;
    }

    /**
     * Gets the complete bar character.
     * @return string A character
     */
    public function getCompleteChar()
    {
        if (null === $this->completeChar) {
            return $this->max ? '=' : $this->completeChar;
        }

        return $this->completeChar;
    }

}
