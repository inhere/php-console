<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-24
 * Time: 9:23
 */

namespace Inhere\Console\Components;

use Inhere\Console\BuiltIn\ArtFonts\ArtFontsBlock;
use Inhere\Console\Utils\Helper;
use Inhere\Console\Utils\Show;

/**
 * Class ArtFont art fonts Manager
 * @package Inhere\Console\Components
 */
class ArtFont
{
    const DEFAULT_GROUP = '_default';
    const INTERNAL_GROUP = '_internal';

    /** @var self */
    private static $instance;

    /** @var array  */
    private static $internalFonts = ['404', '500', 'error', 'no', 'ok', 'success', 'yes'];

    /**
     * @var array<name:path>
     */
    private $groups = [];

    /**
     * @var array
     * [
     *   group => [ name => path ]
     * ]
     */
    private $fonts = [];

    /**
     * @var array
     * [
     *   name => content
     * ]
     */
    private $fontContents = [];

    /**
     * @return self
     */
    public static function create(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * ArtFont constructor.
     */
    public function __construct()
    {
        $this->loadInternalFonts();
    }

    /**
     * load Internal Fonts
     */
    protected function loadInternalFonts()
    {
        $path = \dirname(__DIR__) . '/BuiltIn/Resources/art-fonts/';
        $group = self::INTERNAL_GROUP;

        foreach (self::$internalFonts as $font) {
            $this->fonts[$group][$font] = $path . $font . '%s.txt';
        }

        $this->groups[$group] = $path;
    }

    /**
     * display the internal art font
     * @param string $name
     * @param array $opts
     * @return bool
     */
    public function showInternal(string $name, array $opts = [])
    {
        return $this->show($name, self::INTERNAL_GROUP, $opts);
    }

    /**
     * display the art font
     * @param string $name
     * @param string $group
     * @param array $opts
     * contains:
     * - type => '', // 'italic'
     * - indent => 2,
     * - style => '', // 'info' 'error'
     * @return bool
     */
    public function show(string $name, string $group = null, array $opts = [])
    {
        $opts = array_merge([
            'type' => '',
            'indent' => 2,
            'style' => '',
        ], $opts);

        $type = $opts['type'];
        $pfxType = $type ? '_' . $type : '';

        $txt = '';
        $group = trim($group);
        $group = $group ?: self::DEFAULT_GROUP;
        $longKey = $group . '.' . $name . $pfxType;

        if (isset($this->fontContents[$longKey])) {
            $txt = $this->fontContents[$longKey];
        } elseif (isset($this->fonts[$group][$name])) {
            $font = sprintf($this->fonts[$group][$name], $pfxType);

            if (is_file($font)) {
                $txt = file_get_contents($font);
            }
        } elseif (isset($this->groups[$group])) {
            $font = $this->groups[$group] . $name . $pfxType . '.txt';

            if (is_file($font)) {
                $txt = file_get_contents($font);
            }
        }

        // var_dump($txt, $this);
        if ($txt) {
            return Show::write(Helper::wrapTag($txt, $opts['style']));
        }

        return false;
    }

    /**
     * @param string $name
     * @param string $group
     * @return string
     */
    public function font(string $name, string $group = null)
    {
        return '';
    }

    /**
     * @param string $name
     * @param string $group
     * @return string
     */
    public function italic(string $name, string $group = null)
    {
        return $this->font($name . '_italic', $group);
    }

    public function addFontsFromPath(string $path, string $group)
    {

    }

    /**
     * @param string $name
     * @param string $file font file path
     * @return $this
     */
    public function addFont(string $name, string $file)
    {
        if (is_file($file) && ($txt = trim(file_get_contents($file)))) {
            $this->fonts[$name] = $txt;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     * @return $this
     */
    public function addFontContent(string $name, string $content)
    {
        if ($name && ($content = trim($content))) {
            $this->fontContents[$name] = $content;
        }

        return $this;
    }

    /**
     * @param string $name Named fonts path
     * @param string $path fonts path
     * @return ArtFont
     */
    public function addPath(string $name, string $path)
    {
        if (file_exists($path)) {
            $this->groups[$name] = $path;
        }

        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isInternalFont($name): bool
    {
        return \in_array((string)$name, self::$internalFonts, true);
    }

    /**
     * @return array
     */
    public static function getInternalFonts(): array
    {
        return self::$internalFonts;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param array $groups
     */
    public function setGroups(array $groups)
    {
        $this->groups = array_merge($this->groups, $groups);
    }

    /**
     * @return array
     */
    public function getFonts(): array
    {
        return $this->fonts;
    }

    /**
     * @param array $fonts
     */
    public function setFonts(array $fonts)
    {
        foreach ($fonts as $name => $font) {
            $this->addFont($name, $font);
        }
    }
}
