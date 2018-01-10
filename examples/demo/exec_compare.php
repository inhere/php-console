<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2018-01-10
 * Time: 19:36
 */

use Inhere\Console\Components\ExecComparator;
use Inhere\Console\Utils\CliUtil;

require dirname(__DIR__, 2) . '/tests/boot.php';

$common = <<<CODE
    \$text = 'hello, world!';
CODE;

// preg_match()
$code1 = <<<CODE
    preg_match('/wor/', \$text);
CODE;

// strpos()
$code2 = <<<CODE
    strpos(\$text, 'wor');
CODE;
// var_dump(CliUtil::getTempDir());die;
$ec = new ExecComparator();
$ec
    ->setCommon($common)
    ->setLoops(1000000)
    ->setSample1($code1)
    ->setSample2($code2);

$ret = $ec->compare();

print_r($ret);