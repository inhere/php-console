<?php
/**
 * Created by PhpStorm.
 * Used:
 * file: Style.php
 * @todo  unused
 */

namespace inhere\console\color;

/**
 *@link https://github.com/ventoviro/windwalker-IO
 */
final class Style
{

//////////////////////////////////////////// Color Style ////////////////////////////////////////////

    /**
     * Known color list
     */
    private static $knownColors = array(
        'black'   => 0,
        'red'     => 1,
        'green'   => 2,
        'yellow'  => 3,
        'blue'    => 4,
        'magenta' => 5, // 洋红色 洋红 品红色
        'cyan'    => 6, // 青色 青绿色 蓝绿色
        'white'   => 7,
        'default' => 9
    );

    /**
     * Known style option
     * @var array
     */
    private static $knownOptions = [
        'bold'       => 1,      // 加粗
        'fuzzy'      => 2,      // 模糊(不是所有的终端仿真器都支持)
        'italic'     => 3,      // 斜体(不是所有的终端仿真器都支持)
        'underscore' => 4,      // 下划线
        'blink'      => 5,      // 闪烁
        'reverse'    => 7,      // 颠倒的 交换背景色与前景色
    ];

    /**
     * Foreground base value
     */
    private static $fgBase = 30;

    /**
     * Background base value
     */
    private static $bgBase = 40;

    /**
     * Foreground color
     */
    private $fgColor = 0;

    /**
     * Background color
     */
    private $bgColor = 0;

    /**
     * Array of style options
     */
    private $options = [];

    public static function make($fg = '', $bg = '', array $options = [])
    {
        return new self($fg, $bg, $options);
    }

    /**
     * Create a color style from a parameter string.
     *
     * @param string $string e.g 'fg=white;bg=black;options=bold,underscore'
     * @return static
     */
    public static function makeByString($string)
    {
        $fg = $bg = '';
        $options = [];
        $parts = explode(';', $string);

        foreach ($parts as $part) {
            $subParts = explode('=', $part);

            if (count($subParts) < 2) {
                continue;
            }

            switch ($subParts[0]) {
                case 'fg':
                    $fg = $subParts[1];
                    break;
                case 'bg':
                    $bg = $subParts[1];
                    break;
                case 'options':
                    $options = explode(',', $subParts[1]);
                    break;

                default:
                    throw new \RuntimeException('Invalid option');
                    break;
            }
        }

        return new self($fg, $bg, $options);
    }

    /**
     * Constructor
     * @param string $fg Foreground color.  e.g 'white'
     * @param string $bg Background color.  e.g 'black'
     * @param array $options Style options. e.g ['bold', 'underscore']
     */
    public function __construct($fg = '', $bg = '', array $options = [])
    {
        if ($fg) {
            if (false === array_key_exists($fg, static::$knownColors)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid foreground color "%1$s" [%2$s]',
                        $fg,
                        implode(', ', $this->getKnownColors())
                    )
                );
            }

            $this->fgColor = static::$fgBase + static::$knownColors[$fg];
        }

        if ($bg) {
            if (false === array_key_exists($bg, static::$knownColors)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid background color "%1$s" [%2$s]',
                        $bg,
                        implode(', ', $this->getKnownColors())
                    )
                );
            }

            $this->bgColor = static::$bgBase + static::$knownColors[$bg];
        }

        foreach ($options as $option) {
            if (false === array_key_exists($option, static::$knownOptions)) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid option "%1$s" [%2$s]',
                        $option,
                        implode(', ', $this->getKnownOptions())
                    )
                );
            }

            $this->options[] = $option;
        }
    }

    /**
     * Convert to a string.
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Get the translated color code.
     */
    public function toString()
    {
        $values = [];

        if ($this->fgColor) {
            $values[] = $this->fgColor;
        }

        if ($this->bgColor) {
            $values[] = $this->bgColor;
        }

        foreach ($this->options as $option) {
            $values[] = static::$knownOptions[$option];
        }

        return implode(';', $values);
    }

    /**
     * Get the known colors.
     * @param bool $onlyName
     * @return array
     */
    public function getKnownColors($onlyName=true)
    {
        return (bool)$onlyName ? array_keys(static::$knownColors) : static::$knownColors;
    }

    /**
     * Get the known options.
     * @param bool $onlyName
     * @return array
     */
    public function getKnownOptions($onlyName=true)
    {
        return (bool)$onlyName ? array_keys(static::$knownOptions) : static::$knownOptions;
    }

}
