<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-12-21
 * Time: 9:51
 */

namespace Inhere\Console\Components;

use Inhere\Console\Utils\CliUtil;

/**
 * Class ExecComparator - PHP code exec speed comparator
 * @package Inhere\Console\Components
 */
class ExecComparator
{
    /**
     * @var string
     */
    public $tmpDir;

    /**
     * @var array
     */
    private $vars = [];

    /** @var string[] */
    private $sample1;

    /** @var string[] */
    private $sample2;

    /** @var string */
    private $common;

    /** @var int */
    private $loops = 0;

    /** @var string */
    private $time;

    /**
     * ExecComparator constructor.
     * @param string|null $tmpDir
     */
    public function __construct(string $tmpDir = null)
    {
        $this->tmpDir = $tmpDir ?? CliUtil::getTempDir();
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setCommon(string $code)
    {
        $this->common = $code;

        return $this;
    }

    /**
     * @param int $times
     * @return $this
     */
    public function setLoops(int $times)
    {
        if ($times <= 0) {
            throw new \InvalidArgumentException('The time must be gt zero');
        }

        $this->loops = $times;

        return $this;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setSample1(string $code)
    {
        $this->sample1['code'] = $code;

        return $this;
    }

    /**
     * @param string $code
     * @return $this
     */
    public function setSample2(string $code)
    {
        $this->sample2['code'] = $code;

        return $this;
    }

    /**
     * @param array $context
     * @param int $loops
     * @return array
     */
    public function compare(array $context = [], int $loops = 0)
    {
        if ($loops) {
            $this->setLoops($loops);
        }

        $sTime = microtime(1);
        $this->time = date('ymdH');

        $id = 1;
        $file1 = $this->dump($this->sample1['code'], $id);
        $info1 = $this->runSampleFile($file1, $id);

        $id = 2;
        $file2 = $this->dump($this->sample2['code'], $id);
        $info2 = $this->runSampleFile($file2, $id);
        $eTime = microtime(1);

        return [
            $info1,
            $info2,
            'total' => [
                'startTime' => $sTime,
                'endTime' => $eTime,
            ]
        ];
    }

    public function runSampleFile(string $file, int $id)
    {
        $func = 'sample_func_' . $id;
        $sMem = memory_get_usage();
        $sTime = microtime(1);

        // load and running
        ob_start();
        require $file;
        $ret = $func();
        $out = ob_get_clean();

        $eMem = memory_get_usage();
        $eTime = microtime(1);

        return [
            'startTime' => $sTime,
            'endTime' => $eTime,
            'startMem' => $sMem,
            'endMem' => $eMem,
            'output' => $out,
            'return' => $ret,
        ];
    }

    public function dump(string $code, int $id, array $context = [])
    {
        $file = $this->tmpDir . '/' . $this->time . '_' . md5($code . random_int(1000, 100000)) . '.php';
        $common = $this->common;

        $content = <<<CODE
function sample_func_{$id}() {
    // prepare
$common

    // exec
    for (\$i = 0; \$i < $this->loops; \$i++) {
    $code
    }
}
CODE;

        file_put_contents($file, '<?php' . PHP_EOL . $content);

        return $file;
    }

    /**
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @param array $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }
}
