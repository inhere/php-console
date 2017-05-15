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
use inhere\console\io\OutputInterface;

/**
 * Class ProgressBar
 * @package inhere\console\utils
 * @form Symfony\Component\Console\Helper\ProgressBar
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

    private $completeChar = '=';  // 已完成的显示字符
    private $progressChar = '>';  // 当前进度的显示字符
    private $remainingChar = '-'; // 剩下的的显示字符
    private $redrawFreq = 1;
    private $format;

    /**
     * 已完成百分比
     * @var float
     */
    private $percent = 0.0;

    /**
     * maximal steps.
     * 当前在多少步
     * @var int
     */
    private $step = 0;

    /**
     * maximal steps.
     * 设置多少步就会走完进度条,
     * @var int
     */
    private $maxSteps;

    /**
     * step Width
     * 设置步长
     * @var int
     */
    private $stepWidth;

    private $startTime;
    private $finishTime;

    private $overwrite = true;
    private $started = false;
    private $firstRun = true;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * messages
     * @var array
     */
    private $messages = [];

    /**
     * section formatters
     * @var \Closure[]
     */
    private static $formatters = [
        // 'precent' => function () { ... },
    ];

    const DEFAULT_FORMAT = '[{bar}] {percent}({current}/{max}) {memory}';

    /**
     * @param OutputInterface $output
     * @param array $config
     * @return ProgressBar
     */
    public static function create(OutputInterface $output = null, int $maxSteps = 0)
    {
        return new self($output, $config);
    }

    /**
     * @param OutputInterface $output
     * @param array $config
     * @return ProgressBar
     */
    public function __construct(OutputInterface $output = null, int $maxSteps = 0)
    {
        $this->output = $output ?: new Output;

        $this->setMaxSteps($maxSteps);

        // Helper::loadAttrs($this, $config);
    }

    /**
     * 开始
     * @param null $maxSteps
     */
    public function start($maxSteps = null)
    {
        if ($this->started) {
            throw new LogicException('Progress bar already started.');
        }

        $this->startTime = time();
        $this->step = 0;
        $this->percent = 0.0;
        $this->started = true;

        if (null !== $maxSteps) {
            $this->setMaxSteps($maxSteps);
        }

        $this->display();
    }

    /**
     * 前进，按步长前进几步
     * @param int $step 前进几步
     */
    public function advance(int $step = 1)
    {
        if (!$this->started) {
            throw new LogicException('Progress indicator has not yet been started.');
        }

        $this->advanceTo($this->step + $step);
    }

    /**
     * 直接前进到第几步
     * @param int $step 第几步
     */
    public function advanceTo(int $step)
    {
        if ($this->maxSteps && $step > $this->maxSteps) {
            $this->maxSteps = $step;
        } elseif ($step < 0) {
            $step = 0;
        }

        $prevPeriod = (int) ($this->step / $this->redrawFreq);
        $currPeriod = (int) ($step / $this->redrawFreq);
        $this->step = $step;
        $this->percent = $this->maxSteps ? (float) $this->step / $this->maxSteps : 0;

        if ($prevPeriod !== $currPeriod || $this->maxSteps === $step) {
            $this->display();
        }
    }

    /**
     * Finishes the progress output.
     */
    public function finish()
    {
        if (!$this->started) {
            throw new \LogicException('Progress bar has not yet been started.');
        }

        if (!$this->maxSteps) {
            $this->maxSteps = $this->step;
        }

        if ($this->step === $this->maxSteps && !$this->overwrite) {
            // prevent double 100% output
            return;
        }

        $this->finishTime = time();
        $this->advanceTo($this->maxSteps);
    }

    /**
     * Outputs the current progress string.
     */
    public function display()
    {
        if (null === $this->format) {
            $this->format = self::DEFAULT_FORMAT;
        }

        $this->render($this->buildLine());
    }

    /**
     * Removes the progress bar from the current line.
     *
     * This is useful if you wish to write some output
     * while a progress bar is running.
     * Call display() to show the progress bar again.
     */
    public function clear()
    {
        if (!$this->overwrite) {
            return;
        }

        $this->render('');
    }

    /**
     * render
     * @param  string $text
     */
    public function render(string $text)
    {
        if ($this->overwrite) {
            if (!$this->firstRun) {

                // \x0D - Move the cursor to the beginning of the line
                // \x1B[2K - Erase the line
                $this->output->write("\x0D\x1B[2K", false);
                $this->output->write($text, false);
            }
        } elseif ($this->step > 0) {
            $this->output->write('');
        }

        $this->firstRun = false;
        $this->output->write($text, false);
    }

    protected function buildLine()
    {
        return preg_replace_callback('/({[\w_]+})/i', function ($matches) {
            if ($formatter = $this->getFormatter($matches[1])) {
                $text = call_user_func($formatter, $this, $this->output);
            } elseif (isset($this->messages[$matches[1]])) {
                $text = $this->messages[$matches[1]];
            } else {
                return $matches[0];
            }

            if (isset($matches[2])) {
                $text = sprintf('%'.$matches[2], $text);
            }

            return $text;
        }, $this->format);
    }

    /**
     * set section Formatter
     * @param string   $section
     * @param callable $handler
     */
    public function setFormatter(string $section, callable $handler)
    {
        self::$formatters[$section] = $handler;
    }

    /**
     * get section Formatter
     * @param  string       $section
     * @param  bool|boolean $throwException
     * @return mixed
     */
    public function getFormatter(string $section, bool $throwException = false)
    {
        if (!self::$formatters) {
            self::$formatters = self::loadDefaultFormatters();
        }

        if (isset(self::$formatters[$section])) {
            return self::$formatters[$section];
        }

        if ($throwException) {
            throw new \RuntimeException("The section($section) formatter is not registered!", -500);
        }

        return null;
    }

    /**
     * set a named Message
     * @param string $message The text to associate with the placeholder
     * @param string $name    The name of the placeholder
     */
    public function setMessage($message, string $name = 'message')
    {
        $this->messages[$name] = $message;
    }

    public function getMessage(string $name = 'message')
    {
        return $this->messages[$name];
    }

    /**
     * Gets the current step position.
     *
     * @return int The progress bar step
     */
    public function getProgress()
    {
        return $this->step;
    }
    public function getStep()
    {
        return $this->step;
    }

    /**
     * Sets the redraw frequency.
     *
     * @param int|float $freq The frequency in steps
     */
    public function setRedrawFreq($freq)
    {
        $this->redrawFreq = max((int) $freq, 1);
    }

    public function setOverwrite(bool $overwrite)
    {
        $this->overwrite = $overwrite;
    }

    /**
     * Sets the progress bar maximal steps.
     * @param int $maxSteps The progress bar max steps
     */
    private function setMaxSteps(int $maxSteps)
    {
        $this->maxSteps = max(0, $maxSteps);
        $this->stepWidth = $this->maxSteps ? Helper::strLen($this->maxSteps) : 2;
    }

    /**
     * @return int
     */
    public function getBarWidth(): int
    {
        return $this->barWidth;
    }

    /**
     * @param int $barWidth
     */
    public function setBarWidth(int $barWidth)
    {
        $this->barWidth = $barWidth;
    }

    /**
     * @param mixed $completeChar
     */
    public function setCompleteChar(string $completeChar)
    {
        $this->completeChar = $completeChar;
    }

    /**
     * Gets the complete bar character.
     * @return string A character
     */
    public function getCompleteChar(): string
    {
        if (null === $this->completeChar) {
            return $this->max ? '=' : $this->completeChar;
        }

        return $this->completeChar;
    }

    /**
     * @return string
     */
    public function getProgressChar(): string
    {
        return $this->progressChar;
    }

    /**
     * @param string $progressChar
     */
    public function setProgressChar(string $progressChar)
    {
        $this->progressChar = $progressChar;
    }

    /**
     * @return string
     */
    public function getRemainingChar(): string
    {
        return $this->remainingChar;
    }

    /**
     * @param string $remainingChar
     */
    public function setRemainingChar(string $remainingChar)
    {
        $this->remainingChar = $remainingChar;
    }

    public function getPercent()
    {
        return $this->percent;
    }

    public function getStartTime()
    {
        return $this->startTime;
    }

    public function getFinishTime()
    {
        return $this->finishTime;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat(string $format)
    {
        $this->format = $format;
    }

    private static function loadDefaultFormatters()
    {
        return [
            'bar' => function (ProgressBar $bar, OutputInterface $output) {
                $completeBars = floor($bar->getMaxSteps() > 0 ? $bar->getPercent() * $bar->getBarWidth() : $bar->getProgress() % $bar->getBarWidth());
                $display = str_repeat($bar->getBarCharacter(), $completeBars);

                if ($completeBars < $bar->getBarWidth()) {
                    $emptyBars = $bar->getBarWidth() - $completeBars;
                    $display .= $bar->getProgressChar() . str_repeat($bar->getRemainingChar(), $emptyBars);
                }

                return $display;
            },
            'elapsed' => function (ProgressBar $bar) {
                return Helper::formatTime(time() - $bar->getStartTime());
            },
            'remaining' => function (ProgressBar $bar) {
                if (!$bar->getMaxSteps()) {
                    throw new LogicException('Unable to display the remaining time if the maximum number of steps is not set.');
                }

                if (!$bar->getProgress()) {
                    $remaining = 0;
                } else {
                    $remaining = round((time() - $bar->getStartTime()) / $bar->getProgress() * ($bar->getMaxSteps() - $bar->getProgress()));
                }

                return Helper::formatTime($remaining);
            },
            'estimated' => function (ProgressBar $bar) {
                if (!$bar->getMaxSteps()) {
                    throw new LogicException('Unable to display the estimated time if the maximum number of steps is not set.');
                }

                if (!$bar->getProgress()) {
                    $estimated = 0;
                } else {
                    $estimated = round((time() - $bar->getStartTime()) / $bar->getProgress() * $bar->getMaxSteps());
                }

                return Helper::formatTime($estimated);
            },
            'memory' => function (ProgressBar $bar) {
                return Helper::formatMemory(memory_get_usage(true));
            },
            'current' => function (ProgressBar $bar) {
                return str_pad($bar->getProgress(), $bar->getStepWidth(), ' ', STR_PAD_LEFT);
            },
            'max' => function (ProgressBar $bar) {
                return $bar->getMaxSteps();
            },
            'percent' => function (ProgressBar $bar) {
                return floor($bar->getPercent() * 100);
            },
        ];
    }

    private static function defaultFormats()
    {
        return [
            'normal' => ' %current%/%max% [%bar%] %percent:3s%%',
            'normal_nomax' => ' %current% [%bar%]',

            'verbose' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%',
            'verbose_nomax' => ' %current% [%bar%] %elapsed:6s%',

            'very_verbose' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%',
            'very_verbose_nomax' => ' %current% [%bar%] %elapsed:6s%',

            'debug' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%',
            'debug_nomax' => ' %current% [%bar%] %elapsed:6s% %memory:6s%',
        ];
    }
}