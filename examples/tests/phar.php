<?php
/**
 * Created by PhpStorm.
 * User: inhere
 * Date: 2017-10-27
 * Time: 10:02
 */

$srcDir = dirname(__DIR__, 2);
$pharFile = $srcDir . '/project.phar';
//var_dump($srcDir);die;

// create with alias "project.phar"
$phar = new Phar($pharFile, 0, basename($pharFile));
$phar->setSignatureAlgorithm(Phar::SHA1);

// 开始打包
$phar->startBuffering();

// add all files in the project, only include php files
$phar->buildFromDirectory($srcDir, '/[\.php|app]$/');
$phar->setStub($phar::createDefaultStub('examples/app'));
//$phar->setStub($phar::createDefaultStub('examples/app', 'www/index.php'));

$phar->stopBuffering();

// 打包完成
echo "Finished {$pharFile}\n";
