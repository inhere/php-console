<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used:
 * file: Color.php
 */

namespace inhere\console\style;

use inhere\console\utils\Helper;

/**
 * Class Style
 * @package inhere\console\style
 * @link https://github.com/ventoviro/windwalker-IO
 */
class Style
{
    /**
     * there are some default styles
     */
    const NORMAL = 'normal';
    const FAINTLY = 'faintly';
    const BOLD = 'bold';
    const NOTICE = 'notice';
    const PRIMARY = 'primary';
    const SUCCESS = 'success';
    const INFO = 'info';
    const WARNING = 'warning';
    const COMMENT = 'comment';
    const QUESTION = 'question';
    const DANGER = 'danger';
    const ERROR = 'error';

    /**
     * @var self
     */
    private static $instance;

    /**
     * Flag to remove color codes from the output
     * @var bool
     */
    public $noColor = false;

    /**
     * Regex to match tags
     * @var string
     */
    protected $tagFilter = '/<([a-z=;]+)>(.*?)<\/\\1>/s';

    /**
     * Regex used for removing color codes
     */
    protected static $stripFilter = '/<[\/]?[a-z=;]+>/';

    /**
     * Array of Style objects
     * @var array
     */
    protected $styles = [];

    /**
     * @return Style
     */
    public static function create(): Style
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     * @param  string $fg 前景色(字体颜色)
     * @param  string $bg 背景色
     * @param  array $options 其它选项
     * @throws  \InvalidArgumentException
     */
    public function __construct($fg = '', $bg = '', array $options = [])
    {
        if ($fg || $bg || $options) {
            $this->add('base', [
                'fg' => $fg,
                'bg' => $bg,
                'options' => $options
            ]);
        }

        $this->loadDefaultStyles();
    }

    /**
     * Adds predefined color styles to the Color styles
     * default primary success info warning danger
     */
    protected function loadDefaultStyles()
    {
        $this->add(self::NORMAL, ['fg' => 'normal', 'options' => ['underscore'] ])
            // 不明显的 浅灰色的
            ->add(self::FAINTLY, ['fg' => 'normal', 'options' => ['italic'] ])
            ->add(self::BOLD, ['options' => ['bold'] ])
            ->add(self::INFO, [ 'fg' => 'green', ])     //'options' => ['bold']
            ->add(self::PRIMARY, [ 'fg' => 'blue', ])   //'options' => ['bold']
            ->add(self::SUCCESS, ['fg' => 'green', 'options' => ['bold'] ])
            ->add(self::NOTICE, ['options' => ['bold', 'underscore'], ])
            ->add(self::WARNING, ['fg' => 'black', 'bg' => 'yellow', ]) //'options' => ['bold']
            ->add(self::COMMENT, [ 'fg' => 'yellow', ])  //'options' => ['bold']
            ->add(self::QUESTION, ['fg' => 'black', 'bg' => 'cyan'])
            ->add(self::DANGER, ['fg' => 'red', ])       // 'bg' => 'magenta', 'options' => ['bold']
            ->add(self::ERROR, ['fg' => 'white', 'bg' => 'red']);
    }

//////////////////////////////////////////// Text Color handle ////////////////////////////////////////////

    /**
     * Process a string.
     * @param $text
     * @return mixed
     */
    public function render($text)
    {
        return $this->format($text);
    }

    /**
     * @param $text
     * @return mixed|string
     */
    public function format($text)
    {
        if (!$text) {
            return $text;
        }

        // if don't support output color text, clear color tag.
        if (!Helper::isSupportColor()) {
            return static::stripColor($text);
        }

        preg_match_all($this->tagFilter, $text, $matches);

        if (!$matches) {
            return $text;
        }

        foreach ((array)$matches[0] as $i => $m) {
            if (array_key_exists($matches[1][$i], $this->styles)) {
                $text = $this->replaceColor($text, $matches[1][$i], $matches[2][$i], $this->styles[$matches[1][$i]]);

                // Custom style format @see Style::makeByString()
            } elseif (strpos($matches[1][$i], '=')) {
                $text = $this->replaceColor($text, $matches[1][$i], $matches[2][$i], Color::makeByString($matches[1][$i]));
            }
        }

        return $text;
    }

    /**
     * Replace color tags in a string.
     * @param string $text
     * @param   string $tag The matched tag.
     * @param   string $match The match.
     * @param   Color $color The color style to apply.
     * @return  string
     */
    protected function replaceColor($text, $tag, $match, Color $color): string
    {
        $style = $color->toStyle();
        $replace = $this->noColor ? $match : "\033[{$style}m{$match}\033[0m";

        return str_replace("<$tag>$match</$tag>", $replace, $text);
    }

    /**
     * Strip color tags from a string.
     * @param $string
     * @return mixed
     */
    public static function stripColor($string)
    {
        // $text = strip_tags($text);
        return preg_replace(static::$stripFilter, '', $string);
    }

///////////////////////////////////////// Attr Color Style /////////////////////////////////////////

    /**
     * Add a style.
     * @param  string $name
     * @param  string|Color|array $fg 前景色|也可以穿入Color对象|也可以是style配置数组(@see self::addByArray())
     *                                      当它为Color对象或配置数组时，后面两个参数无效
     * @param  string $bg 背景色
     * @param  array $options 其它选项
     * @return $this
     */
    public function add($name, $fg = '', $bg = '', array $options = [])
    {
        if (is_array($fg)) {
            return $this->addByArray($name, $fg);
        }

        if (is_object($fg) && $fg instanceof Color) {
            $this->styles[$name] = $fg;
        } else {
            $this->styles[$name] = Color::make($fg, $bg, $options);
        }

        return $this;
    }

    /**
     * Add a style by an array config
     * @param $name
     * @param array $styleConfig 样式设置信息
     * e.g  [
     *       'fg' => 'white',
     *       'bg' => 'black',
     *       'options' => ['bold', 'underscore']
     *   ]
     * @return $this
     */
    public function addByArray($name, array $styleConfig)
    {
        $style = [
            'fg' => '',
            'bg' => '',
            'options' => []
        ];

        $config = array_merge($style, $styleConfig);
        list($fg, $bg, $options) = array_values($config);

        $this->styles[$name] = Color::make($fg, $bg, $options);

        return $this;
    }

    /**
     * @return array
     */
    public function getStyleNames(): array
    {
        return array_keys($this->styles);
    }

    /**
     * @return array
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * @param $name
     * @return Style|null
     */
    public function getStyle($name)
    {
        if (!isset($this->styles[$name])) {
            return null;
        }

        return $this->styles[$name];
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasStyle($name): bool
    {
        return isset($this->styles[$name]);
    }

    /**
     * Method to get property NoColor
     */
    public function isNoColor(): bool
    {
        return (bool)$this->noColor;
    }

    /**
     * Method to set property noColor
     * @param $noColor
     * @return $this
     */
    public function setNoColor($noColor)
    {
        $this->noColor = (bool)$noColor;

        return $this;
    }
}
