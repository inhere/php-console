<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017/5/29
 * Time: 下午9:29
 */
use inhere\console\examples\baks\OldInput;
use inhere\console\Helper;
use inhere\console\io\Input;

require __DIR__ . '/s-autoload.php';

echo Helper::dumpVar(new Input());die;

//$ret1 = OldInput::parseGlobalArgv();
$ret2 = Input::parseOptArgs();

//echo "parseGlobalArgv:\n" . Helper::dumpVar($ret1);

echo "parseOptArgs:\n" . Helper::dumpVar($ret2);
