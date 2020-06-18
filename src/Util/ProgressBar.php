<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 2017/3/7 0007
 * Time: 22:36
 */

namespace Inhere\Console\Util;

use Closure;
use Inhere\Console\IO\Output;
use Inhere\Console\Contract\OutputInterface;
use LogicException;
use RuntimeException;
use Toolkit\Stdlib\Str;
use function max;

/**
 * Class ProgressBar
 *
 * @package Inhere\Console\Util
 * @form \Symfony\Component\Console\Helper\ProgressBar
 *
 * ```
 *     1 [->--------------------------]
 *     3 [■■■>------------------------]
 * 25/50 [==============>-------------]  50%
 * ```
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
     *
     * @var float
     */
    private $percent = 0.0;

    /**
     * maximal steps.
     * 当前在多少步
     *
     * @var int
     */
    private $step = 0;

    /**
     * maximal steps.
     * 设置多少步就会走完进度条,
     *
     * @var int
     */
    private $maxSteps;

    /**
     * step Width
     * 设置步长
     *
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
     *
     * @var array
     */
    private $messages = [];

    /**
     * section parsers
     *
     * @var Closure[]
     */
    private static $parsers = [// 'precent' => function () { ... },
    ];

    public const DEFAULT_FORMAT = '[{@bar}] {@percent:3s}%({@current}/{@max}) {@elapsed:6s}/{@estimated:-6s} {@memory:6s}';

    /**
     * @param OutputInterface $output
     * @param int             $maxSteps
     *
     * @return ProgressBar
     */
    public static function create(OutputInterface $output = null, int $maxSteps = 0): ProgressBar
    {
        return new self($output, $maxSteps);
    }

    /**
     * @param OutputInterface $output
     * @param int             $maxSteps
     */
    public function __construct(OutputInterface $output = null, int $maxSteps = 0)
    {
        $this->output = $output ?: new Output;

        $this->setMaxSteps($maxSteps);
        // Helper::loadAttrs($this, $config);
    }

    /**
     * 开始
     *
     * @param null $maxSteps
     *
     * @throws LogicException
     */
    public function start($maxSteps = null): void
    {
        if ($this->started) {
            throw new LogicException('Progress bar already started.');
        }

        $this->startTime = time();
        $this->step      = 0;
        $this->percent   = 0.0;
        $this->started   = true;

        if (null !== $maxSteps) {
            $this->setMaxSteps($maxSteps);
        }

        $this->display();
    }

    /**
     * 前进，按步长前进几步
     *
     * @param int $step 前进几步
     *
     * @throws LogicException
     */
    public function advance(int $step = 1): void
    {
        if (!$this->started) {
            throw new LogicException('Progress indicator has not yet been started.');
        }

        $this->advanceTo($this->step + $step);
    }

    /**
     * 直接前进到第几步
     *
     * @param int $step 第几步
     */
    public function advanceTo(int $step): void
    {
        if ($this->maxSteps && $step > $this->maxSteps) {
            $this->maxSteps = $step;
        } elseif ($step < 0) {
            $step = 0;
        }

        $prevPeriod    = (int)($this->step / $this->redrawFreq);
        $currPeriod    = (int)($step / $this->redrawFreq);
        $this->step    = $step;
        $this->percent = $this->maxSteps ? (float)$this->step / $this->maxSteps : 0;

        if ($prevPeriod !== $currPeriod || $this->maxSteps === $step) {
            $this->display();
        }
    }

    /**
     * Finishes the progress output.
     *
     * @throws LogicException
     */
    public function finish(): void
    {
        if (!$this->started) {
            throw new LogicException('Progress bar has not yet been started.');
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

        $this->output->write('');
    }

    /**
     * Outputs the current progress string.
     */
    public function display(): void
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
    public function clear(): void
    {
        if (!$this->overwrite) {
            return;
        }

        $this->render('');
    }

    /**
     * render
     *
     * @param string $text
     */
    public function render(string $text): void
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
    }

    /**
     * @return mixed
     * @throws RuntimeException
     */
    protected function buildLine()
    {
        // $regex = "{%([a-z\-_]+)(?:\:([^%]+))?%}i";
        return preg_replace_callback('/{@([\w]+)(?:\:([\w-]+))?}/i', function ($matches) {
            if ($formatter = $this->getParser($matches[1])) {
                $text = $formatter($this, $this->output);
            } elseif (isset($this->messages[$matches[1]])) {
                $text = $this->messages[$matches[1]];
            } else {
                return $matches[1];
            }

            if (isset($matches[2])) {
                $text = sprintf('%' . $matches[2], $text);
            }

            return $text;
        }, $this->format);
    }

    /**
     * set section Parser
     *
     * @param string   $section
     * @param callable $handler
     */
    public function setParser(string $section, callable $handler): void
    {
        self::$parsers[$section] = $handler;
    }

    /**
     * Get section Parser
     *
     * @param string $section
     * @param bool    $throwException
     *
     * @return mixed
     * @throws RuntimeException
     */
    public function getParser(string $section, bool $throwException = false)
    {
        if (!self::$parsers) {
            self::$parsers = self::loadDefaultParsers();
        }

        if (isset(self::$parsers[$section])) {
            return self::$parsers[$section];
        }

        if ($throwException) {
            throw new RuntimeException("The section($section) formatter is not registered!", -500);
        }

        return null;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param array $messages
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * set a named Message
     *
     * @param string $message The text to associate with the placeholder
     * @param string $name    The name of the placeholder
     */
    public function setMessage($message, string $name = 'message'): void
    {
        $this->messages[$name] = $message;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function getMessage(string $name = 'message'): string
    {
        return $this->messages[$name];
    }

    /**
     * Gets the current step position.
     *
     * @return int The progress bar step
     */
    public function getProgress(): int
    {
        return $this->step;
    }

    public function getStep(): int
    {
        return $this->step;
    }

    /**
     * Sets the redraw frequency.
     *
     * @param int|float $freq The frequency in steps
     */
    public function setRedrawFreq($freq): void
    {
        $this->redrawFreq = max((int)$freq, 1);
    }

    public function setOverwrite(bool $overwrite): void
    {
        $this->overwrite = $overwrite;
    }

    /**
     * Sets the progress bar maximal steps.
     *
     * @param int $maxSteps The progress bar max steps
     */
    private function setMaxSteps(int $maxSteps): void
    {
        $this->maxSteps  = max(0, $maxSteps);
        $this->stepWidth = $this->maxSteps ? Str::len($this->maxSteps) : 2;
    }

    /**
     * @return int
     */
    public function getMaxSteps(): int
    {
        return $this->maxSteps;
    }

    /**
     * @return int
     */
    public function getStepWidth(): int
    {
        return $this->stepWidth;
    }

    /**
     * @param int $stepWidth
     */
    public function setStepWidth(int $stepWidth): void
    {
        $this->stepWidth = $stepWidth;
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
    public function setBarWidth(int $barWidth): void
    {
        $this->barWidth = $barWidth;
    }

    /**
     * @param mixed $completeChar
     */
    public function setCompleteChar(string $completeChar): void
    {
        $this->completeChar = $completeChar;
    }

    /**
     * Gets the complete bar character.
     *
     * @return string A character
     */
    public function getCompleteChar(): string
    {
        if (null === $this->completeChar) {
            return $this->maxSteps ? '=' : $this->completeChar;
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
    public function setProgressChar(string $progressChar): void
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
    public function setRemainingChar(string $remainingChar): void
    {
        $this->remainingChar = $remainingChar;
    }

    /**
     * @return float
     */
    public function getPercent(): float
    {
        return $this->percent;
    }

    /**
     * @return mixed
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * @return mixed
     */
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
    public function setFormat(string $format): void
    {
        $this->format = $format;
    }

    /**
     * @return array
     * @throws LogicException
     */
    private static function loadDefaultParsers(): array
    {
        return [
            'bar'       => static function (self $bar) {
                $barWidth = $bar->getBarWidth();
                $completeBars = (int)floor($bar->getMaxSteps() > 0 ? $bar->getPercent() * $barWidth :
                    $bar->getProgress() % $barWidth);
                $display      = str_repeat($bar->getCompleteChar(), $completeBars);

                if ($completeBars < $barWidth) {
                    $emptyBars = $barWidth - $completeBars;
                    $display   .= $bar->getProgressChar() . str_repeat($bar->getRemainingChar(), $emptyBars);
                }

                return $display;
            },
            'elapsed'   => static function (self $bar) {
                return FormatUtil::howLongAgo(time() - $bar->getStartTime());
            },
            'remaining' => static function (self $bar) {
                if (!$bar->getMaxSteps()) {
                    throw new LogicException('Unable to display the remaining time if the maximum number of steps is not set.');
                }

                $progress = $bar->getProgress();
                if (!$progress) {
                    $remaining = 0;
                } else {
                    $remaining = (int)round((time() - $bar->getStartTime()) / $progress * ($bar->getMaxSteps() - $progress));
                }

                return FormatUtil::howLongAgo($remaining);
            },
            'estimated' => static function (self $bar) {
                if (!$bar->getMaxSteps()) {
                    return 0;
                    // throw new \LogicException('Unable to display the estimated time if the maximum number of steps is not set.');
                }

                if (!$bar->getProgress()) {
                    $estimated = 0;
                } else {
                    $estimated = (int)round((time() - $bar->getStartTime()) / $bar->getProgress() * $bar->getMaxSteps());
                }

                return FormatUtil::howLongAgo($estimated);
            },
            'memory'    => static function () {
                return FormatUtil::memoryUsage(memory_get_usage(true));
            },
            'current'   => static function (self $bar) {
                return Str::pad($bar->getProgress(), $bar->getStepWidth(), ' ', STR_PAD_LEFT);
            },
            'max'       => static function (self $bar) {
                return $bar->getMaxSteps();
            },
            'percent'   => static function (self $bar) {
                return (float)floor($bar->getPercent() * 100);
            },
        ];
    }

    /*
     * @return array
     */
    //    private static function defaultFormats()
    //    {
    //        return [
    //            'normal' => ' %current%/%max% [%bar%] %percent:3s%%',
    //            'normal_nomax' => ' %current% [%bar%]',
    //
    //            'verbose' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%',
    //            'verbose_nomax' => ' %current% [%bar%] %elapsed:6s%',
    //
    //            'very_verbose' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s%',
    //            'very_verbose_nomax' => ' %current% [%bar%] %elapsed:6s%',
    //
    //            'debug' => ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%',
    //            'debug_nomax' => ' %current% [%bar%] %elapsed:6s% %memory:6s%',
    //        ];
    //    }
}
