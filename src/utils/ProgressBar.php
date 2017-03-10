<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/7 0007
 * Time: 22:36
 */

namespace inhere\console\utils;

use inhere\console\Helper;
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

    private $format ='{bar} {percent}({complete}/{total})';

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
        return preg_replace_callback('/({[\w_]+})/', function($matched) {

        }, $this->format);
    }

    /**
     * Sets the progress bar maximal steps.
     * @param int $max The progress bar max steps
     */
    private function setMaxSteps($max)
    {
        $this->max = max(0, (int) $max);
        $this->stepWidth = $this->max ? Helper::strlen($this->max) : 4;
    }

    /**
     * @return int
     */
    public function getBarWidth()
    {
        return $this->barWidth;
    }

    /**
     * @param int $barWidth
     */
    public function setBarWidth($barWidth)
    {
        $this->barWidth = $barWidth;
    }

    /**
     * @param mixed $completeChar
     */
    public function setCompleteChar($completeChar)
    {
        $this->completeChar = $completeChar;
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

    /**
     * @return string
     */
    public function getProgressChar()
    {
        return $this->progressChar;
    }

    /**
     * @param string $progressChar
     */
    public function setProgressChar($progressChar)
    {
        $this->progressChar = $progressChar;
    }

    /**
     * @return string
     */
    public function getRemainingChar()
    {
        return $this->remainingChar;
    }

    /**
     * @param string $remainingChar
     */
    public function setRemainingChar($remainingChar)
    {
        $this->remainingChar = $remainingChar;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

}
