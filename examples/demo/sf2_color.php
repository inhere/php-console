<?php declare(strict_types=1);
/**
 * The file is part of inhere/console
 *
 * @author   https://github.com/inhere
 * @homepage https://github.com/inhere/php-console
 * @license  https://github.com/inhere/php-console/blob/master/LICENSE
 */

/**
 * Formatter style class for defining styles.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class OutputFormatterStyle
{
    private static array $availableForegroundColors = [
        'black' => ['set' => 30, 'unset' => 39],
        'red' => ['set' => 31, 'unset' => 39],
        'green' => ['set' => 32, 'unset' => 39],
        'yellow' => ['set' => 33, 'unset' => 39],
        'blue' => ['set' => 34, 'unset' => 39],
        'magenta' => ['set' => 35, 'unset' => 39],
        'cyan' => ['set' => 36, 'unset' => 39],
        'white' => ['set' => 37, 'unset' => 39],
        'default' => ['set' => 39, 'unset' => 39],
    ];

    private static array $availableBackgroundColors = [
        'black' => ['set' => 40, 'unset' => 49],
        'red' => ['set' => 41, 'unset' => 49],
        'green' => ['set' => 42, 'unset' => 49],
        'yellow' => ['set' => 43, 'unset' => 49],
        'blue' => ['set' => 44, 'unset' => 49],
        'magenta' => ['set' => 45, 'unset' => 49],
        'cyan' => ['set' => 46, 'unset' => 49],
        'white' => ['set' => 47, 'unset' => 49],
        'default' => ['set' => 49, 'unset' => 49],
    ];

    private static array $availableOptions = [
        'bold' => ['set' => 1, 'unset' => 22],
        'underscore' => ['set' => 4, 'unset' => 24],
        'blink' => ['set' => 5, 'unset' => 25],
        'reverse' => ['set' => 7, 'unset' => 27],
        'conceal' => ['set' => 8, 'unset' => 28],
    ];

    private array $foreground = [];

    private array $background = [];

    private array $options = [];

    /**
     * Initializes output formatter style.
     *
     * @param string|null $foreground The style foreground color name
     * @param string|null $background The style background color name
     * @param array       $options    The style options
     */
    public function __construct(string $foreground = null, string $background = null, array $options = [])
    {
        if (null !== $foreground) {
            $this->setForeground($foreground);
        }
        if (null !== $background) {
            $this->setBackground($background);
        }
        if (count($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Sets style foreground color.
     *
     * @param string|null $color The color name
     *
     * @throws InvalidArgumentException When the color name isn't defined
     */
    public function setForeground(string $color = null): void
    {
        if (null === $color) {
            $this->foreground = null;
            return;
        }
        if (!isset(static::$availableForegroundColors[$color])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid foreground color specified: "%s". Expected one of (%s)',
                $color,
                implode(', ', array_keys(static::$availableForegroundColors))
            ));
        }
        $this->foreground = static::$availableForegroundColors[$color];
    }

    /**
     * Sets style background color.
     *
     * @param string|null $color The color name
     *
     * @throws InvalidArgumentException When the color name isn't defined
     */
    public function setBackground(string $color = null): void
    {
        if (null === $color) {
            $this->background = null;
            return;
        }
        if (!isset(static::$availableBackgroundColors[$color])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid background color specified: "%s". Expected one of (%s)',
                $color,
                implode(', ', array_keys(static::$availableBackgroundColors))
            ));
        }
        $this->background = static::$availableBackgroundColors[$color];
    }

    /**
     * Sets some specific style option.
     *
     * @param string $option The option name
     *
     * @throws InvalidArgumentException When the option name isn't defined
     */
    public function setOption(string $option): void
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(static::$availableOptions))
            ));
        }
        if (!in_array(static::$availableOptions[$option], $this->options, true)) {
            $this->options[] = static::$availableOptions[$option];
        }
    }

    /**
     * Unsets some specific style option.
     *
     * @param string $option The option name
     *
     * @throws InvalidArgumentException When the option name isn't defined
     */
    public function unsetOption(string $option): void
    {
        if (!isset(static::$availableOptions[$option])) {
            throw new InvalidArgumentException(sprintf(
                'Invalid option specified: "%s". Expected one of (%s)',
                $option,
                implode(', ', array_keys(static::$availableOptions))
            ));
        }
        $pos = array_search(static::$availableOptions[$option], $this->options, true);
        if (false !== $pos) {
            unset($this->options[$pos]);
        }
    }

    /**
     * Sets multiple style options at once.
     *
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = [];
        foreach ($options as $option) {
            $this->setOption($option);
        }
    }

    /**
     * Applies the style to a given text.
     *
     * @param string $text The text to style
     *
     * @return string
     */
    public function apply(string $text): string
    {
        $setCodes = [];
        $unsetCodes = [];
        if ($this->foreground) {
            $setCodes[] = $this->foreground['set'];
            $unsetCodes[] = $this->foreground['unset'];
        }
        if ($this->background) {
            $setCodes[] = $this->background['set'];
            $unsetCodes[] = $this->background['unset'];
        }
        if (count($this->options)) {
            foreach ($this->options as $option) {
                $setCodes[] = $option['set'];
                $unsetCodes[] = $option['unset'];
            }
        }
        if (0 === count($setCodes)) {
            return $text;
        }
        return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }
}

$clr = new OutputFormatterStyle('red');
echo $clr->apply('message');
