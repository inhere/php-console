<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-12-31
 * Time: 14:27
 */

namespace Inhere\Console\Examples\Controller;

use Inhere\Console\Component\Formatter\HelpPanel;
use Inhere\Console\Component\Formatter\Panel;
use Inhere\Console\Component\Symbol\Char;
use Inhere\Console\Component\Symbol\Emoji;
use Inhere\Console\Controller;
use Inhere\Console\IO\Input;
use Inhere\Console\Util\Show;
use ReflectionException;
use Toolkit\Cli\Color;
use Toolkit\Cli\Highlighter;
use function file_get_contents;

/**
 * Class ShowController
 * @package Inhere\Console\Examples\Controller
 */
class ShowController extends Controller
{
    protected static $name = 'show';

    protected static $description = 'there are some demo commands for show format data';

    public static function commandAliases(): array
    {
        return [
            'hp'  => 'helpPanel',
            'hpl' => 'helpPanel',
            'hl'  => 'highlight',
        ];
    }

    /**
     * @return array
     */
    protected function groupOptions(): array
    {
        return [
            '-c, --common' => 'This is a common option for all sub-commands',
        ];
    }

    /**
     * output format message: title
     */
    public function titleCommand(): int
    {
        $this->output->title('title show');

        return 0;
    }

    /**
     * output format message: splitLine
     * @options
     *  -w, --width WIDTH   The split line width. default is current screen width.
     */
    public function splitLineCommand(): int
    {
        $this->output->splitLine('', '=', $this->getSameOpt(['w', 'width'], 0));
        $this->output->splitLine('split Line', '-', $this->getSameOpt(['w', 'width'], 0));
        $this->output->splitLine('split 中文 Line', '-', $this->getSameOpt(['w', 'width'], 0));

        return 0;
    }

    /**
     * output format message: section
     */
    public function sectionCommand(): int
    {
        $body = 'If screen size could not be detected, or the indentation is greater than the screen size, the text will not be wrapped.' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,' .
            'Word wrap text with indentation to fit the screen size,';

        $this->output->section('section show', $body, [
            'pos' => 'l'
        ]);

        return 0;
    }

    /**
     * output format message: panel
     */
    public function panelCommand(): void
    {
        $data = [
            'application version' => '1.2.0',
            'system version'      => '5.2.3',
            'key'                 => 'value ...',
            'a only value message text',
        ];

        Show::panel($data, 'panel show', [
            'borderChar' => '*'
        ]);

        Show::panel($data, 'panel show', [
            'borderChar' => '='
        ]);

        Panel::create([
            'data'        => $data,
            'title'       => 'panel show',
            'titleBorder' => '=',
            'footBorder'  => '=',
        ])->display();
    }

    /**
     * a example for use color text output by Style::class
     * @usage {fullCommand}
     * @return int
     */
    public function colorCommand(): int
    {
        if (!$this->output->supportColor()) {
            $this->write('Current terminal is not support output color text.');
            return 0;
        }

        $this->write('color style text output:');
        $styles = $this->output->getStyle()->getStyleNames();

        foreach ($styles as $style) {
            $this->output->write("<$style>$style style text</$style>");
        }

        return 0;
    }

    /**
     * a example for use color text output by Style::class
     */
    public function colorLiteCommand(): int
    {
        if (!$this->output->supportColor()) {
            $this->write('Current terminal is not support output color text.');
            return -2;
        }

        $this->output->startBuffer();

        foreach (Color::getStyles() as $style) {
            $this->output->write(Color::render("color text(style:$style)", $style));
        }

        $this->output->flush();

        return 0;
    }

    /**
     * output block message text
     * @return int
     */
    public function blockMsgCommand(): int
    {
        if (!$this->output->supportColor()) {
            $this->write('Current terminal is not support output color text.');

            return 0;
        }

        $this->write('block message:');

        foreach (Show::getBlockMethods() as $type) {
            $this->output->$type("$type style message text");
        }

        return 0;
    }

    /**
     * display some special chars
     * @return int
     * @throws ReflectionException
     */
    public function charCommand(): int
    {
        $this->output->aList(Char::getConstants(), 'some special char', [
            'ucFirst' => false,
        ]);

        return 0;
    }

    /**
     * display some special emoji chars
     * @return int
     * @throws ReflectionException
     */
    public function emojiCommand(): int
    {
        $this->output->aList(Emoji::getConstants(), 'some emoji char', [
            'ucFirst' => false,
        ]);

        return 0;
    }

    /**
     * a example for highlight code
     * @options
     *  --ln    Display with line number
     * @param Input $in
     */
    public function highlightCommand($in): void
    {
        // $file = $this->app->getRootPath() . '/examples/routes.php';
        $file = $this->app->getRootPath() . '/src/Utils/Show.php';
        $src  = file_get_contents($file);

        $code = Highlighter::create()->highlight($src, $in->getBoolOpt('ln'));

        $this->output->writeRaw($code);
    }

    /**
     * output format message: helpPanel
     */
    public function helpPanelCommand(): void
    {
        Show::helpPanel([
            HelpPanel::DESC      => 'a help panel description text. (help panel show)',
            HelpPanel::USAGE     => 'a usage text',
            HelpPanel::ARGUMENTS => [
                'arg1' => 'arg1 description',
                'arg2' => 'arg2 description',
            ],
            HelpPanel::OPTIONS   => [
                '--opt1'     => 'a long option',
                '-s'         => 'a short option',
                '-d'         => 'Run the server on daemon.(default: <comment>false</comment>)',
                '-h, --help' => 'Display this help message'
            ],
        ]);
    }

    /**
     * output format message: tree
     */
    public function treeCommand(): void
    {
        Show::tree([
            123,
            true,
            false,
            null,
            'one-level',
            'one-level1',

            [
                'two-level',
                'two-level1',
                'two-level2',
                [
                    'three-level',
                    'three-level1',
                    'three-level2',
                ],
            ],

            'one-level99',
        ]);
    }

    /**
     * output format message: dump
     */
    public function jsonCommand(): void
    {
        $data = [
            [
                'id'     => 1,
                'name'   => 'john',
                'status' => 2,
                'email'  => 'john@email.com',
            ],
            [
                'id'     => 2,
                'name'   => 'tom',
                'status' => 0,
                'email'  => 'tom@email.com',
            ],
            [
                'id'     => 3,
                'name'   => 'jack',
                'status' => 1,
                'email'  => 'jack-test@email.com',
            ],
        ];

        $this->output->write('use dump:');
        $this->output->dump($data);

        $this->output->write('use print:');
        $this->output->prints($data);

        $this->output->write('use json:');
        $this->output->json($data);
    }
}
