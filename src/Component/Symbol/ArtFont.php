<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-24
 * Time: 9:23
 */

namespace Inhere\Console\Component\Symbol;

use Inhere\Console\Console;
use Toolkit\Cli\ColorTag;
use function dirname;
use function file_get_contents;
use function in_array;
use function is_file;

/**
 * Class ArtFont art fonts Manager
 *
 * @package Inhere\Console\Component\Symbol
 */
class ArtFont
{
    public const DEFAULT_GROUP  = '_default';

    public const INTERNAL_GROUP = '_internal';

    /** @var self */
    private static $instance;

    /** @var array */
    private static $internalFonts = ['404', '500', 'error', 'no', 'ok', 'success', 'yes'];

    /**
     * @var array <name:path>
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
    protected function loadInternalFonts(): void
    {
        $path  = dirname(__DIR__) . '/BuiltIn/Resources/art-fonts/';
        $group = self::INTERNAL_GROUP;

        foreach (self::$internalFonts as $font) {
            $this->fonts[$group][$font] = $path . $font . '%s.txt';
        }

        $this->groups[$group] = $path;
    }

    /**
     * display the internal art font
     *
     * @param string $name
     * @param array  $opts
     *
     * @return int
     */
    public function showInternal(string $name, array $opts = []): int
    {
        return $this->show($name, self::INTERNAL_GROUP, $opts);
    }

    /**
     * @param string $name
     * @param string $group
     * @param array  $opts
     *
     * @return int
     */
    public function showItalic(string $name, string $group = null, array $opts = []): int
    {
        $opts['type'] = 'italic';

        return $this->show($name . '_italic', $group, $opts);
    }

    /**
     * display the art font
     *
     * @param string $name
     * @param string $group
     * @param array  $opts
     * contains:
     * - type => '', // 'italic'
     * - indent => 2,
     * - style => '', // 'info' 'error'
     *
     * @return int
     */
    public function show(string $name, string $group = null, array $opts = []): int
    {
        $opts = array_merge([
            'type'   => '',
            'indent' => 2,
            'style'  => '',
        ], $opts);

        $type    = $opts['type'];
        $pfxType = $type ? '_' . $type : '';

        $txt     = '';
        $group   = trim($group);
        $group   = $group ?: self::DEFAULT_GROUP;
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
            return Console::write(ColorTag::wrap($txt, $opts['style']));
        }

        return 0;
    }

    /**
     * @param string $name
     * @param string $group
     *
     * @return string
     */
    public function font(string $name, string $group = null): string
    {
        return '';
    }

    /**
     * @param string $group
     * @param string $path
     *
     * @return $this
     */
    public function addGroup(string $group, string $path): self
    {
        $group = trim($group, '_');

        if (!$group || !is_dir($path)) {
            return $this;
        }

        if (!isset($this->groups[$group])) {
            $this->groups[$group] = $path;
        }

        return $this;
    }

    /**
     * @param string $group
     * @param string $path
     *
     * @return $this
     */
    public function setGroup(string $group, string $path): self
    {
        $group = trim($group, '_');

        if (!$group || !is_dir($path)) {
            return $this;
        }

        $this->groups[$group] = $path;

        return $this;
    }

    /**
     * @param string      $name
     * @param string      $file font file path
     * @param string|null $group
     *
     * @return $this
     */
    public function addFont(string $name, string $file, string $group = null): self
    {
        $group = $group ?: self::DEFAULT_GROUP;

        if (is_file($file)) {
            $info = pathinfo($file);
            $ext  = !empty($info['extension']) ? $info['extension'] : 'txt';

            $this->fonts[$group][$name] = $info['dirname'] . '/' . $info['filename'] . '.' . $ext;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param string $content
     *
     * @return $this
     */
    public function addFontContent(string $name, string $content): self
    {
        if ($name && ($content = trim($content))) {
            $this->fontContents[$name] = $content;
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function isInternalFont($name): bool
    {
        return in_array((string)$name, self::$internalFonts, true);
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
    public function setGroups(array $groups): void
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
    public function setFonts(array $fonts): void
    {
        foreach ($fonts as $name => $font) {
            $this->addFont($name, $font);
        }
    }
}
