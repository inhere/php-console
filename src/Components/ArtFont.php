<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-24
 * Time: 9:23
 */

namespace Inhere\Console\Components;

/**
 * Class ArtFont
 * @package Inhere\Console\Components
 */
class ArtFont
{
    private static $instance;

    /**
     * @var array
     */
    private $artPaths = [];

    /**
     * @var array
     */
    private $fonts = [];

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
     * @param string $name
     * @param array $opts
     */
    public function draw(string $name, array $opts = [])
    {

    }

    public function addFont(string $name, string $content)
    {
        if ($name && $content) {
            $this->fonts[$name] = $content;
        }
    }

    /**
     * @param string $path
     */
    public function addPath(string $path)
    {
        if (file_exists($path)) {
            $this->artPaths[] = $path;
        }
    }

    /**
     * @return array
     */
    public function getArtPaths(): array
    {
        return $this->artPaths;
    }

    /**
     * @param array $artPaths
     */
    public function setArtPaths(array $artPaths)
    {
        foreach ($artPaths as $path) {
            $this->addPath($path);
        }
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